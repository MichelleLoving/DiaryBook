<?php
session_start();
include 'config/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT id, title, content, filename FROM files WHERE id=? AND user_id=?");
if (!$stmt) { die("Database error: " . $conn->error); }
$stmt->bind_param("ii", $editId, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$note = $res->fetch_assoc();

if (!$note) {
    header("Location: index.php");
    exit();
}

$titleVal = $note['title'] ?? '';
$contentVal = $note['content'] ?? '';
$currentFilename = $note['filename'] ?? null;
$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $newFilename = null;
    $newOriginalFilename = null;

    if (!empty($_FILES["file"]["name"])) {
        $targetDir = "uploads/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

        $newOriginalFilename = basename($_FILES["file"]["name"]);
        $newFilename = time() . '_' . $newOriginalFilename;
        $targetFilePath = $targetDir . $newFilename;

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES["file"]["type"];

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
                if (!empty($currentFilename)) {
                    $oldPath = $targetDir . $currentFilename;
                    if (file_exists($oldPath)) @unlink($oldPath);
                }
            } else {
                $message = "Gagal mengupload file baru."; $messageType = "error";
            }
        } else {
            $message = "Hanya file gambar yang diperbolehkan!"; $messageType = "error";
        }
    }

    if ($messageType !== "error") {
        if ($newFilename) {
            $up = $conn->prepare("UPDATE files SET title=?, content=?, filename=?, original_filename=? WHERE id=? AND user_id=?");
            $up->bind_param("ssssii", $title, $content, $newFilename, $newOriginalFilename, $editId, $user_id);
        } else {
            $up = $conn->prepare("UPDATE files SET title=?, content=? WHERE id=? AND user_id=?");
            $up->bind_param("ssii", $title, $content, $editId, $user_id);
        }

        if (!$up) {
            $message = "Gagal memperbarui catatan: " . $conn->error; $messageType = "error";
        } else {
            if ($up->execute()) {
                header("Location: index.php"); exit();
            } else {
                $message = "Gagal memperbarui catatan."; $messageType = "error";
            }
        }
    }

    $titleVal = $title;
    $contentVal = $content;
    if ($newFilename && $messageType === "error") {
        $currentFilename = $newFilename;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Catatan - Diary Book</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="stars"></div>
    <div class="stars-small"></div>

    <div class="header-nav">
        <h2>✏️ Edit Catatan</h2>
        <div class="nav-buttons">
            <a href="index.php" class="btn btn-icon btn-home" title="Kembali ke Beranda">🏠</a>
        </div>
    </div>

    <div class="container" style="max-width: 700px;">
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $messageType === 'error' ? 'error' : 'success'; ?>" style="padding: 15px; border-radius: 12px; margin-bottom: 20px; background: <?php echo $messageType === 'error' ? 'rgba(239, 68, 68, 0.2)' : 'rgba(34, 197, 94, 0.2)'; ?>; border: 2px solid <?php echo $messageType === 'error' ? '#ef4444' : '#22c55e'; ?>; color: <?php echo $messageType === 'error' ? '#fca5a5' : '#86efac'; ?>;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label>📌 Judul Catatan:</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($titleVal); ?>" required>
            <label>📝 Isi Catatan:</label>
            <textarea name="content" rows="8" required><?php echo htmlspecialchars($contentVal); ?></textarea>
            <label>📷 Ganti Foto (Opsional):</label>
            <input type="file" name="file" accept="image/*">
            <?php if (!empty($currentFilename)): ?>
                <div style="margin-top:10px;">
                    <img src="uploads/<?php echo htmlspecialchars($currentFilename); ?>" alt="Foto Saat Ini" style="max-width:100%; border-radius:12px;">
                    <div style="margin-top:6px; color: var(--text-light); font-size:0.9rem;">Biarkan kosong jika tidak ingin mengganti foto.</div>
                </div>
            <?php endif; ?>

            <div style="display: flex; gap: 15px; margin-top: 10px;">
                <button type="submit" style="flex: 1;">💾 Perbarui Catatan</button>
            </div>
        </form>
    </div>
    
    </body>
</html>