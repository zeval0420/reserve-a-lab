<?php
// Start output buffering to prevent stray output/warnings from corrupting the PDF binary stream
ob_start();

require_once '../vendor/autoload.php';

try {
    include('db_connection.php');
} catch (Throwable $e) {
    while (ob_get_level()) { ob_end_clean(); }
    die("Database Connection Error: " . $e->getMessage());
}

use Dompdf\Dompdf;
use Dompdf\Options;

// Get dates
$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;
$classificationFilter = $_GET['classification'] ?? 'all';

if (!$startDate || !$endDate) {
    while (ob_get_level()) { ob_end_clean(); }
    die('Error: Start and end dates are required.');
}

if ($classificationFilter === 'all' || $classificationFilter === '') {
    $classifications = [];
} else {
    $classifications = explode(',', $classificationFilter);
}

$filterByClassification = !empty($classifications) && !in_array('all', $classifications);

// Fetch item usage data
$sql = "
    SELECT 
        COALESCE(si.classification, 'Uncategorized') as classification,
        mr.item, 
        mr.description,
        si.unit,
        mr.quantity,
        a.firstname,
        a.middlename,
        a.lastname,
        fr.inclusiveDate,
        fr.inclusiveTime
    FROM scilab_material_requests mr
    JOIN scilab_form_requests fr ON mr.formID = fr.id
    LEFT JOIN scilab_inventory si ON mr.item = si.item
    LEFT JOIN accounts a ON fr.requesterEmployeeID = a.employeeID
    WHERE fr.statusScilabPersonnel = 'Approved'
    AND fr.inclusiveDate BETWEEN ? AND ?
";

if ($filterByClassification) {
    $placeholders = implode(',', array_fill(0, count($classifications), '?'));
    $sql .= " AND COALESCE(si.classification, 'Uncategorized') IN ($placeholders) ";
}

$sql .= " ORDER BY COALESCE(si.classification, 'Uncategorized') ASC, mr.item ASC, fr.inclusiveDate ASC ";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    while (ob_get_level()) { ob_end_clean(); }
    die('Database query preparation failed.');
}

if ($filterByClassification) {
    $bindTypes = 'ss' . str_repeat('s', count($classifications));
    $bindParams = array_merge([$startDate, $endDate], $classifications);
    $refs = [];
    $refs[0] = $bindTypes;
    foreach ($bindParams as $key => $value) {
        $refs[$key + 1] = &$bindParams[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
} else {
    $stmt->bind_param('ss', $startDate, $endDate);
}

$stmt->execute();
$result = $stmt->get_result();

$categorizedItems = [];
while ($row = $result->fetch_assoc()) {
    $categorizedItems[$row['classification']][] = $row;
}

$stmt->close();
$conn->close();

// Prepare logo using base64 for reliable rendering in Dompdf without filesystem or URL path issues
// Only include <img> if PHP GD extension is installed, since Dompdf requires GD to process images
$logoPath = dirname(__DIR__) . "/img/logo.png";
$logoSrc = '';
if (extension_loaded('gd') && file_exists($logoPath)) {
    $logoData = base64_encode(file_get_contents($logoPath));
    $logoSrc = 'data:image/png;base64,' . $logoData;
}

$logoHtml = $logoSrc ? "<img src='{$logoSrc}' alt='Logo'>" : "";

// Build HTML
$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Item Usage Summary</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px; 
            color: #333; 
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 12px;
            border-bottom: 2px solid #003366;
        }

        .header img {
            width: 85px;
            height: 85px;
            margin-bottom: 8px;
        }

        .header h1 {
            margin: 0;
            font-size: 22px;
            color: #003366;
            letter-spacing: 0.5px;
        }

        .subheader {
            font-size: 13px;
            color: #555;
        }

        .date-range {
            text-align: center;
            margin: 15px 0 25px;
            font-size: 14px;
            font-weight: bold;
            color: #444;
        }

        h3 {
            margin-top: 35px;
            padding-left: 10px;
            font-size: 15px;
            color: #003366;
            border-left: 5px solid #003366;
            background: #f2f6fa;
            padding-top: 4px;
            padding-bottom: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 12px;
        }

        th {
            background: #e9eef5;
            border: 1px solid #c5c5c5;
            padding: 7px;
            font-weight: bold;
            color: #003366;
        }

        td {
            border: 1px solid #d1d1d1;
            padding: 7px;
        }

        tr:nth-child(even) td {
            background: #fafafa;
        }

        .no-data {
            text-align: center;
            padding: 25px;
            color: #777;
            font-size: 14px;
            margin-top: 20px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 5px;
            background: white;
        }

    </style>
</head>
<body>

    <div class='header'>
        {$logoHtml}
        <h1>SCIENCE LABORATORY USAGE SUMMARY</h1>
        <div class='subheader'>Philippine Science High School – Ilocos Region Campus</div>
        <div class='subheader'>San Ildefonso, Ilocos Sur</div>
    </div>

    <div class='date-range'>
        Date Range: " . htmlspecialchars($startDate) . " to " . htmlspecialchars($endDate) . "
    </div>
";

if (empty($categorizedItems)) {
    $html .= "<div class='no-data'>No items were used during this period.</div>";
} else {
    foreach ($categorizedItems as $classification => $items) {
        $html .= "<h3>" . htmlspecialchars($classification) . "</h3>";
        
        $html .= "
        <table>
            <tr>
                <th>Item</th>
                <th>Description</th>
                <th>Quantity Used</th>
                <th>Requestor</th>
                <th>Date of Use</th>
            </tr>
        ";

        foreach ($items as $item) {
            $description = $item['description'] ? htmlspecialchars($item['description']) : 'N/A';
            $unit = $item['unit'] ? ' ' . htmlspecialchars($item['unit']) : '';
            $requestorName = trim($item['firstname'] . ' ' . $item['middlename'] . ' ' . $item['lastname']);

            $html .= "
            <tr>
                <td>" . htmlspecialchars($item['item']) . "</td>
                <td>" . $description . "</td>
                <td>" . $item['quantity'] . $unit . "</td>
                <td>" . htmlspecialchars($requestorName) . "</td>
                <td>" . htmlspecialchars($item['inclusiveDate'] . ' (' . $item['inclusiveTime'] . ')') . "</td>
            </tr>
            ";
        }

        $html .= "</table>";
    }
}

$html .= "
    <div class='footer'>
        Generated by the SciLab Inventory System
    </div>
</body>
</html>
";

// Generate PDF
try {
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('chroot', dirname(__DIR__));
    $options->set('tempDir', sys_get_temp_dir());

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Clean output buffer completely before streaming PDF headers
    while (ob_get_level()) {
        ob_end_clean();
    }

    $dompdf->stream("SciLab_Summary_{$startDate}_to_{$endDate}.pdf", ["Attachment" => false]);
    exit();
} catch (Throwable $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    die("PDF Generation Error: " . $e->getMessage());
}
?>
