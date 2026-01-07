<?php
include('../helperFiles/db_connection.php');
include('../helperFiles/session_handler.php');

$email = $_SESSION['email'] ?? null;
$username = $_SESSION['username'] ?? null;

if (!isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}



if (isset($_POST['action']) && $_POST['action'] === 'get_calendar_events') {

    $events = [];

    $stmt = $conn->prepare("
        SELECT scilabName, inclusiveDate, inclusiveTime 
        FROM scilab_form_requests 
        WHERE statusScilabPersonnel = 'Approved'
    ");

    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {

        // Format date: YYYY-MM-DD
        $date = $row['inclusiveDate'];

        // Format time: "9:47 AM to 11:47 AM"
        $timeRange = explode(" to ", $row['inclusiveTime']);
        $startTime = date("H:i:s", strtotime($timeRange[0]));
        $endTime   = date("H:i:s", strtotime($timeRange[1]));

        // Make full ISO timestamps
        $startDateTime = $date . "T" . $startTime;
        $endDateTime   = $date . "T" . $endTime;

        // Background color by lab
        switch ($row['scilabName']) {
            case "Science Laboratory 1":
                $color = "#e74c3c"; // red
                break;
            case "Science Laboratory 2":
                $color = "#3498db"; // blue
                break;
            case "Science Laboratory 3":
                $color = "#f1c40f"; // yellow
                break;
            case "Science Laboratory 4":
                $color = "#2ecc71"; // green
                break;
            default:
                $color = "#7f8c8d"; // gray fallback
        }

        $events[] = [
            "title" => $row['scilabName'],
            "start" => $startDateTime,
            "end"   => $endDateTime,
            "backgroundColor" => $color
        ];
    }

    echo json_encode($events);
    exit();
}

?>
