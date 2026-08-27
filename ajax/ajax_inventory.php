<?php
// Centralized DB connection and session handler
include_once(__DIR__ . '/../../scilab/helperFiles/db_connection.php');
include_once(__DIR__ . '/../helperFiles/session_handler.php');
include_once(__DIR__ . '/../helperFiles/variableDeclarations.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

// HELPER FUNCTION: Check Threshold and Notify Admins
// Note: checkInventoryThresholdAndNotify() now lives in
// helperFiles/variableDeclarations.php so approval endpoints can reuse it
// after an automatic inventory deduction.

// REMOVE INVENTORY ITEM (mark as Removed and clear status)
if (isset($_POST['action']) && $_POST['action'] == 'delete_inventory') {
    $id = $_POST['id'];

    $stmt = $conn->prepare("UPDATE scilab_inventory SET classification = 'Removed', status = '' WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
    $conn->close();
    exit;
}

// ADD INVENTORY ITEM
if (isset($_POST['action']) && $_POST['action'] == 'add_inventory') {
    $classification = $_POST['classification'];
    $item = $_POST['item'];
    $productID = $_POST['productID'];
    $description = $_POST['description'];
    $quantity = intval($_POST['quantity']);
    $unit = $_POST['unit'];
    $status = $_POST['status'];
    $threshold_qty = (isset($_POST['threshold_qty']) && $_POST['threshold_qty'] !== '') ? intval($_POST['threshold_qty']) : null;

    $stmt = $conn->prepare("INSERT INTO scilab_inventory (classification, item, productID, description, quantity, unit, status, threshold_qty) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssissi", $classification, $item, $productID, $description, $quantity, $unit, $status, $threshold_qty);

    if ($stmt->execute()) {
        $insertedId = $conn->insert_id;
        checkInventoryThresholdAndNotify($conn, $insertedId);
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
    $conn->close();
    exit;
}

// STOCK IN INVENTORY ITEM
if (isset($_POST['action']) && $_POST['action'] == 'stock_in_inventory') {
    $id = $_POST['id'];
    $quantityToAdd = intval($_POST['quantity']);

    // Check if item exists and status is not Removed
    $checkStmt = $conn->prepare("SELECT quantity FROM scilab_inventory WHERE id = ? AND (status IS NULL OR status != 'Removed')");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        // Proceed with update
        $stmt = $conn->prepare("UPDATE scilab_inventory SET quantity = quantity + ? WHERE id = ?");
        $stmt->bind_param("ii", $quantityToAdd, $id);

        if ($stmt->execute()) {
            checkInventoryThresholdAndNotify($conn, $id);
            echo "success";
        } else {
            echo "error";
        }

        $stmt->close();
    } else {
        echo "not_found";
    }
    
    $checkStmt->close();
    $conn->close();
    exit;
}

// UPDATE INVENTORY ITEM
if (isset($_POST['action']) && $_POST['action'] == 'update_inventory') {
    $id = $_POST['id'];
    $classification = $_POST['classification'];
    $item = $_POST['item'];
    $productID = $_POST['productID'];
    $description = $_POST['description'];
    $quantity = intval($_POST['quantity']);
    $unit = $_POST['unit'];
    $status = $_POST['status'];
    $threshold_qty = (isset($_POST['threshold_qty']) && $_POST['threshold_qty'] !== '') ? intval($_POST['threshold_qty']) : null;

    // Check if there are any changes first
    $checkStmt = $conn->prepare("SELECT classification, item, productID, description, quantity, unit, status, threshold_qty FROM scilab_inventory WHERE id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        $existing = $result->fetch_assoc();

        if (
            $existing['classification'] === $classification &&
            $existing['item'] === $item &&
            $existing['productID'] === $productID &&
            $existing['description'] === $description &&
            intval($existing['quantity']) === $quantity &&
            $existing['unit'] === $unit &&
            $existing['status'] === $status &&
            ($existing['threshold_qty'] === null ? null : intval($existing['threshold_qty'])) === $threshold_qty
        ) {
            echo "no_changes";
            $checkStmt->close();
            $conn->close();
            exit;
        }
    }
    $checkStmt->close();

    // Proceed with update
    $stmt = $conn->prepare("UPDATE scilab_inventory SET classification = ?, item = ?, productID = ?, description = ?, quantity = ?, unit = ?, status = ?, threshold_qty = ? WHERE id = ?");
    $stmt->bind_param("ssssissii", $classification, $item, $productID, $description, $quantity, $unit, $status, $threshold_qty, $id);

    if ($stmt->execute()) {
        checkInventoryThresholdAndNotify($conn, $id);
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
    $conn->close();
    exit;
}

// BATCH UPDATE THRESHOLDS
if (isset($_POST['action']) && $_POST['action'] == 'update_thresholds') {
    $thresholds = isset($_POST['thresholds']) ? $_POST['thresholds'] : [];
    
    $success = true;
    foreach ($thresholds as $t) {
        $id = intval($t['id']);
        $threshold = ($t['threshold'] !== '') ? intval($t['threshold']) : null;
        
        $stmt = $conn->prepare("UPDATE scilab_inventory SET threshold_qty = ? WHERE id = ?");
        $stmt->bind_param("ii", $threshold, $id);
        if (!$stmt->execute()) {
            $success = false;
        }
        $stmt->close();
        
        checkInventoryThresholdAndNotify($conn, $id);
    }
    
    if ($success) {
        echo "success";
    } else {
        echo "error";
    }
    
    $conn->close();
    exit;
}
?>
