<?php
// material_release.php - see tasks-subject-teacher-release.md (work package C)
//
// Release Materials and Cancel Request for admin_approve.php (endpoint: ajax/ajax_release.php).
//
// Stock bookkeeping: scilab_request_deductions holds, per request + inventory row, the net
// quantity currently taken out of stock for that request. It is written when the request is
// approved (scilab_deduct_inventory), adjusted here when materials are released with changed
// quantities, and read by the cancel so exactly that quantity is put back.
//
// Locking order everywhere: the request row first, then the inventory rows in id order.

if (!function_exists('scilab_release_clean_id')) {
    /** Trim a scanned / typed ID and drop control characters; '' when unusable. */
    function scilab_release_clean_id($raw)
    {
        $id = preg_replace('/[\x00-\x1F\x7F]/', '', (string)$raw);
        $id = trim($id);
        if ($id === '' || strlen($id) > 50) return '';
        return $id;
    }
}

if (!function_exists('scilab_release_lookup_receiver')) {
    /**
     * Resolve a scanned ID to an employee (accounts.employeeID) or a student (student.LRN).
     * $formID (optional) only adds 'isRequester' so the admin can see whether the receiver
     * is the person who made the request.
     *
     * Returns ['ok' => true, 'type' => 'employee'|'student', 'id' => string, 'name' => string,
     *          'isRequester' => bool] or ['ok' => false, 'message' => string].
     */
    function scilab_release_lookup_receiver($conn, $rawID, $formID = 0)
    {
        $id = scilab_release_clean_id($rawID);
        if ($id === '') {
            return ['ok' => false, 'message' => 'No ID was scanned.'];
        }

        $found = null;

        $stmt = $conn->prepare("SELECT employeeID, firstname, middlename, lastname, status FROM accounts WHERE employeeID = ? LIMIT 1");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $acc = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($acc) {
            if (strtolower(trim((string)$acc['status'])) === 'inactive') {
                return ['ok' => false, 'message' => 'The account for ID ' . $id . ' is inactive and cannot receive materials.'];
            }
            $found = ['type' => 'employee', 'id' => $acc['employeeID'], 'row' => $acc];
        } else {
            $stmt = $conn->prepare("SELECT LRN, firstname, middlename, lastname FROM student WHERE LRN = ? LIMIT 1");
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $stu = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($stu) {
                $found = ['type' => 'student', 'id' => $stu['LRN'], 'row' => $stu];
            }
        }

        if (!$found) {
            return ['ok' => false, 'message' => 'No employee or student found for ID "' . $id . '".'];
        }

        $row = $found['row'];
        $name = trim(preg_replace('/\s+/', ' ', trim((string)$row['firstname']) . ' ' . trim((string)$row['middlename']) . ' ' . trim((string)$row['lastname'])));

        $isRequester = false;
        if ($formID > 0) {
            $stmt = $conn->prepare("SELECT requesterEmployeeID FROM scilab_form_requests WHERE id = ?");
            $stmt->bind_param("i", $formID);
            $stmt->execute();
            $req = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $isRequester = $req && strcasecmp(trim((string)$req['requesterEmployeeID']), $found['id']) === 0;
        }

        return ['ok' => true, 'type' => $found['type'], 'id' => $found['id'], 'name' => $name, 'isRequester' => $isRequester];
    }
}

if (!function_exists('scilab_release_pick_row')) {
    /**
     * Choose the inventory row a requested material belongs to.
     *  - rows the request already has in the ledger win (that is where the stock came from);
     *  - then an exact description match;
     *  - otherwise the row with the most stock, as scilab_deduct_inventory does.
     * $rows: inventory rows (id, item, description, quantity, ...). $ledger: inventoryID => quantity.
     */
    function scilab_release_pick_row(array $rows, array $ledger, $item, $description)
    {
        $item = trim((string)$item);
        $description = trim((string)$description);
        $candidates = array_values(array_filter($rows, function ($inv) use ($item) {
            return strcasecmp(trim((string)$inv['item']), $item) === 0;
        }));
        if (!$candidates) return null;

        $pool = array_values(array_filter($candidates, function ($inv) use ($ledger) {
            return isset($ledger[intval($inv['id'])]);
        }));
        if (!$pool) $pool = $candidates;

        usort($pool, function ($a, $b) {
            return [intval($b['quantity']), intval($a['id'])] <=> [intval($a['quantity']), intval($b['id'])];
        });

        if ($description !== '') {
            foreach ($pool as $inv) {
                if (strcasecmp(trim((string)$inv['description']), $description) === 0) return $inv;
            }
        }
        return $pool[0];
    }
}

if (!function_exists('scilab_release_fetch_rows')) {
    /**
     * Inventory rows by id, ordered by id. With $lock the rows are locked FOR UPDATE
     * (callers pass them in id order so concurrent transactions lock consistently).
     */
    function scilab_release_fetch_rows($conn, array $ids, $lock = false)
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);
        if (!$ids) return [];
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("SELECT id, item, description, classification, quantity, unit, status FROM scilab_inventory WHERE id IN ($ph) ORDER BY id" . ($lock ? ' FOR UPDATE' : ''));
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
}

if (!function_exists('scilab_release_candidate_ids')) {
    /** ids of the active inventory rows whose item name matches one of $names. */
    function scilab_release_candidate_ids($conn, array $names)
    {
        $names = array_values(array_unique(array_filter(array_map('trim', $names), 'strlen')));
        if (!$names) return [];
        $ph = implode(',', array_fill(0, count($names), '?'));
        $stmt = $conn->prepare("SELECT id FROM scilab_inventory WHERE item IN ($ph) AND (status IS NULL OR status != 'Removed')");
        $stmt->bind_param(str_repeat('s', count($names)), ...$names);
        $stmt->execute();
        $ids = array_map('intval', array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id'));
        $stmt->close();
        return $ids;
    }
}

if (!function_exists('scilab_release_form_materials')) {
    /** The original request lines (scilab_material_requests), in id order. */
    function scilab_release_form_materials($conn, $formID)
    {
        $stmt = $conn->prepare("SELECT id, item, quantity, unit, description FROM scilab_material_requests WHERE formID = ? ORDER BY id");
        $stmt->bind_param("i", $formID);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
}

if (!function_exists('scilab_release_ledger')) {
    /** inventoryID => quantity currently deducted for the form (rows not yet restored). */
    function scilab_release_ledger($conn, $formID, $lock = false)
    {
        $stmt = $conn->prepare("SELECT inventoryID, quantity FROM scilab_request_deductions WHERE formID = ? AND restoredAt IS NULL ORDER BY inventoryID" . ($lock ? ' FOR UPDATE' : ''));
        $stmt->bind_param("i", $formID);
        $stmt->execute();
        $out = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
            $out[intval($r['inventoryID'])] = intval($r['quantity']);
        }
        $stmt->close();
        return $out;
    }
}

if (!function_exists('scilab_release_has_ledger_rows')) {
    /** True when the form has any ledger row at all (restored or not): false means a legacy approval. */
    function scilab_release_has_ledger_rows($conn, $formID)
    {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM scilab_request_deductions WHERE formID = ?");
        $stmt->bind_param("i", $formID);
        $stmt->execute();
        $c = intval($stmt->get_result()->fetch_assoc()['c'] ?? 0);
        $stmt->close();
        return $c > 0;
    }
}

if (!function_exists('scilab_release_get_data')) {
    /**
     * Everything the Release Materials modal needs for one request:
     * header, requested materials (autofilled), the inventory catalog for "add item",
     * and the existing release (if any) with its old + new quantities.
     */
    function scilab_release_get_data($conn, $formID)
    {
        $formID = intval($formID);
        $stmt = $conn->prepare("SELECT id, requesterEmployeeID, scilabName, subject, subjectTopic, inclusiveDate, inclusiveTime, statusScilabPersonnel, releaseStatus, cancelledAt FROM scilab_form_requests WHERE id = ?");
        $stmt->bind_param("i", $formID);
        $stmt->execute();
        $req = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$req) return ['ok' => false, 'message' => 'Request not found.'];

        $status = strtolower(trim((string)$req['statusScilabPersonnel']));
        $released = ($req['releaseStatus'] === 'released');

        $materials = scilab_release_form_materials($conn, $formID);
        $ledger = scilab_release_ledger($conn, $formID);
        $legacy = !scilab_release_has_ledger_rows($conn, $formID);

        $names = array_column($materials, 'item');
        $rows = scilab_release_fetch_rows($conn, array_merge(scilab_release_candidate_ids($conn, $names), array_keys($ledger)));

        $outMaterials = [];
        foreach ($materials as $m) {
            $row = scilab_release_pick_row($rows, $ledger, $m['item'], $m['description']);
            $inStock = $row ? intval($row['quantity']) : null;
            $heldHere = $row ? intval($ledger[intval($row['id'])] ?? 0) : 0;
            $outMaterials[] = [
                'materialRequestID' => intval($m['id']),
                'item' => $m['item'],
                'description' => $m['description'],
                'unit' => $m['unit'],
                'quantity' => intval($m['quantity']),
                'inventoryID' => $row ? intval($row['id']) : null,
                'stock' => $inStock,
                // Most that could go out: what is on the shelf plus what this request already holds
                'maxQuantity' => $row ? ($inStock + ($legacy ? intval($m['quantity']) : $heldHere)) : null,
            ];
        }

        $catalog = [];
        $res = $conn->query("SELECT id, item, description, classification, quantity, unit FROM scilab_inventory WHERE (status IS NULL OR status != 'Removed') AND classification != 'Removed' AND TRIM(item) != '' ORDER BY item, description");
        while ($r = $res->fetch_assoc()) {
            $catalog[] = ['id' => intval($r['id']), 'item' => $r['item'], 'description' => $r['description'],
                'classification' => $r['classification'], 'quantity' => intval($r['quantity']), 'unit' => $r['unit']];
        }

        $release = null;
        $stmt = $conn->prepare("SELECT id, releasedAt, releasedBy, receiverType, receiverID, receiverName, scanMethod, remarks FROM scilab_material_releases WHERE formID = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("i", $formID);
        $stmt->execute();
        $rel = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($rel) {
            $stmt = $conn->prepare("SELECT item, description, unit, originalQuantity, releasedQuantity, isAdded FROM scilab_material_release_items WHERE releaseID = ? ORDER BY id");
            $rid = intval($rel['id']);
            $stmt->bind_param("i", $rid);
            $stmt->execute();
            $rel['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            $release = $rel;
        }

        $canRelease = ($status === 'approved') && !$released && empty($req['cancelledAt']);
        return [
            'ok' => true,
            'request' => [
                'id' => intval($req['id']),
                'requesterID' => $req['requesterEmployeeID'],
                'requesterName' => function_exists('ral_display_name') ? ral_display_name($conn, (string)$req['requesterEmployeeID']) : $req['requesterEmployeeID'],
                'lab' => $req['scilabName'],
                'subject' => $req['subject'],
                'topic' => $req['subjectTopic'],
                'date' => scilab_request_dates_label($conn, $req['id'], $req['inclusiveDate']),
                'time' => $req['inclusiveTime'],
                'status' => $req['statusScilabPersonnel'],
                'releaseStatus' => $req['releaseStatus'],
            ],
            'canRelease' => $canRelease,
            'legacy' => $legacy,
            'materials' => $outMaterials,
            'catalog' => $catalog,
            'release' => $release,
        ];
    }
}

if (!function_exists('scilab_release_confirm')) {
    /**
     * Release the materials of an Approved request to a scanned receiver, in one transaction.
     *
     * $items: list of ['materialRequestID' => int] + 'quantity' for the original lines (item,
     * description and the old quantity come from the database, never the client) and
     * ['inventoryID' => int, 'quantity' => int] for added lines. An original line left out
     * is released at its requested quantity.
     * Saves the release header, one row per line with BOTH the requested (old) and released
     * (new) quantity, moves stock by (released - already deducted) with a stock check, and
     * sets releaseStatus = 'released'.
     *
     * Returns ['ok' => true, 'releaseID' => int, 'receiver' => [...], 'message' => string,
     * 'lowStockIDs' => int[]] or ['ok' => false, 'message' => string].
     */
    function scilab_release_confirm($conn, $formID, array $items, $receiverRaw, $scanMethod, $remarks, $adminName)
    {
        $formID = intval($formID);
        $scanMethod = in_array($scanMethod, ['scanner', 'camera', 'manual'], true) ? $scanMethod : 'scanner';
        $remarks = trim((string)$remarks);
        if (function_exists('mb_substr')) $remarks = mb_substr($remarks, 0, 500);
        else $remarks = substr($remarks, 0, 500);
        if (count($items) > 200) return ['ok' => false, 'message' => 'Too many item lines.'];

        $conn->begin_transaction();
        try {
            // 1. The request: lock it first, then check it can still be released
            $stmt = $conn->prepare("SELECT id, statusScilabPersonnel, releaseStatus, cancelledAt FROM scilab_form_requests WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $formID);
            $stmt->execute();
            $req = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$req) { $conn->rollback(); return ['ok' => false, 'message' => 'Request not found.']; }
            if (strtolower(trim((string)$req['statusScilabPersonnel'])) !== 'approved' || !empty($req['cancelledAt'])) {
                $conn->rollback();
                return ['ok' => false, 'message' => 'Only an approved request can have its materials released.'];
            }
            if ($req['releaseStatus'] === 'released') {
                $conn->rollback();
                return ['ok' => false, 'message' => 'The materials of this request were already released.'];
            }

            // 2. The receiver, validated again here (the name the browser showed is never trusted)
            $receiver = scilab_release_lookup_receiver($conn, $receiverRaw, $formID);
            if (!$receiver['ok']) { $conn->rollback(); return ['ok' => false, 'message' => $receiver['message']]; }

            // 3. Original lines + what the ledger says is deducted now
            $materials = scilab_release_form_materials($conn, $formID);
            $byMaterialId = [];
            foreach ($materials as $m) $byMaterialId[intval($m['id'])] = $m;
            $legacy = !scilab_release_has_ledger_rows($conn, $formID);
            $ledger = scilab_release_ledger($conn, $formID, true);

            // 4. Validate the client's lines
            $qtyFor = [];      // materialRequestID => released quantity
            $added = [];       // [inventoryID, quantity]
            foreach ($items as $line) {
                if (!is_array($line)) { $conn->rollback(); return ['ok' => false, 'message' => 'Invalid item line.']; }
                $q = $line['quantity'] ?? null;
                if (is_string($q) && preg_match('/^\d{1,6}$/', trim($q))) $q = intval($q);
                if (!is_int($q) || $q < 0 || $q > 999999) {
                    $conn->rollback();
                    return ['ok' => false, 'message' => 'Quantities must be whole numbers of 0 or more.'];
                }
                if (isset($line['materialRequestID']) && $line['materialRequestID'] !== '' && $line['materialRequestID'] !== null) {
                    $mid = intval($line['materialRequestID']);
                    if (!isset($byMaterialId[$mid]) || isset($qtyFor[$mid])) {
                        $conn->rollback();
                        return ['ok' => false, 'message' => 'A requested item does not belong to this request.'];
                    }
                    $qtyFor[$mid] = $q;
                } else {
                    $inv = intval($line['inventoryID'] ?? 0);
                    if ($inv <= 0) { $conn->rollback(); return ['ok' => false, 'message' => 'Choose an inventory item for each added line.']; }
                    if ($q < 1) { $conn->rollback(); return ['ok' => false, 'message' => 'Added items need a quantity of at least 1.']; }
                    $added[] = [$inv, $q];
                }
            }
            foreach ($byMaterialId as $mid => $m) {
                if (!isset($qtyFor[$mid])) $qtyFor[$mid] = intval($m['quantity']);
            }

            $anyOut = !empty($added);
            foreach ($qtyFor as $q) if ($q > 0) $anyOut = true;
            if (!$anyOut) {
                $conn->rollback();
                return ['ok' => false, 'message' => 'Nothing to release: every quantity is 0. Cancel the request instead.'];
            }

            // 5. Lock every inventory row involved, in id order
            $origIds = array_map('intval', array_merge(
                array_keys($ledger),
                scilab_release_candidate_ids($conn, array_column($materials, 'item'))
            ));
            $rows = scilab_release_fetch_rows($conn, array_merge($origIds, array_column($added, 0)), true);
            $rowById = [];
            foreach ($rows as $r) $rowById[intval($r['id'])] = $r;
            // Original lines only match rows the approval could have used, never a row that was just added
            $origRows = array_values(array_filter($rows, function ($r) use ($origIds) { return in_array(intval($r['id']), $origIds, true); }));

            // 6. Build the release lines and the net stock wanted per inventory row
            $lines = [];
            $desired = [];
            $current = $ledger;
            foreach ($materials as $m) {
                $mid = intval($m['id']);
                $row = scilab_release_pick_row($origRows, $ledger, $m['item'], $m['description']);
                $invID = $row ? intval($row['id']) : null;
                $q = $qtyFor[$mid];
                if ($invID !== null) {
                    $desired[$invID] = ($desired[$invID] ?? 0) + $q;
                    // Approved before the ledger existed: the original quantity was deducted at approval
                    if ($legacy && intval($m['quantity']) > 0) $current[$invID] = ($current[$invID] ?? 0) + intval($m['quantity']);
                }
                $lines[] = ['materialRequestID' => $mid, 'inventoryID' => $invID, 'item' => $m['item'], 'description' => $m['description'],
                    'unit' => $m['unit'], 'orig' => intval($m['quantity']), 'rel' => $q, 'added' => 0];
            }
            foreach ($added as [$inv, $q]) {
                if (!isset($rowById[$inv]) || $rowById[$inv]['classification'] === 'Removed' || $rowById[$inv]['status'] === 'Removed') {
                    $conn->rollback();
                    return ['ok' => false, 'message' => 'An added item is not in the inventory (or was removed).'];
                }
                $r = $rowById[$inv];
                $desired[$inv] = ($desired[$inv] ?? 0) + $q;
                $lines[] = ['materialRequestID' => null, 'inventoryID' => $inv, 'item' => $r['item'], 'description' => $r['description'],
                    'unit' => $r['unit'], 'orig' => null, 'rel' => $q, 'added' => 1];
            }

            // 7. Stock moves: delta = wanted - already deducted. Check first, then apply with guarded UPDATEs.
            $invIds = array_values(array_unique(array_merge(array_keys($desired), array_keys($current))));
            sort($invIds);
            $deltas = [];
            foreach ($invIds as $inv) {
                $delta = ($desired[$inv] ?? 0) - ($current[$inv] ?? 0);
                if ($delta === 0) continue;
                if ($delta > 0 && $delta > intval($rowById[$inv]['quantity'] ?? 0)) {
                    $conn->rollback();
                    $r = $rowById[$inv];
                    return ['ok' => false, 'message' => 'Not enough stock for "' . $r['item'] . '": ' . $delta . ' more needed, only ' . intval($r['quantity']) . ' ' . $r['unit'] . ' left. Nothing was released.'];
                }
                $deltas[$inv] = $delta;
            }

            $dec = $conn->prepare("UPDATE scilab_inventory SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
            $inc = $conn->prepare("UPDATE scilab_inventory SET quantity = quantity + ? WHERE id = ?");
            foreach ($deltas as $inv => $delta) {
                if ($delta > 0) {
                    $dec->bind_param("iii", $delta, $inv, $delta);
                    $dec->execute();
                    $ok = ($dec->affected_rows === 1);
                } else {
                    $back = -$delta;
                    $inc->bind_param("ii", $back, $inv);
                    $inc->execute();
                    $ok = ($inc->affected_rows === 1);
                }
                if (!$ok) {
                    $dec->close(); $inc->close();
                    $conn->rollback();
                    return ['ok' => false, 'message' => 'Stock changed while releasing. Please try again.'];
                }
            }
            $dec->close();
            $inc->close();

            // 8. Ledger: net quantity now held by this request, per inventory row
            $led = $conn->prepare("INSERT INTO scilab_request_deductions (formID, inventoryID, quantity) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), restoredAt = NULL");
            foreach ($invIds as $inv) {
                $want = $desired[$inv] ?? 0;
                if ($want === 0 && !isset($ledger[$inv])) continue;
                $led->bind_param("iii", $formID, $inv, $want);
                $led->execute();
            }
            $led->close();

            // 9. The release: header, then old + new values for every line
            $stmt = $conn->prepare("INSERT INTO scilab_material_releases (formID, releasedBy, receiverType, receiverID, receiverName, scanMethod, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $remarksOrNull = ($remarks === '') ? null : $remarks;
            $stmt->bind_param("issssss", $formID, $adminName, $receiver['type'], $receiver['id'], $receiver['name'], $scanMethod, $remarksOrNull);
            if (!$stmt->execute()) { $stmt->close(); $conn->rollback(); return ['ok' => false, 'message' => 'Could not save the release.']; }
            $releaseID = intval($stmt->insert_id);
            $stmt->close();

            $ins = $conn->prepare("INSERT INTO scilab_material_release_items (releaseID, formID, materialRequestID, inventoryID, item, description, unit, originalQuantity, releasedQuantity, isAdded) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($lines as $l) {
                $desc = (string)$l['description'];
                $ins->bind_param("iiiisssiii", $releaseID, $formID, $l['materialRequestID'], $l['inventoryID'], $l['item'], $desc, $l['unit'], $l['orig'], $l['rel'], $l['added']);
                if (!$ins->execute()) { $ins->close(); $conn->rollback(); return ['ok' => false, 'message' => 'Could not save the released items.']; }
            }
            $ins->close();

            $stmt = $conn->prepare("UPDATE scilab_form_requests SET releaseStatus = 'released' WHERE id = ?");
            $stmt->bind_param("i", $formID);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }

        return [
            'ok' => true,
            'releaseID' => $releaseID,
            'receiver' => ['type' => $receiver['type'], 'id' => $receiver['id'], 'name' => $receiver['name']],
            'message' => 'Materials released to ' . $receiver['name'] . ' (' . ucfirst($receiver['type']) . ' ' . $receiver['id'] . ').',
            'lowStockIDs' => array_keys(array_filter($deltas, function ($d) { return $d > 0; })),
        ];
    }
}

if (!function_exists('scilab_cancel_request')) {
    /**
     * Cancel a Pending or Approved request that has not been released, in one transaction:
     * put back exactly what the ledger says was deducted (once: restoredAt is set), set
     * statusScilabPersonnel = 'Cancelled' with cancelledAt/By/Reason, and clear the control
     * numbers of this request (other requests are not renumbered). The lab slot is freed
     * because slot checks only count 'Approved'.
     *
     * Returns ['ok' => true, 'restored' => [inventoryID => quantity], 'message' => string]
     * or ['ok' => false, 'message' => string].
     */
    function scilab_cancel_request($conn, $formID, $reason, $adminName)
    {
        $formID = intval($formID);
        $reason = trim((string)$reason);
        $reason = function_exists('mb_substr') ? mb_substr($reason, 0, 255) : substr($reason, 0, 255);

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("SELECT id, statusScilabPersonnel, releaseStatus, cancelledAt FROM scilab_form_requests WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $formID);
            $stmt->execute();
            $req = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$req) { $conn->rollback(); return ['ok' => false, 'message' => 'Request not found.']; }

            $status = strtolower(trim((string)$req['statusScilabPersonnel']));
            if ($status === 'cancelled' || !empty($req['cancelledAt'])) {
                $conn->rollback();
                return ['ok' => false, 'message' => 'This request is already cancelled.'];
            }
            if ($req['releaseStatus'] === 'released') {
                $conn->rollback();
                return ['ok' => false, 'message' => 'The materials of this request were already released, so it can no longer be cancelled.'];
            }
            if ($status !== 'approved' && $status !== 'pending') {
                $conn->rollback();
                return ['ok' => false, 'message' => 'Only a pending or approved request can be cancelled.'];
            }

            // Ledger rows still holding stock for this request
            $ledger = scilab_release_ledger($conn, $formID, true);
            $restored = [];
            if ($ledger) {
                // Lock the inventory rows in id order, then put the stock back
                scilab_release_fetch_rows($conn, array_keys($ledger), true);
                $inc = $conn->prepare("UPDATE scilab_inventory SET quantity = quantity + ? WHERE id = ?");
                $mark = $conn->prepare("UPDATE scilab_request_deductions SET restoredAt = NOW() WHERE formID = ? AND inventoryID = ? AND restoredAt IS NULL");
                foreach ($ledger as $inv => $qty) {
                    if ($qty > 0) {
                        $inc->bind_param("ii", $qty, $inv);
                        $inc->execute();
                        if ($inc->affected_rows !== 1) {
                            $inc->close(); $mark->close();
                            $conn->rollback();
                            return ['ok' => false, 'message' => 'Could not return stock to the inventory. Nothing was cancelled.'];
                        }
                        $restored[$inv] = $qty;
                    }
                    $mark->bind_param("ii", $formID, $inv);
                    $mark->execute();
                }
                $inc->close();
                $mark->close();
            }

            // controlNumber is NOT NULL (int), so it goes back to 0; the four text series to NULL.
            $reasonOrNull = ($reason === '') ? null : $reason;
            $stmt = $conn->prepare("UPDATE scilab_form_requests SET statusScilabPersonnel = 'Cancelled', cancelledAt = NOW(), cancelledBy = ?, cancelReason = ?,
                controlNumber = 0, control_equipment = NULL, control_reagent = NULL, control_permit = NULL, control_reservation = NULL, controlAssignedAt = NULL
                WHERE id = ?");
            $stmt->bind_param("ssi", $adminName, $reasonOrNull, $formID);
            if (!$stmt->execute()) { $stmt->close(); $conn->rollback(); return ['ok' => false, 'message' => 'Could not cancel the request.']; }
            $stmt->close();

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }

        if ($restored) {
            $units = array_sum($restored);
            $msg = 'Request cancelled. Returned ' . $units . ' unit(s) across ' . count($restored) . ' inventory item(s) to stock.';
        } elseif ($status === 'approved') {
            $msg = 'Request cancelled. No inventory was returned because no deduction was recorded for it (approved before deductions were tracked, or it had no inventory items).';
        } else {
            $msg = 'Request cancelled. Nothing had been deducted from the inventory.';
        }
        return ['ok' => true, 'restored' => $restored, 'message' => $msg];
    }
}
