<?php
session_start();
include 'config/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$messageType = '';
$song_dir = "assets/songs/";

if (!file_exists($song_dir)) {
    mkdir($song_dir, 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["song"])) {
    if ($_FILES["song"]["error"] == 0) {
        $fileType = $_FILES["song"]["type"];
        $fileExtension = strtolower(pathinfo($_FILES["song"]["name"], PATHINFO_EXTENSION));

        if ($fileType == "audio/mpeg" || $fileExtension == "mp3") {
            $filename = basename($_FILES["song"]["name"]);
            $target_file = $song_dir . $filename;

            if (move_uploaded_file($_FILES["song"]["tmp_name"], $target_file)) {
                $message = "Lagu '" . htmlspecialchars($filename) . "' berhasil diupload!";
                $messageType = "success";
            } else {
                $message = "Gagal memindahkan file."; $messageType = "error";
            }
        } else {
            $message = "Hanya file MP3 yang diperbolehkan."; $messageType = "error";
        }
    } else {
        $message = "Terjadi error saat upload."; $messageType = "error";
    }
}

$song_files = array_diff(scandir($song_dir), ['.', '..']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lagu - DiaryBook</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .table-container { overflow-x: auto; }
        .alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 25px; text-align: center; font-weight: 600; }
        .alert.success { background: rgba(34, 197, 94, 0.2); border: 2px solid #22c55e; color: #86efac; }
        .alert.error { background: rgba(239, 68, 68, 0.2); border: 2px solid #ef4444; color: #fca5a5; }
        table { width: 100%; border-collapse: collapse; background: var(--card-bg); backdrop-filter: blur(15px); border-radius: 15px; overflow: hidden; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4); margin-top: 20px; }
        th { background: linear-gradient(135deg, var(--accent-purple), var(--accent-pink)); color: white; padding: 16px; text-align: left; font-weight: 600; }
        td { padding: 14px 16px; border-bottom: 1px solid var(--glass-border); color: var(--text-light); vertical-align: middle; }
        tr:hover { background: rgba(124, 58, 237, 0.1); }
        .btn-delete { width: auto; padding: 8px 16px; font-size: 0.9rem; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border: none; border-radius: 20px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 5px; cursor: pointer; }
        .btn-delete:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4); }
    </style>
</head>
<body>
    <div class="stars"></div>
    <div class="stars-small"></div>

    <div class="header-nav">
        <h2>🎵 Kelola Playlist Lagu</h2>
        <div class="nav-buttons">
            <a href="index.php" class="btn btn-icon btn-home" title="Kembali ke Beranda">🏠</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" style="max-width: 600px; margin: 0 auto;">
            <h2>📤 Upload Lagu Baru (MP3)</h2>
            <input type="file" name="song" required accept=".mp3,audio/mpeg">
            <button type="submit">🚀 Upload Lagu</button>
        </form>

        <div style="margin-top: 60px;">
            <h3 style="color: var(--moon-yellow); text-align: center; margin-bottom: 30px; font-size: 1.8rem;">
                📂 Daftar Lagu Saat Ini
            </h3>

            <?php 
            $mp3_files = [];
            foreach ($song_files as $file) {
                if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'mp3') {
                    $mp3_files[] = $file;
                }
            }
            
            if (count($mp3_files) > 0): 
            ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama File Lagu (MP3)</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($mp3_files as $file): 
                            ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td>
                                    <span style="display: flex; align-items: center; gap: 10px;">
                                        🎧 <?= htmlspecialchars($file); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="delete_song.php?file=<?= urlencode($file); ?>" class="btn-delete" onclick="return confirm('Yakin ingin menghapus lagu ini?')">
                                        🗑️ Hapus
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px;">
                    <div style="font-size: 80px; margin-bottom: 20px;">🎶</div>
                    <h3 style="color: var(--moon-yellow); font-size: 1.5rem; margin-bottom: 15px;">Belum Ada Lagu</h3>
                    <p style="color: var(--text-light);">Mulai upload lagu pertamamu!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    </body>
</html>