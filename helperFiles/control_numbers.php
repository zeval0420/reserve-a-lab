<?php
// control_numbers.php - automatic control numbers (see tasks-subject-teacher-release.md)
//
// Four independent series live on scilab_form_requests:
//   equipment   -> control_equipment    (e.g. CIID-20-001)
//   reagent     -> control_reagent      (e.g. RG-001)
//   permit      -> control_permit       (e.g. PT-001)
//   reservation -> control_reservation  (e.g. CID-05-001)
// Prefix / padding / start come from scilab_control_sequences. A form's position in a
// series is (controlAssignedAt, id). Legacy int controlNumber values are ignored when
// computing next numbers: only strings of the form <prefix><digits> count.

if (!function_exists('scilab_control_series')) {

    /** Series key => column on scilab_form_requests. */
    function scilab_control_series()
    {
        return [
            'equipment'   => 'control_equipment',
            'reagent'     => 'control_reagent',
            'permit'      => 'control_permit',
            'reservation' => 'control_reservation',
        ];
    }

    /** Series config (prefix, padWidth, startNumber) from scilab_control_sequences, with safe defaults. */
    function scilab_control_config($conn, $type)
    {
        static $cache = [];
        $defaults = [
            'equipment'   => ['prefix' => 'CIID-20-', 'padWidth' => 3, 'startNumber' => 1],
            'reagent'     => ['prefix' => 'RG-',      'padWidth' => 3, 'startNumber' => 1],
            'permit'      => ['prefix' => 'PT-',      'padWidth' => 3, 'startNumber' => 1],
            'reservation' => ['prefix' => 'CID-05-',  'padWidth' => 3, 'startNumber' => 1],
        ];
        if (!isset($defaults[$type])) {
            return null;
        }
        if (!isset($cache[$type])) {
            $cfg = $defaults[$type];
            try {
                $stmt = $conn->prepare("SELECT prefix, padWidth, startNumber FROM scilab_control_sequences WHERE formType = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $type);
                    $stmt->execute();
                    $row = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    if ($row) {
                        $cfg = [
                            'prefix'      => (string)$row['prefix'],
                            'padWidth'    => max(0, (int)$row['padWidth']),
                            'startNumber' => (int)$row['startNumber'],
                        ];
                    }
                }
            } catch (Throwable $e) {
                // fall back to defaults
            }
            $cache[$type] = $cfg;
        }
        return $cache[$type];
    }

    /** True when the connection currently has an open transaction. */
    function scilab_control_in_transaction($conn)
    {
        try {
            $res = $conn->query("SELECT @@in_transaction AS t");
            if ($res) {
                $row = $res->fetch_assoc();
                $res->free();
                return (int)($row['t'] ?? 0) === 1;
            }
        } catch (Throwable $e) {
        }
        return false;
    }

    /** Format prefix + number with zero padding. */
    function scilab_control_format($prefix, $number, $padWidth)
    {
        return $prefix . str_pad((string)$number, (int)$padWidth, '0', STR_PAD_LEFT);
    }

    /** Split "CIID-20-007" into ['prefix' => 'CIID-20-', 'number' => 7, 'width' => 3]; null when no trailing integer. */
    function scilab_control_parse($value)
    {
        $value = trim((string)$value);
        if ($value === '' || !preg_match('/^(.*?)(\d+)$/s', $value, $m)) {
            return null;
        }
        return ['prefix' => $m[1], 'number' => (int)$m[2], 'width' => strlen($m[2])];
    }

    /** Take the series-wide lock (serialises number assignment and shifting across requests). */
    function scilab_control_lock_series($conn, $type)
    {
        $stmt = $conn->prepare("SELECT formType FROM scilab_control_sequences WHERE formType = ? FOR UPDATE");
        if ($stmt) {
            $stmt->bind_param("s", $type);
            $stmt->execute();
            $stmt->get_result();
            $stmt->close();
        }
    }

    /**
     * Next number of a series as a formatted string: MAX integer suffix of existing
     * "<prefix><digits>" values + 1 (startNumber when there are none). A cancel/reject leaves
     * a gap; nothing else renumbers. With $forUpdate the read is a locking read, so it sees
     * the latest committed data even inside a REPEATABLE READ transaction (needs an open
     * transaction and the series lock).
     */
    function scilab_next_control_number($conn, $type, $forUpdate = false)
    {
        $series = scilab_control_series();
        $cfg = scilab_control_config($conn, $type);
        if (!$cfg || !isset($series[$type])) {
            return '';
        }
        $col = $series[$type];
        $prefix = $cfg['prefix'];
        $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $prefix) . '%';
        $pos = strlen($prefix) + 1;

        $sql = "SELECT MAX(CAST(SUBSTRING($col, ?) AS UNSIGNED)) AS m FROM scilab_form_requests
                WHERE $col LIKE ? AND SUBSTRING($col, ?) REGEXP '^[0-9]+$'" . ($forUpdate ? " FOR UPDATE" : "");
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $pos, $like, $pos);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $next = ($row && $row['m'] !== null) ? ((int)$row['m'] + 1) : $cfg['startNumber'];
        $next = max($next, $cfg['startNumber']);
        return scilab_control_format($prefix, $next, $cfg['padWidth']);
    }

    /** Preview of the next number of every series (no locks, nothing reserved). */
    function scilab_preview_control_numbers($conn)
    {
        $out = [];
        foreach (array_keys(scilab_control_series()) as $type) {
            $out[$type] = scilab_next_control_number($conn, $type, false);
        }
        return $out;
    }

    /**
     * Fill the blank control series of an Approved request. Idempotent: existing values are
     * never touched. Joins the caller's transaction when one is open, otherwise runs in its
     * own. Returns ['ok' => bool, 'assigned' => [type => value], 'message' => string].
     */
    function scilab_assign_control_numbers($conn, $formID)
    {
        $formID = (int)$formID;
        $series = scilab_control_series();
        $owns = !scilab_control_in_transaction($conn);
        if ($owns) {
            $conn->begin_transaction();
        }
        try {
            $stmt = $conn->prepare("SELECT statusScilabPersonnel, control_equipment, control_reagent, control_permit, control_reservation, controlAssignedAt FROM scilab_form_requests WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $formID);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // Only a request that is actually approved receives numbers
            if (!$row || $row['statusScilabPersonnel'] !== 'Approved') {
                if ($owns) {
                    $conn->rollback();
                }
                return ['ok' => false, 'assigned' => [], 'message' => 'Request is not approved.'];
            }

            $blank = [];
            $anyFilled = false;
            foreach ($series as $type => $col) {
                if (trim((string)($row[$col] ?? '')) === '') {
                    $blank[] = $type;
                } else {
                    $anyFilled = true;
                }
            }

            $assigned = [];
            if ($blank) {
                foreach ($blank as $type) {
                    scilab_control_lock_series($conn, $type);
                    $assigned[$type] = scilab_next_control_number($conn, $type, true);
                    $col = $series[$type];
                    $upd = $conn->prepare("UPDATE scilab_form_requests SET $col = ? WHERE id = ? AND ($col IS NULL OR $col = '')");
                    $upd->bind_param("si", $assigned[$type], $formID);
                    $upd->execute();
                    $upd->close();
                }

                // Position in the series: a form that already had numbers keeps its place;
                // a fresh (or previously cleared) one goes to the end.
                if (!$anyFilled || $row['controlAssignedAt'] === null) {
                    $upd = $conn->prepare("UPDATE scilab_form_requests SET controlAssignedAt = NOW(6) WHERE id = ?");
                    $upd->bind_param("i", $formID);
                    $upd->execute();
                    $upd->close();
                }

                // Legacy int column mirrors the equipment suffix (falls back to any other assigned series)
                $legacy = null;
                $eq = scilab_control_parse($assigned['equipment'] ?? $row['control_equipment'] ?? '');
                if ($eq) {
                    $legacy = $eq['number'];
                } else {
                    foreach ($assigned as $v) {
                        $p = scilab_control_parse($v);
                        if ($p) { $legacy = $p['number']; break; }
                    }
                }
                if ($legacy !== null) {
                    $upd = $conn->prepare("UPDATE scilab_form_requests SET controlNumber = ? WHERE id = ?");
                    $upd->bind_param("ii", $legacy, $formID);
                    $upd->execute();
                    $upd->close();
                }
            }

            if ($owns) {
                $conn->commit();
            }
            return ['ok' => true, 'assigned' => $assigned, 'message' => ''];
        } catch (Throwable $e) {
            if ($owns) {
                $conn->rollback();
                return ['ok' => false, 'assigned' => [], 'message' => 'Control number assignment failed: ' . $e->getMessage()];
            }
            throw $e; // caller's transaction: let it roll back
        }
    }

    /**
     * Set one control number and shift the following forms of that series.
     *
     * "Later" = higher controlAssignedAt, then higher id. If $newValue is prefix + integer,
     * every later form of the series is renumbered value+1, value+2, ... keeping the prefix
     * and zero padding of the edited value. Rejected (nothing saved) when the renumbered
     * sequence would collide with an earlier form's number. A value without a trailing
     * integer is saved on this form only (shifted = false). A blank value clears the field.
     *
     * Returns ['ok' => bool, 'message' => string, 'value' => string, 'changed' => bool,
     *          'shift' => bool (true when a shift was performed), 'shifted' => int (later forms renumbered)].
     */
    function scilab_set_control_number($conn, $formID, $type, $newValue)
    {
        $formID = (int)$formID;
        $series = scilab_control_series();
        $fail = function ($msg) use ($newValue) {
            return ['ok' => false, 'message' => $msg, 'value' => (string)$newValue, 'changed' => false, 'shift' => false, 'shifted' => 0];
        };
        if (!isset($series[$type])) {
            return $fail('Unknown control number series.');
        }
        $col = $series[$type];
        $newValue = trim((string)$newValue);
        if (strlen($newValue) > 100) {
            return $fail('Control number is too long.');
        }

        $owns = !scilab_control_in_transaction($conn);
        $attempts = $owns ? 3 : 1;
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            if ($owns) {
                $conn->begin_transaction();
            }
            try {
                $result = scilab_set_control_number_locked($conn, $formID, $type, $col, $newValue);
                if ($owns) {
                    if ($result['ok']) {
                        $conn->commit();
                    } else {
                        $conn->rollback();
                    }
                }
                return $result;
            } catch (Throwable $e) {
                if ($owns) {
                    $conn->rollback();
                    $deadlock = ($e instanceof mysqli_sql_exception && in_array((int)$e->getCode(), [1213, 1205], true));
                    if ($deadlock && $attempt < $attempts) {
                        usleep(100000 * $attempt);
                        continue;
                    }
                    return $fail('Could not save the control number: ' . $e->getMessage());
                }
                throw $e;
            }
        }
        return $fail('Could not save the control number.');
    }

    /** Body of scilab_set_control_number; runs inside an open transaction. */
    function scilab_set_control_number_locked($conn, $formID, $type, $col, $newValue)
    {
        $res = function ($ok, $msg, $changed, $shift, $shifted) use ($newValue) {
            return ['ok' => $ok, 'message' => $msg, 'value' => $newValue, 'changed' => $changed, 'shift' => $shift, 'shifted' => $shifted];
        };

        scilab_control_lock_series($conn, $type);

        $stmt = $conn->prepare("SELECT id, $col AS val, controlAssignedAt FROM scilab_form_requests WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $formID);
        $stmt->execute();
        $form = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$form) {
            return $res(false, 'Request not found.', false, false, 0);
        }
        $current = trim((string)($form['val'] ?? ''));
        if ($current === $newValue) {
            return $res(true, 'Control number unchanged.', false, false, 0);
        }

        $parsed = ($newValue === '') ? null : scilab_control_parse($newValue);

        // Blank / free text: save this form only, no shifting
        if ($parsed === null) {
            $this_value = ($newValue === '') ? null : $newValue;
            $upd = $conn->prepare("UPDATE scilab_form_requests SET $col = ? WHERE id = ?");
            $upd->bind_param("si", $this_value, $formID);
            $upd->execute();
            $upd->close();
            if ($newValue !== '' && $form['controlAssignedAt'] === null) {
                $conn->query("UPDATE scilab_form_requests SET controlAssignedAt = NOW(6) WHERE id = " . (int)$formID);
            }
            $msg = ($newValue === '')
                ? 'Control number cleared.'
                : 'Control number saved. It has no trailing number, so no later forms were shifted.';
            return $res(true, $msg, true, false, 0);
        }

        // Give a form without a position one (end of the series) so the order is well defined
        $assignedAt = $form['controlAssignedAt'];
        if ($assignedAt === null) {
            $conn->query("UPDATE scilab_form_requests SET controlAssignedAt = NOW(6) WHERE id = " . (int)$formID);
            $r = $conn->query("SELECT controlAssignedAt FROM scilab_form_requests WHERE id = " . (int)$formID);
            $assignedAt = $r->fetch_assoc()['controlAssignedAt'];
            $r->free();
        }
        $zero = '1000-01-01 00:00:00.000000';
        $myKey = (string)$assignedAt;

        // Every other numbered form of the series, in stable order, locked
        $members = [];
        $stmt = $conn->prepare("SELECT id, $col AS val, controlAssignedAt FROM scilab_form_requests
                                WHERE id <> ? AND $col REGEXP '[0-9]$'
                                ORDER BY COALESCE(controlAssignedAt, '$zero') ASC, id ASC FOR UPDATE");
        $stmt->bind_param("i", $formID);
        $stmt->execute();
        $rs = $stmt->get_result();
        while ($m = $rs->fetch_assoc()) {
            $members[] = $m;
        }
        $stmt->close();

        $earlier = [];
        $later = [];
        foreach ($members as $m) {
            $key = $m['controlAssignedAt'] === null ? $zero : (string)$m['controlAssignedAt'];
            $cmp = strcmp($key, $myKey);
            if ($cmp === 0) {
                $cmp = ((int)$m['id'] <=> $formID);
            }
            if ($cmp < 0) {
                $earlier[] = $m;
            } else {
                $later[] = $m;
            }
        }

        // The new sequence must not collide with an earlier form's number (same prefix, same integer)
        $first = $parsed['number'];
        $last = $first + count($later);
        foreach ($earlier as $m) {
            $p = scilab_control_parse($m['val']);
            if ($p && strcasecmp($p['prefix'], $parsed['prefix']) === 0 && $p['number'] >= $first && $p['number'] <= $last) {
                $where = ($p['number'] === $first)
                    ? $newValue . ' is already used by the earlier form'
                    : 'renumbering the later forms up to ' . scilab_control_format($parsed['prefix'], $last, $parsed['width']) . ' would overlap the earlier form';
                return $res(false, 'Not saved: ' . $where . ' numbered ' . $m['val'] . '.', false, false, 0);
            }
        }

        $save = function ($id, $value, $number) use ($conn, $col, $type) {
            $upd = $conn->prepare("UPDATE scilab_form_requests SET $col = ? WHERE id = ?");
            $upd->bind_param("si", $value, $id);
            $upd->execute();
            $upd->close();
            if ($type === 'equipment') {
                // legacy int column mirrors the equipment suffix
                $leg = $conn->prepare("UPDATE scilab_form_requests SET controlNumber = ? WHERE id = ?");
                $leg->bind_param("ii", $number, $id);
                $leg->execute();
                $leg->close();
            }
        };

        $save($formID, $newValue, $first);

        $shifted = 0;
        $n = $first;
        foreach ($later as $m) {
            $n++;
            $v = scilab_control_format($parsed['prefix'], $n, $parsed['width']);
            if ($v === trim((string)$m['val'])) {
                continue;
            }
            $save((int)$m['id'], $v, $n);
            $shifted++;
        }

        $msg = 'Control number saved.';
        if ($shifted > 0) {
            $msg .= ' ' . $shifted . ' later form' . ($shifted === 1 ? ' was' : 's were') . ' shifted.';
        } else {
            $msg .= ' No later forms needed shifting.';
        }
        return $res(true, $msg, true, true, $shifted);
    }

    /**
     * Control number shown for an item row of the request summary, chosen by classification:
     * Equipment / Semi Expendable / Glassware / Consumable -> equipment form, Reagent -> reagent
     * form, Food Lab -> reservation form; falls back to the first non-empty of the four.
     * $row needs control_equipment / control_reagent / control_permit / control_reservation.
     */
    function scilab_control_for_classification($classification, array $row)
    {
        $map = [
            'equipment'       => 'control_equipment',
            'semi expendable' => 'control_equipment',
            'semi-expendable' => 'control_equipment',
            'glassware'       => 'control_equipment',
            'consumable'      => 'control_equipment',
            'uncategorized'   => 'control_equipment',
            'reagent'        => 'control_reagent',
            'food lab'        => 'control_reservation',
        ];
        $key = strtolower(trim((string)$classification));
        if (isset($map[$key])) {
            $v = trim((string)($row[$map[$key]] ?? ''));
            if ($v !== '') {
                return $v;
            }
        }
        foreach (['control_equipment', 'control_reagent', 'control_permit', 'control_reservation'] as $c) {
            $v = trim((string)($row[$c] ?? ''));
            if ($v !== '') {
                return $v;
            }
        }
        return '';
    }
}
