<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data file + catatan
$query = "SELECT * FROM files WHERE user_id = ? ORDER BY uploaded_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Diary Book</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h2>Selamat datang, <?php echo $_SESSION['username']; ?>!</h2>
    <a href="tambah.php" class="btn">+ Tambah Catatan</a>
    <a href="logout.php" class="btn logout">Logout</a>

    <div class="container">
        <?php while ($row = $result->fetch_assoc()) : ?>
            <div class="card">
                <?php if (!empty($row['filename'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($row['filename']); ?>" alt="Foto Catatan">
                <?php endif; ?>
                <div class="card-body">
                    <h3><?php echo htmlspecialchars($row['title'] ?? 'Tanpa Judul'); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars($row['content'] ?? '')); ?></p>
                    <span class="date"><?php echo $row['uploaded_at']; ?></span>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>
