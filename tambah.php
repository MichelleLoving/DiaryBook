<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];
    $fileName = null;

    // Upload file
    if (!empty($_FILES["file"]["name"])) {
        $targetDir = "uploads/";
        $fileName = basename($_FILES["file"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
            // sukses upload
        } else {
            echo "Gagal mengupload file.";
        }
    }

    $query = "INSERT INTO files (user_id, filename, uploaded_at, title, content) VALUES (?, ?, NOW(), ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $user_id, $fileName, $title, $content);
    $stmt->execute();

    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Catatan</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h2>Tambah Catatan Baru</h2>
    <form action="" method="POST" enctype="multipart/form-data" class="form">
        <label>Judul:</label>
        <input type="text" name="title" required>

        <label>Isi Catatan:</label>
        <textarea name="content" rows="5" required></textarea>

        <label>Upload Foto:</label>
        <input type="file" name="file" accept="image/*">

        <button type="submit" class="btn">Simpan</button>
        <a href="index.php" class="btn cancel">Batal</a>
    </form>
</body>
</html>
