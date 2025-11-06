<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diary Book</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="stars"></div>
    <div class="stars-small"></div>
    <h2>Selamat datang, <?php echo $_SESSION['username']; ?>!</h2>
    <a href="logout.php">Logout</a>
    <br><br>
    <a href="upload.php">Upload File</a> 
</body>
</html>



