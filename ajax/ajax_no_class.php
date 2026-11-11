<?php
    // Admin-only: add / remove no-class days
    include('../../credentials/db_connection.php');
    include('../helperFiles/session_handler.php');
    require_once __DIR__ . '/../helperFiles/request_dates.php';
    require_once __DIR__ . '/../helperFiles/holidays.php';

    ral_require_role(['admin']);
    ral_require_csrf();
    header('Content-Type: application/json');

    ral_ensure_no_class_table($conn);
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $dates = scilab_normalize_dates($_POST['dates'] ?? '');
        $reason = mb_substr(trim((string) ($_POST['reason'] ?? '')), 0, 100);
        if (!$dates) { echo json_encode(['status' => 'error', 'message' => 'Pick at least one date.']); exit(); }
        if (count($dates) > 366) { echo json_encode(['status' => 'error', 'message' => 'Too many dates at once.']); exit(); }
        $by = (string) ($_SESSION['username'] ?? '');
        $stmt = $conn->prepare("INSERT INTO scilab_no_class_days (noClassDate, reason, createdBy) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE reason = VALUES(reason), createdBy = VALUES(createdBy)");
        foreach ($dates as $d) { $stmt->bind_param('sss', $d, $reason, $by); $stmt->execute(); }
        $stmt->close();
        echo json_encode(['status' => 'success', 'days' => ral_no_class_days($conn)]);
    } elseif ($action === 'remove') {
        $d = scilab_normalize_dates($_POST['date'] ?? '');
        if ($d) {
            $stmt = $conn->prepare("DELETE FROM scilab_no_class_days WHERE noClassDate = ?");
            $stmt->bind_param('s', $d[0]);
            $stmt->execute();
            $stmt->close();
        }
        echo json_encode(['status' => 'success', 'days' => ral_no_class_days($conn)]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
    }
