<?php
/**
 * Input validation and parameterised-query helpers.
 *
 * Every value that reaches SQL from a request, a session or a database row
 * should go through ral_query() (bound parameters) or one of the readers
 * below (typed, whitelisted). Never interpolate a variable into SQL text.
 */

/** Request-status values the approval and request pages can filter by. */
if (!defined('RAL_REQUEST_STATUSES')) {
    define('RAL_REQUEST_STATUSES', ['Pending', 'Approved', 'Rejected']);
}

if (!function_exists('ral_query')) {

    /**
     * Run a SELECT with bound parameters and return the mysqli_result, or
     * false on failure.
     *
     * @param string $types  mysqli bind types ("s", "i", "d"), one per placeholder
     * @param array  $params values for the placeholders, in order
     */
    function ral_query(mysqli $conn, string $sql, string $types = '', array $params = [])
    {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    /** A string from $source (default $_POST), trimmed and capped in length. */
    function input_str(string $key, string $default = '', int $maxLen = 255, ?array $source = null): string
    {
        $source = $source ?? $_POST;
        if (!isset($source[$key]) || !is_scalar($source[$key])) {
            return $default;
        }
        $value = trim((string)$source[$key]);
        return mb_substr($value, 0, $maxLen);
    }

    /** An integer from $source, or $default when missing or not numeric. */
    function input_int(string $key, int $default = 0, ?array $source = null): int
    {
        $source = $source ?? $_POST;
        if (!isset($source[$key]) || !is_scalar($source[$key])) {
            return $default;
        }
        $filtered = filter_var($source[$key], FILTER_VALIDATE_INT);
        return $filtered === false ? $default : $filtered;
    }

    /** A value that must be one of $allowed (strict), else $default. */
    function input_enum(string $key, array $allowed, string $default, ?array $source = null): string
    {
        $source = $source ?? $_POST;
        $value = isset($source[$key]) && is_scalar($source[$key]) ? (string)$source[$key] : null;
        return in_array($value, $allowed, true) ? $value : $default;
    }

    /** A real calendar date as "Y-m-d", or '' when missing or invalid. */
    function input_date(string $key, ?array $source = null): string
    {
        $source = $source ?? $_POST;
        if (!isset($source[$key]) || !is_scalar($source[$key])) {
            return '';
        }
        $raw = trim((string)$source[$key]);
        $dt = DateTime::createFromFormat('!Y-m-d', $raw);
        return ($dt && $dt->format('Y-m-d') === $raw) ? $raw : '';
    }

    /**
     * Date-range clause for the request lists, as [sql, types, params].
     * Manual mode needs two valid dates, otherwise it matches nothing.
     * Timeframe mode uses $days, which the caller takes from a fixed map.
     */
    function ral_request_date_filter(string $mode, string $from, string $to, int $days): array
    {
        if ($mode === 'manual') {
            if ($from === '' || $to === '') {
                return ['AND 1 = 0', '', []];
            }
            return [
                'AND dateRequested >= ? AND dateRequested < DATE_ADD(?, INTERVAL 1 DAY)',
                'ss',
                [$from . ' 00:00:00', $to],
            ];
        }
        return ['AND dateRequested >= DATE_SUB(NOW(), INTERVAL ? DAY)', 'i', [$days]];
    }

    /** "First Middle Last" for an employee ID or student LRN, or the ID itself if unknown. */
    function ral_display_name(mysqli $conn, string $id): string
    {
        $res = ral_query($conn, 'SELECT firstname, middlename, lastname FROM accounts WHERE employeeID = ?', 's', [$id]);
        $row = $res ? $res->fetch_assoc() : null;
        if (!$row) {
            $res = ral_query($conn, 'SELECT firstname, middlename, lastname FROM student WHERE LRN = ?', 's', [$id]);
            $row = $res ? $res->fetch_assoc() : null;
        }
        return $row ? $row['firstname'] . ' ' . $row['middlename'] . ' ' . $row['lastname'] : $id;
    }
}
