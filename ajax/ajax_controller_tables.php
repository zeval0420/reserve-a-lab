<?php
// Table browser for controller_dashboard.php: list, load, edit and delete rows of any table in the database
include_once(__DIR__ . '/../../scilab/helperFiles/db_connection.php');
include_once(__DIR__ . '/../helperFiles/session_handler.php');

// Same gate as controller_dashboard.php; every request is a POST carrying the CSRF token
if (($_SESSION['email'] ?? '') !== 'admin.controller@local') {
    ral_deny(403, 'You do not have permission to do that.');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    ral_deny(405, 'POST required.');
}
ral_require_csrf();

const TB_PAGE_SIZE = 50;     // rows fetched per request; the page shows 10 and scrolls
const TB_MAX_PAGE = 500;
const TB_CELL_CHARS = 200;   // longer values are cut in the grid; the edit form loads the full value

function tb_quote($name)
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function tb_respond(array $data)
{
    header('Content-Type: application/json');
    echo json_encode(['success' => true] + $data, JSON_INVALID_UTF8_SUBSTITUTE);
    exit();
}

/** Every non-NULL value as a string, so the page compares what it sent with what it got (no int/float drift). */
function tb_strings(array $rows)
{
    return array_map(fn($r) => array_map(fn($v) => $v === null ? null : (string)$v, $r), $rows);
}

/** Prepare, bind every parameter as a string (MySQL converts) and execute. */
function tb_run($conn, $sql, array $params = [])
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }
    if ($params) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $stmt->execute();
    return $stmt;
}

/** SELECT list: binary columns become a size label, other values are cut to TB_CELL_CHARS when $truncate. */
function tb_select_list(array $cols, $truncate)
{
    $parts = [];
    foreach ($cols as $c) {
        $q = tb_quote($c['name']);
        if ($c['binary']) {
            $parts[] = "IF($q IS NULL, NULL, CONCAT('[binary ', LENGTH($q), ' bytes]')) AS $q";
        } elseif ($truncate && !$c['pk']) {
            $parts[] = "IF(CHAR_LENGTH($q) > " . TB_CELL_CHARS . ", CONCAT(LEFT($q, " . TB_CELL_CHARS . "), '…'), $q) AS $q";
        } else {
            $parts[] = $q;
        }
    }
    return implode(', ', $parts);
}

/** WHERE clause matching exactly one row by its primary key; $pkVals comes from the client. */
function tb_pk_where(array $pkCols, $pkVals, array &$params)
{
    if (!$pkCols) {
        ral_deny(400, 'This table has no primary key, so single rows cannot be edited or deleted.');
    }
    if (!is_array($pkVals)) {
        ral_deny(400, 'Missing row identifier.');
    }
    $conds = [];
    foreach ($pkCols as $name) {
        if (!array_key_exists($name, $pkVals) || !is_scalar($pkVals[$name])) {
            ral_deny(400, 'Missing row identifier.');
        }
        $conds[] = tb_quote($name) . ' = ?';
        $params[] = (string)$pkVals[$name];
    }
    return ' WHERE ' . implode(' AND ', $conds);
}

try {
    $tables = [];
    $res = $conn->query('SHOW TABLES');
    while ($row = $res->fetch_row()) {
        $tables[] = $row[0];
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'tables') {
        tb_respond(['tables' => $tables]);
    }
    $table = (string)($_POST['table'] ?? '');
    if (!in_array($table, $tables, true)) {
        ral_deny(404, 'Unknown table.');
    }
    $qt = tb_quote($table);

    $cols = [];
    $pkCols = [];
    $res = $conn->query("SHOW FULL COLUMNS FROM $qt");
    while ($c = $res->fetch_assoc()) {
        $cols[] = [
            'name'      => $c['Field'],
            'type'      => $c['Type'],
            'nullable'  => $c['Null'] === 'YES',
            'pk'        => $c['Key'] === 'PRI',
            'auto'      => stripos($c['Extra'], 'auto_increment') !== false,
            'binary'    => (bool)preg_match('/blob|binary|geometry|point|polygon|linestring/i', $c['Type']),
            'generated' => (bool)preg_match('/(VIRTUAL|STORED) GENERATED/i', $c['Extra']),
        ];
        if ($c['Key'] === 'PRI') {
            $pkCols[] = $c['Field'];
        }
    }

    if ($action === 'rows') {
        $offset = max(0, (int)($_POST['offset'] ?? 0));
        $search = trim((string)($_POST['search'] ?? ''));
        $searchCol = (string)($_POST['column'] ?? '');

        $where = '';
        $params = [];
        if ($search !== '') {
            $exprs = [];
            foreach ($cols as $c) {
                if (!$c['binary'] && ($searchCol === '' || $searchCol === $c['name'])) {
                    $exprs[] = 'CAST(' . tb_quote($c['name']) . ' AS CHAR)';
                }
            }
            if ($exprs) {
                $haystack = count($exprs) > 1 ? 'CONCAT_WS(CHAR(31 USING utf8mb4), ' . implode(', ', $exprs) . ')' : $exprs[0];
                $where = " WHERE $haystack LIKE ?";
                $params[] = '%' . addcslashes($search, '\\%_') . '%';
            }
        }

        $total = (int)tb_run($conn, "SELECT COUNT(*) FROM $qt$where", $params)->get_result()->fetch_row()[0];

        $order = $pkCols ? ' ORDER BY ' . implode(', ', array_map(fn($n) => tb_quote($n) . ' DESC', $pkCols)) : '';
        // A reload after an edit/delete asks for the whole range already on screen in one go
        $limit = min(TB_MAX_PAGE, max(1, (int)($_POST['limit'] ?? TB_PAGE_SIZE)));
        $sql = 'SELECT ' . tb_select_list($cols, true) . " FROM $qt$where$order LIMIT $limit OFFSET $offset";
        $rows = tb_run($conn, $sql, $params)->get_result()->fetch_all(MYSQLI_ASSOC);

        tb_respond(['columns' => $cols, 'pk' => $pkCols, 'rows' => tb_strings($rows), 'total' => $total, 'offset' => $offset]);
    }

    $pkVals = json_decode((string)($_POST['pk'] ?? ''), true);
    $params = [];
    $pkWhere = tb_pk_where($pkCols, $pkVals, $params);

    if ($action === 'get') {
        $rows = tb_run($conn, 'SELECT ' . tb_select_list($cols, false) . " FROM $qt$pkWhere LIMIT 1", $params)->get_result()->fetch_all(MYSQLI_ASSOC);
        if (!$rows) {
            ral_deny(404, 'Row not found. It may have been deleted.');
        }
        tb_respond(['row' => tb_strings($rows)[0]]);
    }

    if ($action === 'update') {
        $values = json_decode((string)($_POST['values'] ?? ''), true);
        if (!is_array($values) || !$values) {
            ral_deny(400, 'Nothing to update.');
        }
        $editable = [];
        foreach ($cols as $c) {
            if (!$c['pk'] && !$c['binary'] && !$c['generated']) {
                $editable[$c['name']] = $c;
            }
        }
        $sets = [];
        $setParams = [];
        foreach ($values as $name => $value) {
            if (!isset($editable[$name])) {
                ral_deny(400, "Column `$name` cannot be edited.");
            }
            if ($value === null) {
                if (!$editable[$name]['nullable']) {
                    ral_deny(400, "Column `$name` cannot be NULL.");
                }
                $sets[] = tb_quote($name) . ' = NULL';
            } else {
                $sets[] = tb_quote($name) . ' = ?';
                $setParams[] = (string)$value;
            }
        }
        tb_run($conn, "UPDATE $qt SET " . implode(', ', $sets) . "$pkWhere LIMIT 1", array_merge($setParams, $params));
        tb_respond(['message' => 'Row updated.']);
    }

    if ($action === 'delete') {
        $stmt = tb_run($conn, "DELETE FROM $qt$pkWhere LIMIT 1", $params);
        if ($stmt->affected_rows < 1) {
            ral_deny(404, 'Row not found. It may have been deleted already.');
        }
        tb_respond(['message' => 'Row deleted.']);
    }

    ral_deny(400, 'Unknown action.');
} catch (Throwable $e) {
    ral_deny(500, $e->getMessage());
}
