<?php
session_start();
include 'config/connect.php';

if (!isset($_SESSION['user_id'])) {
    // Jika bukan dari web, jangan kirim header
    // header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// [PERUBAHAN] Menerima ID dari GET (link) bukan POST (form)
$file_id = $_GET['id'] ?? null;

if ($file_id) {
    // Ambil nama file
    $sql = "SELECT filename FROM files WHERE id=? AND user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $file_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (!empty($row['filename'])) {
             $filepath = "uploads/" . $row['filename'];

            // Hapus dari server jika file ada
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
        }

        // Hapus dari database
        // Ini akan menghapus seluruh data (catatan + file)
        $delete = $conn->prepare("DELETE FROM files WHERE id=? AND user_id=?");
        $delete->bind_param("ii", $file_id, $user_id);
        $delete->execute();
    }
}

// [PERUBAHAN] Kembali ke halaman upload.php
header("Location: upload.php");
exit();
?>