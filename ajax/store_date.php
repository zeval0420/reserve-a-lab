<?php
    include('../helperFiles/session_handler.php');
    require_once __DIR__ . '/../helperFiles/input.php';

    ral_require_login();
    ral_require_csrf();

    // Only a real calendar date is kept in the session
    $selectedDate = input_date('date');
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedDate !== '') {
        $_SESSION['selected_date'] = $selectedDate;
        echo "success";
    } else {
        echo "no date";
    }
?>