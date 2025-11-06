<?php
session_start();
include 'config/connect.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Proses upload file
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    $filename = basename($_FILES["file"]["name"]);
    $target_dir = "uploads/";
    $target_file = $target_dir . $filename;

    // Pastikan folder uploads ada
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Validasi ukuran (maks 5MB)
    if ($_FILES["file"]["size"] > 5 * 1024 * 1024) {
        echo "<p style='color:red;'>Ukuran file maksimal 5MB!</p>";
    } else {
        // Pindahkan file
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            // Simpan ke database
            $sql = "INSERT INTO files (user_id, filename) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $user_id, $filename);
            $stmt->execute();

            echo "<p style='color:green;'>File berhasil diupload!</p>";
        } else {
            echo "<p style='color:red;'>Upload gagal!</p>";
        }
    }
}

// Ambil semua file milik user
$sql = "SELECT * FROM files WHERE user_id = ? ORDER BY uploaded_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Upload File - DiaryBook</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="stars"></div>
    <div class="stars-small"></div>
    <h2>Upload File</h2>

    <form method="post" enctype="multipart/form-data">
        <input type="file" name="file" required>
        <button type="submit">Upload</button>
    </form>

    <hr>

    <h3>Daftar File Kamu</h3>

    <?php if ($result->num_rows > 0): ?>
        <table border="1" cellpadding="8" cellspacing="0">
            <tr>
                <th>No</th>
                <th>Nama File</th>
                <th>Tanggal Upload</th>
                <th>Aksi</th>
            </tr>
            <?php 
            $no = 1;
            while ($row = $result->fetch_assoc()): 
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><a href="uploads/<?= htmlspecialchars($row['filename']); ?>" target="_blank">
                    <?= htmlspecialchars($row['filename']); ?>
                </a></td>
                <td><?= $row['uploaded_at']; ?></td>
                <td>
                    <form method="post" action="delete_file.php" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $row['id']; ?>">
                        <button type="submit" onclick="return confirm('Yakin ingin menghapus file ini?')">Hapus</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>Belum ada file yang kamu upload.</p>
    <?php endif; ?>

    <br>
    <a href="index.php">Kembali ke Beranda</a>
</body>
</html>
