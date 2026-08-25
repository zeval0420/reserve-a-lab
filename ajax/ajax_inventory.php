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
function checkInventoryThresholdAndNotify($conn, $itemId) {
    global $email_smtp_host, $email_smtp_user, $email_smtp_password, $email_smtp_secure, $email_smtp_port, $email_sender;

    $stmt = $conn->prepare("SELECT item, description, classification, quantity, unit, threshold_qty, threshold_notified FROM scilab_inventory WHERE id = ?");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        $stmt->close();
        return;
    }
    $item = $res->fetch_assoc();
    $stmt->close();

    $qty = intval($item['quantity']);
    $threshold = $item['threshold_qty'];
    $notified = intval($item['threshold_notified']);

    if ($threshold === null || $threshold === '') {
        // If threshold was cleared, make sure notified is reset to 0
        if ($notified === 1) {
            $updateNotified = $conn->prepare("UPDATE scilab_inventory SET threshold_notified = 0 WHERE id = ?");
            $updateNotified->bind_param("i", $itemId);
            $updateNotified->execute();
            $updateNotified->close();
        }
        return;
    }

    $threshold = intval($threshold);

    if ($qty <= $threshold) {
        if ($notified === 0) {
            // Find active admins to notify
            $admins = [];
            $adminStmt = $conn->prepare("SELECT email, firstname, lastname FROM accounts WHERE status = 'active' AND position IN ('Sci. Res. Assist.', 'Sci. Research Specialist I')");
            $adminStmt->execute();
            $adminRes = $adminStmt->get_result();
            while ($adminRow = $adminRes->fetch_assoc()) {
                $admins[] = $adminRow;
            }
            $adminStmt->close();

            if (empty($admins)) {
                return;
            }

            // Load HTML email template
            $templatePath = __DIR__ . '/../templates/threshold_alert_email.php';
            if (file_exists($templatePath)) {
                $bodyTemplate = file_get_contents($templatePath);
            } else {
                $bodyTemplate = "Low stock alert: [ITEM_NAME] is at [CURRENT_QTY] [UNIT] (Threshold: [THRESHOLD_QTY] [UNIT])";
            }

            $replacements = [
                '[ITEM_NAME]' => htmlspecialchars($item['item']),
                '[ITEM_DESC]' => htmlspecialchars($item['description'] ?? 'N/A'),
                '[CLASSIFICATION]' => htmlspecialchars($item['classification']),
                '[CURRENT_QTY]' => $qty,
                '[THRESHOLD_QTY]' => $threshold,
                '[UNIT]' => htmlspecialchars($item['unit'])
            ];

            foreach ($admins as $admin) {
                $adminName = trim($admin['firstname'] . ' ' . $admin['lastname']);
                $emailBody = str_replace('[ADMIN_NAME]', $adminName, $bodyTemplate);
                foreach ($replacements as $key => $val) {
                    $emailBody = str_replace($key, $val, $emailBody);
                }

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = $email_smtp_host; 
                    $mail->SMTPAuth = true;
                    $mail->Username = $email_smtp_user;
                    $mail->Password = $email_smtp_password;
                    $mail->SMTPSecure = $email_smtp_secure;
                    $mail->Port = $email_smtp_port;

                    $mail->setFrom($email_sender, 'PSHS-IRC SciLab');
                    $mail->addAddress($admin['email'], $adminName);

                    $mail->isHTML(true);
                    $mail->Subject = "LOW STOCK ALERT: " . $item['item'];
                    $mail->Body    = $emailBody;

                    $mail->send();
                } catch (Exception $e) {
                    error_log("Email sending failed to admin {$admin['email']}: {$mail->ErrorInfo}");
                }
            }

            // Mark item as notified
            $updateNotified = $conn->prepare("UPDATE scilab_inventory SET threshold_notified = 1 WHERE id = ?");
            $updateNotified->bind_param("i", $itemId);
            $updateNotified->execute();
            $updateNotified->close();
        }
    } else {
        // Quantity is above threshold, so reset threshold_notified
        if ($notified === 1) {
            $updateNotified = $conn->prepare("UPDATE scilab_inventory SET threshold_notified = 0 WHERE id = ?");
            $updateNotified->bind_param("i", $itemId);
            $updateNotified->execute();
            $updateNotified->close();
        }
    }
}

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
