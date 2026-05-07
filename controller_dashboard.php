<?php
    include('../scilab/helperFiles/db_connection.php');
    include('helperFiles/session_handler.php');

    // Restrict access to only the admin.controller account
    if (!isset($_SESSION['email']) || $_SESSION['email'] !== 'admin.controller@local') {
        header("Location: index.php");
        exit();
    }

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
            $result = $conn->query("SELECT * FROM $table");
            $num_fields = $result->field_count;

            $return .= "DROP TABLE IF EXISTS `$table`;";
            $row2 = $conn->query("SHOW CREATE TABLE $table")->fetch_row();
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

        /* Dark Mode Styles */
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
            <div class="panel-heading" style="display:flex; justify-content:space-between; align-items:center;">
                <h3 class="panel-title" style="color: #2B55C4; font-weight:bold;"><i class="bi bi-clock-history"></i> Query History</h3>
                <form method="POST" style="margin:0;">
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
    </script>
</body>
</html>