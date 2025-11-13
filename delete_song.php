<?php
session_start();
include 'config/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['file'])) {
    $song_dir = "assets/songs/";
    
    // Keamanan: Gunakan basename() untuk mencegah directory traversal
    $filename = basename($_GET['file']);
    $filepath = $song_dir . $filename;

    // Pastikan file ada di dalam folder yang benar dan file itu ada
    if (file_exists($filepath) && strpos(realpath($filepath), realpath($song_dir)) === 0) {
        if (@unlink($filepath)) {
            // Sukses dihapus
        } else {
            // Gagal dihapus (mungkin masalah permission)
            echo "<script>alert('Gagal menghapus file. Periksa permission folder.');</script>";
        }
    } else {
        // File tidak ditemukan atau upaya traversal
        echo "<script>alert('File tidak ditemukan.');</script>";
    }
}

// Kembali ke halaman kelola lagu
header("Location: manage_songs.php");
exit();
?>