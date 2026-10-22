<?php
// Centralized DB connection and session handler
include_once(__DIR__ . '/../../scilab/helperFiles/db_connection.php');
include_once(__DIR__ . '/../helperFiles/session_handler.php');
include_once(__DIR__ . '/../helperFiles/variableDeclarations.php');

// Inventory is managed by admins only; POSTs must carry a valid CSRF token
ral_require_role(['admin']);
ral_require_csrf();

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

// DEDUCT STOCK (single or batch; all-or-nothing, one reason for the whole batch)
// POST: action=deduct_inventory, reason, items[i][id], items[i][quantity]
// Response: JSON {status:'success', batch_id, count} or {status:'error', message}
if (isset($_POST['action']) && $_POST['action'] == 'deduct_inventory') {
    header('Content-Type: application/json');
    $fail = function ($msg) use ($conn) {
        echo json_encode(['status' => 'error', 'message' => $msg]);
        $conn->close();
        exit;
    };

    $reason = trim((string)($_POST['reason'] ?? ''));
    if ($reason === '') $fail('A reason is required.');
    if (strlen($reason) > 255) $fail('Reason must be 255 characters or fewer.');

    $rawItems = $_POST['items'] ?? [];
    if (!is_array($rawItems) || count($rawItems) === 0) $fail('Select at least one item.');
    if (count($rawItems) > 200) $fail('Too many items in one batch.');

    // Validate input and merge duplicate item ids
    $wanted = [];
    foreach ($rawItems as $r) {
        if (!is_array($r)) $fail('Invalid item data.');
        $iid = $r['id'] ?? '';
        $qty = $r['quantity'] ?? '';
        if (!is_scalar($iid) || !is_scalar($qty) || !preg_match('/^[1-9][0-9]*$/', (string)$iid) || !preg_match('/^[1-9][0-9]{0,8}$/', (string)$qty)) {
            $fail('Each item needs a valid selection and a whole-number quantity greater than 0.');
        }
        $iid = (int)$iid;
        $wanted[$iid] = ($wanted[$iid] ?? 0) + (int)$qty;
    }
    ksort($wanted); // consistent lock order

    $user = substr((string)($_SESSION['username'] ?? ($_SESSION['email'] ?? '')), 0, 100);
    $batchId = sprintf('%08x-%04x-4%03x-%04x-%012x', random_int(0, 0xffffffff), random_int(0, 0xffff), random_int(0, 0xfff), random_int(0x8000, 0xbfff), random_int(0, 0xffffffffffff));

    $conn->begin_transaction();
    try {
        $sel = $conn->prepare("SELECT item, description, classification, quantity FROM scilab_inventory WHERE id = ? AND classification != 'Removed' AND (status IS NULL OR status != 'Removed') FOR UPDATE");
        $upd = $conn->prepare("UPDATE scilab_inventory SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
        $log = $conn->prepare("INSERT INTO scilab_inventory_deductions (batch_id, inventory_id, item, description, classification, quantity_deducted, quantity_before, quantity_after, reason, deducted_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$sel || !$upd || !$log) throw new Exception('Database error. Has sql/inventory_deductions.sql been run?');

        foreach ($wanted as $iid => $qty) {
            $sel->bind_param("i", $iid);
            $sel->execute();
            $row = $sel->get_result()->fetch_assoc();
            if (!$row) throw new Exception('An item was not found or has been removed.');
            $before = (int)$row['quantity'];
            if ($qty > $before) {
                throw new Exception('Cannot deduct ' . $qty . ' from "' . $row['item'] . '": only ' . $before . ' in stock.');
            }
            $upd->bind_param("iii", $qty, $iid, $qty);
            $upd->execute();
            if ($upd->affected_rows !== 1) throw new Exception('Stock changed while deducting. Please try again.');
            $after = $before - $qty;
            $log->bind_param("sisssiiiss", $batchId, $iid, $row['item'], $row['description'], $row['classification'], $qty, $before, $after, $reason, $user);
            if (!$log->execute()) throw new Exception('Could not write the deduction log. Nothing was deducted.');
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        $msg = $e instanceof mysqli_sql_exception ? 'Database error. Nothing was deducted.' : $e->getMessage();
        $fail($msg);
    }

    foreach (array_keys($wanted) as $iid) {
        checkInventoryThresholdAndNotify($conn, $iid);
    }
    echo json_encode(['status' => 'success', 'batch_id' => $batchId, 'count' => count($wanted)]);
    $conn->close();
    exit;
}

// DEDUCTION HISTORY (latest 300)
if (isset($_POST['action']) && $_POST['action'] == 'list_deductions') {
    header('Content-Type: application/json');
    $res = $conn->query("SELECT batch_id, item, description, classification, quantity_deducted, quantity_before, quantity_after, reason, deducted_by, deducted_at FROM scilab_inventory_deductions ORDER BY deducted_at DESC, id DESC LIMIT 300");
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode(['status' => $res ? 'success' : 'error', 'rows' => $rows]);
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
