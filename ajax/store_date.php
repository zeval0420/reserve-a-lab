<?php
    session_start();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['date'])) {
        $_SESSION['selected_date'] = $_POST['date'];
        echo "success";
    } else {
        echo "no date";
    }
?>