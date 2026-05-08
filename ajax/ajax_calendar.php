<?php
include('../../scilab/helperFiles/db_connection.php');
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
            sfr.id,
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
            "id" => $row['id'],
            "title" => $row['scilabName'],
            "start" => $startDateTime,
            "end"   => $endDateTime,
            "backgroundColor" => $color
        ];
    }

    echo json_encode($events);
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'get_request_details') {
    $id = $_POST['id'];
    $stmt = $conn->prepare("
        SELECT r.*, CONCAT(a.firstname, ' ', a.lastname) as requesterName 
        FROM scilab_form_requests r
        LEFT JOIN accounts a ON r.requesterEmployeeID = a.employeeID
        WHERE r.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Not found']);
    }
    exit;
}
?>
