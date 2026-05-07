<?php
// Centralized DB connection and session handler
include('../scilab/../helperFiles/db_connection.php');
include('../helperFiles/session_handler.php');

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

    $stmt = $conn->prepare("INSERT INTO scilab_inventory (classification, item, productID, description, quantity, unit, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssiss", $classification, $item, $productID, $description, $quantity, $unit, $status);

    if ($stmt->execute()) echo "success";
    else echo "error";

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

        if ($stmt->execute()) echo "success";
        else echo "error";

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

    // Check if there are any changes first
    $checkStmt = $conn->prepare("SELECT classification, item, productID, description, quantity, unit, status FROM scilab_inventory WHERE id = ?");
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
            $existing['status'] === $status
        ) {
            echo "no_changes";
            $checkStmt->close();
            $conn->close();
            exit;
        }
    }
    $checkStmt->close();

    // Proceed with update
    $stmt = $conn->prepare("UPDATE scilab_inventory SET classification = ?, item = ?, productID = ?, description = ?, quantity = ?, unit = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssssissi", $classification, $item, $productID, $description, $quantity, $unit, $status, $id);

    if ($stmt->execute()) echo "success";
    else echo "error";

    $stmt->close();
    $conn->close();
    exit;
}
?>
