<?php
/**
 * Several dates per request (migration sql/2026-10-30_04_request_dates.sql).
 *
 * scilab_request_dates holds one row per day a request uses the lab; the days all share the
 * request's inclusiveTime. scilab_form_requests.inclusiveDate keeps the EARLIEST day.
 */

if (!defined('SCILAB_MAX_REQUEST_DATES')) {
    define('SCILAB_MAX_REQUEST_DATES', 62);
}

if (!function_exists('scilab_ensure_request_dates_table')) {
    /**
     * Create scilab_request_dates (and give existing requests their one row) if migration
     * sql/2026-10-30_04_request_dates.sql has not been run, so a missing migration cannot break
     * the request form. Costs one SHOW TABLES per PHP request, then nothing.
     */
    function scilab_ensure_request_dates_table($conn)
    {
        static $checked = false;
        if ($checked || !($conn instanceof mysqli)) return;
        $checked = true;

        $res = $conn->query("SHOW TABLES LIKE 'scilab_request_dates'");
        if ($res && $res->num_rows > 0) return;

        $created = $conn->query("CREATE TABLE IF NOT EXISTS `scilab_request_dates` (
            `formID`  int(11) NOT NULL,
            `useDate` date    NOT NULL,
            PRIMARY KEY (`formID`, `useDate`),
            KEY `idx_request_dates_date` (`useDate`),
            CONSTRAINT `fk_request_dates_form` FOREIGN KEY (`formID`) REFERENCES `scilab_form_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        if ($created) {
            $conn->query("INSERT IGNORE INTO `scilab_request_dates` (`formID`, `useDate`)
                SELECT `id`, `inclusiveDate` FROM `scilab_form_requests`
                WHERE `inclusiveDate` IS NOT NULL AND `inclusiveDate` <> '0000-00-00'");
        } else {
            error_log('scilab_request_dates could not be created: ' . $conn->error);
        }
    }
}

if (!function_exists('scilab_normalize_dates')) {
    /**
     * Turn submitted dates into a sorted, unique list of 'Y-m-d' strings.
     * Accepts an array, a JSON array string, or a comma separated string.
     * Entries that are not real calendar dates are dropped.
     */
    function scilab_normalize_dates($input)
    {
        if (is_string($input)) {
            $trimmed = trim($input);
            $decoded = ($trimmed !== '' && $trimmed[0] === '[') ? json_decode($trimmed, true) : null;
            $input = is_array($decoded) ? $decoded : explode(',', $trimmed);
        }
        if (!is_array($input)) return [];

        $dates = [];
        foreach ($input as $value) {
            if (!is_string($value)) continue;
            $value = trim($value);
            $d = DateTime::createFromFormat('!Y-m-d', $value);
            if ($d && $d->format('Y-m-d') === $value) {
                $dates[$value] = true;
            }
        }
        $dates = array_keys($dates);
        sort($dates);
        return $dates;
    }
}

if (!function_exists('scilab_format_dates')) {
    /**
     * Readable label for a set of dates: "Nov 3, 5, 9, 10-14, 2026".
     * Three or more days in a row become a range. A single date is returned as plain
     * 'Y-m-d', exactly what the old single-date column showed.
     */
    function scilab_format_dates($dates)
    {
        $dates = scilab_normalize_dates($dates);
        if (count($dates) === 0) return '';
        if (count($dates) === 1) return $dates[0];

        // Group by month, then collapse runs of 3+ consecutive days
        $months = [];
        foreach ($dates as $date) {
            $months[substr($date, 0, 7)][] = (int) substr($date, 8, 2);
        }

        $years = array_unique(array_map(function ($ym) { return substr($ym, 0, 4); }, array_keys($months)));
        $showYearPerMonth = count($years) > 1;

        $parts = [];
        foreach ($months as $ym => $days) {
            $chunks = [];
            $i = 0;
            $n = count($days);
            while ($i < $n) {
                $j = $i;
                while ($j + 1 < $n && $days[$j + 1] === $days[$j] + 1) $j++;
                if ($j - $i >= 2) {
                    $chunks[] = $days[$i] . '-' . $days[$j];
                } else {
                    for ($k = $i; $k <= $j; $k++) $chunks[] = (string) $days[$k];
                }
                $i = $j + 1;
            }
            $label = date('M', strtotime($ym . '-01')) . ' ' . implode(', ', $chunks);
            if ($showYearPerMonth) $label .= ', ' . substr($ym, 0, 4);
            $parts[] = $label;
        }

        $text = implode('; ', $parts);
        if (!$showYearPerMonth) $text .= ', ' . $years[0];
        return $text;
    }
}

if (!function_exists('scilab_request_dates_map')) {
    /**
     * [formID => ['Y-m-d', ...]] for the given requests, earliest first.
     */
    function scilab_request_dates_map($conn, array $formIds)
    {
        scilab_ensure_request_dates_table($conn);
        $formIds = array_values(array_unique(array_filter(array_map('intval', $formIds))));
        $map = [];
        if (!$formIds) return $map;

        $in = implode(',', $formIds); // integers only
        $res = $conn->query("SELECT formID, useDate FROM scilab_request_dates WHERE formID IN ($in) ORDER BY useDate ASC");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $map[(int) $row['formID']][] = $row['useDate'];
            }
        }
        return $map;
    }
}

if (!function_exists('scilab_request_dates')) {
    /**
     * The dates of one request. Falls back to $fallbackDate (the request's inclusiveDate)
     * for a request that has no rows yet.
     */
    function scilab_request_dates($conn, $formID, $fallbackDate = '')
    {
        $map = scilab_request_dates_map($conn, [$formID]);
        $dates = $map[(int) $formID] ?? [];
        if (!$dates && $fallbackDate) $dates = scilab_normalize_dates([$fallbackDate]);
        return $dates;
    }
}

if (!function_exists('scilab_request_dates_label')) {
    function scilab_request_dates_label($conn, $formID, $fallbackDate = '')
    {
        return scilab_format_dates(scilab_request_dates($conn, $formID, $fallbackDate));
    }
}

if (!function_exists('scilab_attach_date_labels')) {
    /**
     * Add 'dateList' (array) and 'dateLabel' (string) to each row of a request list, using one query.
     * Rows need an 'id' and an 'inclusiveDate'.
     */
    function scilab_attach_date_labels($conn, array $rows)
    {
        $ids = [];
        foreach ($rows as $row) $ids[] = $row['id'] ?? 0;
        $map = scilab_request_dates_map($conn, $ids);

        foreach ($rows as &$row) {
            $list = $map[(int) ($row['id'] ?? 0)] ?? scilab_normalize_dates([$row['inclusiveDate'] ?? '']);
            $row['dateList'] = $list;
            $row['dateLabel'] = scilab_format_dates($list);
        }
        unset($row);
        return $rows;
    }
}

if (!function_exists('scilab_save_request_dates')) {
    /** Replace a request's dates and keep inclusiveDate on the earliest. Returns false on failure. */
    function scilab_save_request_dates($conn, $formID, array $dates)
    {
        $dates = scilab_normalize_dates($dates);
        if (!$dates) return false;
        scilab_ensure_request_dates_table($conn);
        $formID = (int) $formID;

        $del = $conn->prepare("DELETE FROM scilab_request_dates WHERE formID = ?");
        $del->bind_param("i", $formID);
        $ok = $del->execute();
        $del->close();

        $ins = $conn->prepare("INSERT INTO scilab_request_dates (formID, useDate) VALUES (?, ?)");
        foreach ($dates as $date) {
            $ins->bind_param("is", $formID, $date);
            $ok = $ins->execute() && $ok;
        }
        $ins->close();

        $upd = $conn->prepare("UPDATE scilab_form_requests SET inclusiveDate = ? WHERE id = ?");
        $upd->bind_param("si", $dates[0], $formID);
        $ok = $upd->execute() && $ok;
        $upd->close();

        return $ok;
    }
}

if (!function_exists('scilab_label_row')) {
    /**
     * Copy of a request row whose inclusiveDate is the readable label of all its dates.
     * For emails, PDFs and detail views that print inclusiveDate as-is.
     */
    function scilab_label_row($conn, $row)
    {
        if (is_array($row) && isset($row['id'])) {
            $row['inclusiveDate'] = scilab_request_dates_label($conn, $row['id'], $row['inclusiveDate'] ?? '');
        }
        return $row;
    }
}

// Pages load this file after db_connection.php, so $conn is already here: make sure the table exists
// before any query joins it.
if (isset($conn) && $conn instanceof mysqli) {
    scilab_ensure_request_dates_table($conn);
}
