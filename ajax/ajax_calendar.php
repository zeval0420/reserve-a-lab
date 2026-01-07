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
        SELECT 
            sfr.scilabName,
            sfr.inclusiveDate,
            sfr.inclusiveTime,
            sa.color
        FROM scilab_form_requests sfr
        JOIN scilab_availability sa
            ON sfr.scilabName = sa.scilabName
        WHERE sfr.statusScilabPersonnel = 'Approved'
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
        $color = $row['color'] ?? "#7f8c8d";

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
