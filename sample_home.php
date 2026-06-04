<?php
    include('../scilab/helperFiles/db_connection.php');
    include('helperFiles/session_handler.php');

    if (!isset($_SESSION['role'])) {
        header("Location: index.php");
        exit();
    }
    if ($_SESSION['role'] != 'admin') {
        header("Location: requester_home.php");
        exit();
    }

    $email = $_SESSION['email'];
    $username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Home</title>
        <?php include('helperFiles/headData.php'); ?>
        <link rel="stylesheet" href="css/laboratory-card.css">
    </head>
    
    <body>
        <?php include('helperFiles/header.php'); ?>
        <div class="main-wrapper">
            <div class="container-fluid text-center mt-4">
                <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
                <p>You are logged in as an admin.</p>
            </div>
        </div>
        <?php include('helperFiles/footer.php'); ?>
    </body>

    <script src="js/laboratory-card.js"></script>
</html>