<?php
    include('../scilab/helperFiles/db_connection.php');
    include('helperFiles/session_handler.php');

    // Restrict access to only the admin.controller account
    if (!isset($_SESSION['email']) || $_SESSION['email'] !== 'admin.controller@local') {
        header("Location: index.php");
        exit();
    }

    // Every POST here (SQL console, clear history) must come from this page's own forms
    ral_require_csrf();

    // Same policy as the rest of the site, plus the CodeMirror editor files from cdnjs
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");

    if (isset($_GET['view_phpinfo']) && $_GET['view_phpinfo'] === 'true') {
        phpinfo();
        exit();
    }

    if (isset($_GET['backup_database']) && $_GET['backup_database'] === 'true') {
        $tables = [];
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }

        $return = "-- Database Backup\n-- Generated: " . date("Y-m-d H:i:s") . "\n\n";
        $return .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $result = $conn->query("SELECT * FROM `" . str_replace('`', '``', $table) . "`");
            $num_fields = $result->field_count;

            $return .= "DROP TABLE IF EXISTS `$table`;";
            $row2 = $conn->query("SHOW CREATE TABLE `" . str_replace('`', '``', $table) . "`")->fetch_row();
            $return .= "\n\n" . $row2[1] . ";\n\n";

            while ($row = $result->fetch_row()) {
                $return .= "INSERT INTO `$table` VALUES(";
                for ($j = 0; $j < $num_fields; $j++) {
                    if (isset($row[$j])) {
                        $return .= '"' . $conn->real_escape_string($row[$j]) . '"';
                    } else {
                        $return .= 'NULL';
                    }
                    if ($j < ($num_fields - 1)) {
                        $return .= ',';
                    }
                }
                $return .= ");\n";
            }
            $return .= "\n\n\n";
        }
        
        $return .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $backup_name = "db_backup_" . date("Y-m-d_H-i-s") . ".sql";
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . $backup_name . "\"");
        echo $return;
        exit;
    }

    // Initialize history if not set
    if (!isset($_SESSION['query_history'])) {
        $_SESSION['query_history'] = [];
    }

    // Handle Clear History
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_history'])) {
        $_SESSION['query_history'] = [];
        header("Location: controller_dashboard.php");
        exit();
    }

    $message = "";
    $messageType = "";
    $queryResult = null;

    // Tables for the Table Browser menu
    $browseTables = [];
    $tablesResult = $conn->query("SHOW TABLES");
    while ($row = $tablesResult->fetch_row()) {
        $browseTables[] = $row[0];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sql_query'])) {
        $sql = $_POST['sql_query'];
        
        try {
            // Check if it's a SELECT/SHOW/DESCRIBE query to fetch results
            if (stripos(trim($sql), 'SELECT') === 0 || stripos(trim($sql), 'SHOW') === 0 || stripos(trim($sql), 'DESCRIBE') === 0) {
                $result = $conn->query($sql);
                if ($result) {
                    $queryResult = $result->fetch_all(MYSQLI_ASSOC);
                    $message = "Query executed successfully. " . $result->num_rows . " rows returned.";
                    $messageType = "success";
                } else {
                    $message = "Error: " . $conn->error;
                    $messageType = "danger";
                }
            } else {
                // For INSERT, UPDATE, DELETE, etc.
                if ($conn->query($sql) === TRUE) {
                    $message = "Query executed successfully. Affected rows: " . $conn->affected_rows;
                    $messageType = "success";
                } else {
                    $message = "Error: " . $conn->error;
                    $messageType = "danger";
                }
            }
        } catch (Exception $e) {
            $message = "Exception: " . $e->getMessage();
            $messageType = "danger";
        }

        // Add to history
        array_unshift($_SESSION['query_history'], [
            'timestamp' => date('Y-m-d H:i:s'),
            'sql' => $sql,
            'status' => $messageType,
            'message' => $message
        ]);
        
        // Keep only last 50
        if (count($_SESSION['query_history']) > 50) {
            array_pop($_SESSION['query_history']);
        }
    }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Controller Dashboard</title>
    <?php include('helperFiles/headData.php'); ?>
    <!-- CodeMirror for SQL Syntax Highlighting -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/theme/dracula.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/sql/sql.min.js"></script>

    <style>
        .dashboard-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            padding: 30px;
            margin: 30px auto;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 1200px;
        }
        .CodeMirror {
            border: 1px solid rgba(43, 85, 196, 0.2);
            border-radius: 15px;
            height: 250px;
            font-size: 14px;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);
        }
        .table-container { overflow-x: auto; margin-top: 20px; border-radius: 10px; }
        .table th { background-color: #2B55C4; color: white; white-space: nowrap; }
        
        /* Panel styles for liquid look */
        .panel-default {
            border: none;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .panel-heading {
            background: transparent !important;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 15px 20px;
        }
        .panel-body {
            padding: 20px;
        }

        /* Table Browser */
        .tb-menu { max-height: 440px; overflow-y: auto; margin-top: 10px; border-radius: 10px; }
        .tb-menu .list-group-item { cursor: pointer; padding: 7px 12px; font-family: monospace; font-size: 13px; background: transparent; }
        .tb-menu .list-group-item.active { background: #2B55C4; border-color: #2B55C4; color: #fff; }
        /* 10 rows (36px) + header (~40px); anything beyond scrolls */
        .tb-scroll { max-height: 402px; overflow: auto; border: 1px solid rgba(0,0,0,0.1); border-radius: 10px; background: white; }
        .tb-scroll .table { margin-bottom: 0; }
        .tb-scroll thead th { position: sticky; top: 0; z-index: 2; }
        .tb-scroll td { height: 36px; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: middle !important; }
        .tb-scroll td.tb-actions { width: 1%; white-space: nowrap; overflow: visible; }
        .tb-null { color: #999; font-style: italic; }
        .tb-status { font-size: 13px; margin: 8px 2px 0; color: #666; }
        #tbEditFields .form-group label { font-family: monospace; }
        #tbEditFields .tb-type { color: #888; font-weight: normal; font-size: 11px; margin-left: 6px; }

        /* Dark Mode Styles */
        body.dark-mode .tb-scroll { background: #1e1e1e; border-color: rgba(255,255,255,0.1); }
        body.dark-mode .tb-menu .list-group-item { color: #e0e0e0; border-color: rgba(255,255,255,0.1); }
        body.dark-mode .tb-status { color: #aaa; }
        body.dark-mode {
            background: #121212 !important;
            color: #e0e0e0;
        }
        body.dark-mode .dashboard-container {
            background: rgba(30, 30, 30, 0.85);
            border-color: rgba(255, 255, 255, 0.1);
        }
        body.dark-mode .panel-default {
            background: rgba(255, 255, 255, 0.05);
        }
        body.dark-mode .panel-heading h3 {
            color: #8ab4f8 !important;
        }
        body.dark-mode .table {
            color: #e0e0e0;
            background: transparent !important;
        }
        body.dark-mode .table-bordered, 
        body.dark-mode .table-bordered th, 
        body.dark-mode .table-bordered td {
            border-color: rgba(255, 255, 255, 0.1);
        }
        body.dark-mode .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: rgba(255, 255, 255, 0.05);
        }
        body.dark-mode label {
            color: #8ab4f8 !important;
        }

        /* Dark Mode Switch */
        .dark-mode-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
            margin-right: 15px;
            vertical-align: middle;
        }
        .dark-mode-switch input { 
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(200, 200, 200, 0.5);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            transition: .4s;
            border-radius: 34px;
            box-shadow: inset 0 1px 4px rgba(0,0,0,0.1);
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 2;
        }
        input:checked + .slider {
            background: rgba(43, 85, 196, 0.6);
            border-color: rgba(43, 85, 196, 0.3);
        }
        input:checked + .slider:before {
            transform: translateX(30px);
        }
        .slider .icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            z-index: 1;
            transition: opacity 0.3s;
        }
        .slider .bi-sun-fill {
            left: 7px;
            color: #f39c12;
        }
        .slider .bi-moon-fill {
            right: 7px;
            color: #f1c40f;
        }

        .btn-liquid-success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.2));
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #28a745;
            padding: 5px 15px;
            border-radius: 20px;
            transition: all 0.3s;
            backdrop-filter: blur(5px);
            display: inline-block;
        }
        .btn-liquid-success:hover {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.2), rgba(40, 167, 69, 0.4));
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
            color: #1e7e34;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include('helperFiles/header.php'); ?>

    <div class="dashboard-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-primary" style="font-weight:bold; margin: 0;"><i class="bi bi-terminal-fill" style="margin-right: 10px;"></i> Controller Dashboard</h2>
            <div style="display:flex; align-items:center;">
                <label class="dark-mode-switch">
                    <input type="checkbox" id="darkModeToggle">
                    <span class="slider">
                        <i class="bi bi-sun-fill icon"></i>
                        <i class="bi bi-moon-fill icon"></i>
                    </span>
                </label>
                <a href="?backup_database=true" class="btn-liquid-success btn-sm" style="margin-right: 10px; text-decoration:none;"><i class="bi bi-database-down"></i> Backup DB</a>
                <a href="?view_phpinfo=true" target="_blank" class="btn-liquid-info btn-sm" style="margin-right: 10px; text-decoration:none;"><i class="bi bi-info-circle"></i> PHP Info</a>
                <span class="badge badge-danger p-2" style="border-radius: 10px;">Restricted Access Area</span>
            </div>
        </div>

        <div class="alert alert-warning" style="border-radius: 15px; backdrop-filter: blur(5px); margin-bottom: 25px;">
            <strong><i class="bi bi-exclamation-triangle"></i> Warning:</strong> 
            You have direct access to the database. Executing raw SQL queries can result in data loss. Please proceed with caution.
        </div>

        <form method="POST" onsubmit="return confirm('Are you sure you want to execute this query?');" style="margin-bottom: 25px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <div class="form-group">
                <label for="sql_query" class="font-weight-bold" style="color: #2B55C4;">SQL Query Execution</label>
                <textarea name="sql_query" id="sql_query" class="form-control" placeholder="SELECT * FROM accounts..."><?php echo isset($_POST['sql_query']) ? htmlspecialchars($_POST['sql_query']) : ''; ?></textarea>
            </div>
            <div class="text-right">
                <button type="button" class="btn-liquid-secondary mr-2" onclick="if(window.editor) window.editor.setValue('');">Clear</button>
                <button type="submit" class="btn-liquid font-weight-bold px-4">Execute Query</button>
            </div>
        </form>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?> mt-4 alert-dismissible fade show" style="border-radius: 15px;">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>

        <?php if ($queryResult !== null): ?>
            <div class="table-container">
                <?php if (count($queryResult) > 0): ?>
                    <table class="table table-bordered table-striped table-hover" style="background: white;">
                        <thead>
                            <tr>
                                <?php foreach (array_keys($queryResult[0]) as $col): ?>
                                    <th><?= htmlspecialchars($col) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($queryResult as $row): ?>
                                <tr>
                                    <?php foreach ($row as $val): ?>
                                        <td><?= htmlspecialchars($val ?? 'NULL') ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info" style="border-radius: 15px;">No results found.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="panel panel-default" style="margin-top: 30px;">
            <div class="panel-heading">
                <h3 class="panel-title" style="color: #2B55C4; font-weight:bold;"><i class="bi bi-table"></i> Table Browser</h3>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-3">
                        <input type="text" id="tbTableFilter" class="form-control" placeholder="Filter tables (<?= count($browseTables) ?>)" autocomplete="off">
                        <div class="list-group tb-menu" id="tbMenu">
                            <?php foreach ($browseTables as $t): ?>
                                <a class="list-group-item" data-table="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></a>
                            <?php endforeach; ?>
                            <div class="list-group-item text-muted" id="tbMenuEmpty" style="display:none;">No matching tables</div>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div id="tbPlaceholder" class="text-muted" style="padding: 40px 0; text-align:center;">Select a table to open it.</div>
                        <div id="tbView" style="display:none;">
                            <div class="row" style="margin-bottom: 10px;">
                                <div class="col-sm-4"><h4 id="tbTitle" style="margin: 6px 0; font-family: monospace; font-weight: bold;"></h4></div>
                                <div class="col-sm-4"><select id="tbColumn" class="form-control"></select></div>
                                <div class="col-sm-4"><input type="text" id="tbSearch" class="form-control" placeholder="Filter rows" autocomplete="off"></div>
                            </div>
                            <div class="tb-scroll" id="tbScroll">
                                <table class="table table-bordered table-striped table-hover">
                                    <thead></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <div class="tb-status" id="tbStatus"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default" style="margin-top: 30px;">
            <div class="panel-heading" style="display:flex; justify-content:space-between; align-items:center;">
                <h3 class="panel-title" style="color: #2B55C4; font-weight:bold;"><i class="bi bi-clock-history"></i> Query History</h3>
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="clear_history" value="true">
                    <button type="submit" class="btn-liquid-danger btn-sm" style="padding: 4px 10px; font-size: 12px;">Clear History</button>
                </form>
            </div>
            <div class="panel-body">
                <?php if (isset($_SESSION['query_history']) && !empty($_SESSION['query_history'])): ?>
                    <div class="table-responsive">
                        <table class="table table-condensed table-hover" style="font-size: 13px;">
                            <thead>
                                <tr>
                                    <th style="width: 160px; border-radius: 10px 0 0 0;">Time</th>
                                    <th>Query</th>
                                    <th style="border-radius: 0 10px 0 0;">Result</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['query_history'] as $h): ?>
                                    <tr class="<?= $h['status'] == 'success' ? 'success' : 'danger' ?>">
                                        <td><?= $h['timestamp'] ?></td>
                                        <td style="font-family: monospace;"><?= htmlspecialchars($h['sql']) ?></td>
                                        <td><?= htmlspecialchars($h['message']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No history available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="tbEditModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Edit row <small id="tbEditTitle" style="font-family: monospace;"></small></h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger" id="tbEditError" style="display:none;"></div>
                    <div id="tbEditFields"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-liquid-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-liquid" id="tbEditSave">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <?php include('helperFiles/footer.php'); ?>

    <script>
        const toggleCheckbox = document.getElementById('darkModeToggle');
        const body = document.body;
        
        if (localStorage.getItem('darkMode') === 'enabled') {
            body.classList.add('dark-mode');
            toggleCheckbox.checked = true;
        }

        toggleCheckbox.addEventListener('change', () => {
            if (toggleCheckbox.checked) {
                body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
            }
        });

        // Initialize CodeMirror
        window.editor = CodeMirror.fromTextArea(document.getElementById("sql_query"), {
            mode: "text/x-sql",
            theme: "dracula",
            lineNumbers: true,
            matchBrackets: true,
            indentWithTabs: true,
            smartIndent: true
        });

        // Table Browser: pick a table from the menu, filter its rows, scroll (10 rows visible), edit or delete a row
        (function () {
            var ENDPOINT = 'ajax/ajax_controller_tables.php';
            var state = { table: null, columns: [], pk: [], rows: [], total: 0, loading: false, seq: 0 };
            var $menu = $('#tbMenu'), $scroll = $('#tbScroll'), $table = $scroll.find('table');
            var editing = null; // { row, columns } while the edit modal is open

            function call(params) {
                var body = new URLSearchParams();
                Object.keys(params).forEach(function (k) { body.append(k, params[k]); });
                return fetch(ENDPOINT, { method: 'POST', body: body, credentials: 'same-origin' })
                    .then(function (r) { return r.json().catch(function () { return { success: false, message: 'Unexpected response (HTTP ' + r.status + ').' }; }); })
                    .then(function (j) { if (!j.success) throw new Error(j.message || 'Request failed.'); return j; });
            }

            function pkOf(row) {
                var pk = {};
                state.pk.forEach(function (n) { pk[n] = row[n]; });
                return JSON.stringify(pk);
            }

            function cell(v) {
                return v === null ? '<span class="tb-null">NULL</span>' : ralEsc(v);
            }

            function status() {
                var text = state.total === 0 ? 'No rows.' : 'Showing ' + state.rows.length + ' of ' + state.total + ' rows' + (state.rows.length < state.total ? ' (scroll for more)' : '');
                if (!state.pk.length && state.table) text += ' This table has no primary key, so rows are read-only.';
                $('#tbStatus').text(text);
            }

            function rowHtml(row, i) {
                var canAct = state.pk.length > 0;
                var tds = state.columns.map(function (c) {
                    var v = row[c.name];
                    return '<td title="' + (v === null ? 'NULL' : ralEsc(v)) + '">' + cell(v) + '</td>';
                }).join('');
                var disabled = canAct ? '' : ' disabled title="No primary key"';
                tds += '<td class="tb-actions">'
                    + '<button type="button" class="btn-liquid-info btn-sm tb-edit" data-i="' + i + '"' + disabled + '><i class="bi bi-pencil"></i> Edit</button> '
                    + '<button type="button" class="btn-liquid-danger btn-sm tb-delete" data-i="' + i + '"' + disabled + '><i class="bi bi-trash"></i> Delete</button>'
                    + '</td>';
                return '<tr data-i="' + i + '">' + tds + '</tr>';
            }

            // append: fetch the next page; otherwise start over, with `limit` rows (default one page)
            function load(append, limit) {
                var seq = ++state.seq;
                state.loading = true;
                if (!append) $('#tbStatus').text('Loading…');
                return call({
                    action: 'rows',
                    table: state.table,
                    offset: append ? state.rows.length : 0,
                    limit: limit || 50,
                    search: $('#tbSearch').val(),
                    column: $('#tbColumn').val()
                }).then(function (j) {
                    if (seq !== state.seq) return; // a newer request replaced this one
                    if (!append) {
                        state.columns = j.columns;
                        state.pk = j.pk;
                        state.rows = [];
                        $table.find('thead').html('<tr>' + j.columns.map(function (c) { return '<th title="' + ralEsc(c.type) + '">' + ralEsc(c.name) + (c.pk ? ' <i class="bi bi-key-fill"></i>' : '') + '</th>'; }).join('') + '<th>Actions</th></tr>');
                        $table.find('tbody').empty();
                        $scroll.scrollTop(0);
                    }
                    var start = state.rows.length;
                    state.rows = state.rows.concat(j.rows);
                    state.total = j.total;
                    $table.find('tbody').append(j.rows.map(function (r, k) { return rowHtml(r, start + k); }).join(''));
                    status();
                }).catch(function (e) {
                    if (seq === state.seq) $('#tbStatus').text('Error: ' + e.message);
                }).then(function () {
                    if (seq === state.seq) state.loading = false;
                });
            }

            // After an edit or delete: re-fetch everything already on screen, keeping the scroll position
            function reloadLoaded() {
                var top = $scroll.scrollTop();
                return load(false, Math.max(50, state.rows.length)).then(function () { $scroll.scrollTop(top); });
            }

            function openTable(name) {
                state.table = name;
                $menu.find('.list-group-item').removeClass('active').filter(function () { return $(this).data('table') === name; }).addClass('active');
                $('#tbPlaceholder').hide();
                $('#tbView').show();
                $('#tbTitle').text(name);
                $('#tbSearch').val('');
                $('#tbColumn').html('<option value="">All columns</option>');
                load(false).then(function () {
                    var opts = state.columns.filter(function (c) { return !c.binary; }).map(function (c) { return '<option value="' + ralEsc(c.name) + '">' + ralEsc(c.name) + '</option>'; });
                    $('#tbColumn').append(opts.join(''));
                });
            }

            // Menu: click opens a table, the box above it filters the list
            $menu.on('click', '[data-table]', function () { openTable($(this).data('table')); });
            $('#tbTableFilter').on('input', function () {
                var q = this.value.toLowerCase().trim(), shown = 0;
                $menu.find('[data-table]').each(function () {
                    var match = String($(this).data('table')).toLowerCase().indexOf(q) !== -1;
                    $(this).toggle(match);
                    if (match) shown++;
                });
                $('#tbMenuEmpty').toggle(shown === 0);
            });

            // Row filter (debounced) and column choice
            var searchTimer;
            $('#tbSearch').on('input', function () { clearTimeout(searchTimer); searchTimer = setTimeout(function () { load(false); }, 300); });
            $('#tbColumn').on('change', function () { if ($('#tbSearch').val() !== '') load(false); });

            // Next page when scrolled near the bottom
            $scroll.on('scroll', function () {
                if (!state.loading && state.rows.length < state.total && this.scrollTop + this.clientHeight >= this.scrollHeight - 80) load(true);
            });

            // Delete
            $table.on('click', '.tb-delete', function () {
                var i = +$(this).data('i'), row = state.rows[i];
                var label = state.pk.map(function (n) { return n + '=' + row[n]; }).join(', ');
                if (!confirm('Delete the row (' + label + ') from ' + state.table + '? This cannot be undone.')) return;
                var $btn = $(this).prop('disabled', true);
                call({ action: 'delete', table: state.table, pk: pkOf(row) }).then(reloadLoaded).catch(function (e) {
                    $btn.prop('disabled', false);
                    alert('Delete failed: ' + e.message);
                });
            });

            // Edit: load the full row, show a form, send back only the changed fields
            $table.on('click', '.tb-edit', function () {
                var i = +$(this).data('i'), row = state.rows[i], $btn = $(this).prop('disabled', true);
                call({ action: 'get', table: state.table, pk: pkOf(row) }).then(function (j) {
                    openEditor(i, j.row);
                }).catch(function (e) {
                    alert('Could not load the row: ' + e.message);
                }).then(function () { $btn.prop('disabled', false); });
            });

            function openEditor(i, full) {
                editing = { i: i, row: full, columns: state.columns };
                $('#tbEditTitle').text(state.table + ' — ' + state.pk.map(function (n) { return n + '=' + full[n]; }).join(', '));
                $('#tbEditError').hide();
                var html = state.columns.map(function (c, k) {
                    var locked = c.pk || c.binary || c.generated;
                    var v = full[c.name];
                    var note = c.pk ? 'primary key' : c.binary ? 'binary' : c.generated ? 'generated' : '';
                    var long = !locked && v !== null && (String(v).length > 80 || /\n/.test(v) || /text|json/i.test(c.type));
                    var field = long
                        ? '<textarea class="form-control tb-field" rows="4" data-k="' + k + '">' + ralEsc(v) + '</textarea>'
                        : '<input type="text" class="form-control tb-field" data-k="' + k + '" value="' + ralEsc(v) + '">';
                    if (locked) field = field.replace('class="form-control', 'readonly class="form-control');
                    var nullBox = (!locked && c.nullable)
                        ? '<label class="checkbox-inline" style="margin-top:4px;"><input type="checkbox" class="tb-null-box" data-k="' + k + '"' + (v === null ? ' checked' : '') + '> NULL</label>'
                        : '';
                    return '<div class="form-group"><label>' + ralEsc(c.name) + '<span class="tb-type">' + ralEsc(c.type) + (note ? ' · ' + note : '') + '</span></label>' + field + nullBox + '</div>';
                }).join('');
                $('#tbEditFields').html(html);
                $('#tbEditFields .tb-null-box').each(function () { syncNull($(this)); });
                $('#tbEditModal').modal('show');
            }

            function syncNull($box) {
                $('#tbEditFields .tb-field[data-k="' + $box.data('k') + '"]').prop('disabled', $box.prop('checked'));
            }
            $('#tbEditFields').on('change', '.tb-null-box', function () { syncNull($(this)); });

            $('#tbEditSave').on('click', function () {
                if (!editing) return;
                var changes = {}, any = false;
                editing.columns.forEach(function (c, k) {
                    if (c.pk || c.binary || c.generated) return;
                    var isNull = c.nullable && $('#tbEditFields .tb-null-box[data-k="' + k + '"]').prop('checked');
                    var val = $('#tbEditFields .tb-field[data-k="' + k + '"]').val();
                    var orig = editing.row[c.name];
                    if (isNull ? orig !== null : (orig === null || val !== orig)) {
                        changes[c.name] = isNull ? null : val;
                        any = true;
                    }
                });
                if (!any) { $('#tbEditModal').modal('hide'); return; }
                var $save = $(this).prop('disabled', true);
                call({ action: 'update', table: state.table, pk: pkOf(editing.row), values: JSON.stringify(changes) }).then(function () {
                    $('#tbEditModal').modal('hide');
                    return reloadLoaded();
                }).catch(function (e) {
                    $('#tbEditError').text(e.message).show();
                }).then(function () { $save.prop('disabled', false); });
            });
        })();
    </script>
</body>
</html>