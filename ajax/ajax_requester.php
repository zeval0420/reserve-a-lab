<?php
include('../scilab/../helperFiles/db_connection.php');
include('../helperFiles/session_handler.php');

function parseTimeRangeMinutes($range) {
    if (!$range) return null;
    $parts = preg_split('/\s+to\s+/i', $range);
    if (!$parts || count($parts) !== 2) return null;

    $startRaw = trim($parts[0]);
    $endRaw = trim($parts[1]);

    $formats = ['g:i A', 'h:i A', 'H:i', 'g:iA', 'h:iA'];
    $start = null;
    $end = null;

    foreach ($formats as $fmt) {
        if (!$start) $start = DateTime::createFromFormat($fmt, $startRaw);
        if (!$end) $end = DateTime::createFromFormat($fmt, $endRaw);
    }

    if (!$start || !$end) return null;

    $startMinutes = intval($start->format('H')) * 60 + intval($start->format('i'));
    $endMinutes = intval($end->format('H')) * 60 + intval($end->format('i'));
    if ($endMinutes <= $startMinutes) return null;

    return [
        'start' => $startMinutes,
        'end' => $endMinutes,
        'label' => $start->format('g:i A') . ' - ' . $end->format('g:i A')
    ];
}

$email = $_SESSION['email'] ?? null;
$username = $_SESSION['username'] ?? null;

// Snapshot of all labs for a date (schedules, pending counts, conflicts)
if (isset($_POST['action']) && $_POST['action'] === 'get_day_snapshot') {
    header('Content-Type: application/json');
    $date = $_POST['date'] ?? null;

    if (!$date) {
        echo json_encode([
            'disabledLabs' => [],
            'pendingCounts' => [],
            'schedules' => [],
            'error' => 'Missing date parameter'
        ]);
        exit();
    }

    $syResult = $conn->query("SELECT MAX(value) AS currentSY FROM current WHERE description='School Year'");
    $currentSY = $syResult && $syResult->num_rows ? ($syResult->fetch_assoc()['currentSY'] ?? null) : null;

    if ($currentSY) {
        $stmt = $conn->prepare("SELECT id, scilabName, inclusiveTime, statusScilabPersonnel 
            FROM scilab_form_requests 
            WHERE inclusiveDate = ? AND sy = ?");
        $stmt->bind_param("ss", $date, $currentSY);
    } else {
        $stmt = $conn->prepare("SELECT id, scilabName, inclusiveTime, statusScilabPersonnel 
            FROM scilab_form_requests 
            WHERE inclusiveDate = ?");
        $stmt->bind_param("s", $date);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    $entriesByLab = [];
    $pendingCounts = [];
    $disabledLabs = [];

    while ($row = $res->fetch_assoc()) {
        $lab = $row['scilabName'];
        $statusRaw = strtolower(trim($row['statusScilabPersonnel']));
        $status = ucfirst($statusRaw);
        $parsed = parseTimeRangeMinutes($row['inclusiveTime']);
        if (!$parsed) continue;

        if (!isset($entriesByLab[$lab])) {
            $entriesByLab[$lab] = [];
        }

        $entriesByLab[$lab][] = [
            'status' => $status,
            'start' => $parsed['start'],
            'end' => $parsed['end'],
            'label' => $parsed['label'],
            'conflict' => false
        ];

        if ($statusRaw === 'pending') {
            $pendingCounts[$lab] = ($pendingCounts[$lab] ?? 0) + 1;
        }
        if ($statusRaw === 'approved') {
            $disabledLabs[] = $lab;
        }
    }

    foreach ($entriesByLab as $lab => &$entries) {
        usort($entries, function ($a, $b) {
            return $a['start'] <=> $b['start'];
        });

        $count = count($entries);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $overlap = max($entries[$i]['start'], $entries[$j]['start']) < min($entries[$i]['end'], $entries[$j]['end']);
                if ($overlap) {
                    $entries[$i]['conflict'] = true;
                    $entries[$j]['conflict'] = true;
                }
            }
        }

        $entries = array_map(function ($entry) {
            return [
                'status' => $entry['status'],
                'label' => $entry['label'],
                'conflict' => $entry['conflict']
            ];
        }, $entries);
    }
    unset($entries);

    echo json_encode([
        'disabledLabs' => array_values(array_unique($disabledLabs)),
        'pendingCounts' => $pendingCounts,
        'schedules' => $entriesByLab
    ]);
    exit();
}

// Requester notification feed
if (isset($_GET['action']) && $_GET['action'] === 'notification_feed') {
    header('Content-Type: application/json');
    $user = $_SESSION['email'] ?? null;

    if (!$user) {
        echo json_encode(['items' => []]);
        exit();
    }

    $stmt = $conn->prepare("SELECT id, scilabName, subject, statusScilabPersonnel, inclusiveDate, inclusiveTime, dateRequested 
        FROM scilab_form_requests 
        WHERE requester = ? 
        ORDER BY dateRequested DESC 
        LIMIT 15");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $res = $stmt->get_result();

    $seenIds = $_SESSION['seen_requests'] ?? [];
    $updatedSeen = $seenIds;
    $notifications = [];
    $unseenCount = 0;

    while ($row = $res->fetch_assoc()) {
        $status = ucfirst(strtolower($row['statusScilabPersonnel'] ?? 'Pending'));
        $isActionable = in_array(strtolower($status), ['approved', 'rejected']);
        $isSeen = in_array($row['id'], $seenIds);
        if ($isActionable && !$isSeen) {
            $unseenCount++;
            $updatedSeen[] = $row['id'];
        }

        $notifications[] = [
            'id' => (int)$row['id'],
            'lab' => $row['scilabName'],
            'subject' => $row['subject'] ?? 'N/A',
            'status' => $status,
            'date' => date('M d, Y', strtotime($row['inclusiveDate'])),
            'time' => $row['inclusiveTime'],
            'submitted' => date('M d, Y g:i A', strtotime($row['dateRequested']))
        ];
    }

    $_SESSION['seen_requests'] = array_values(array_unique($updatedSeen));

    echo json_encode([
        'unseen' => $unseenCount,
        'items' => $notifications
    ]);
    exit();
}

// Get disabled labs for a specific date
if (isset($_POST['action']) && $_POST['action'] === 'get_disabled_labs') {
    $date = $_POST['date'];
    $disabled = [];

    $stmt = $conn->prepare("SELECT scilabName FROM scilab_form_requests WHERE inclusiveDate = ? AND statusScilabPersonnel = 'approved'");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $disabled[] = $row['scilabName'];
    }

    echo json_encode($disabled);
    exit();
}

// Get images for a specific lab
if (isset($_POST['action']) && $_POST['action'] === 'get_lab_images' && isset($_POST['lab'])) {
    $lab = $_POST['lab'];
    $folder = '../img/labimages/' . $lab;
    $images = [];

    if (is_dir($folder)) {
        foreach (scandir($folder) as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $images[] = str_replace('../', '', $folder) . '/' . $file;
            }
        }
    }

    echo json_encode($images);
    exit();
}

// Get number of pending requests for a specific lab on a specific date
if (isset($_POST['action']) && $_POST['action'] === 'get_pending_count' && isset($_POST['lab'], $_POST['date'])) {
    $lab = $_POST['lab'];
    $date = $_POST['date'];

    // Get current school year
    $syResult = $conn->query("SELECT MAX(value) AS currentSY FROM current WHERE description='School Year'");
    $currentSY = $syResult->fetch_assoc()['currentSY'];

    $stmt = $conn->prepare("SELECT COUNT(*) AS pendingCount FROM scilab_form_requests WHERE scilabName = ? AND inclusiveDate = ? AND statusScilabPersonnel = 'Pending' AND sy = ?");
    $stmt->bind_param("sss", $lab, $date, $currentSY);
    $stmt->execute();
    $res = $stmt->get_result();
    $count = $res->fetch_assoc()['pendingCount'] ?? 0;

    echo json_encode($count);
    exit();
}

// Get unseen request updates
if (isset($_GET['action']) && $_GET['action'] === 'get_unseen_updates') {
    session_start();
    $user = $_SESSION['email'] ?? null;
    if (!$user) {
        echo 0;
        exit();
    }

    $syQuery = mysqli_query($conn, "SELECT value FROM current ORDER BY id DESC LIMIT 1");
    $currentSY = mysqli_fetch_assoc($syQuery)['value'];

    $result = mysqli_query($conn, "
        SELECT id 
        FROM scilab_form_requests 
        WHERE requester = '$user' 
        AND statusScilabPersonnel IN ('Approved', 'Rejected') 
        AND sy = '$currentSY'
    ");

    $allSeen = $_SESSION['seen_requests'] ?? [];
    $unseenCount = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        if (!in_array($row['id'], $allSeen)) {
            $unseenCount++;
        }
    }

    echo $unseenCount;
    exit();
}
?>
