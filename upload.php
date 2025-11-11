<?php
session_start();
include 'config/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Proses upload file
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    $filename = basename($_FILES["file"]["name"]);
    $target_dir = "uploads/";
    $target_file = $target_dir . time() . '_' . $filename;

    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Validasi ukuran (maks 5MB)
    if ($_FILES["file"]["size"] > 5 * 1024 * 1024) {
        $message = "Ukuran file maksimal 5MB!";
        $messageType = "error";
    } else {
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            $sql = "INSERT INTO files (user_id, filename) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $user_id, basename($target_file));
            $stmt->execute();

            $message = "File berhasil diupload!";
            $messageType = "success";
        } else {
            $message = "Upload gagal!";
            $messageType = "error";
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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload File - DiaryBook</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            animation: slideDown 0.5s ease;
        }
        
        .alert.success {
            background: rgba(34, 197, 94, 0.2);
            border: 2px solid #22c55e;
            color: #86efac;
        }
        
        .alert.error {
            background: rgba(239, 68, 68, 0.2);
            border: 2px solid #ef4444;
            color: #fca5a5;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="stars"></div>
    <div class="stars-small"></div>

    <!-- Music Player dengan Playlist -->
    <div class="music-player" onclick="toggleMusic()">
        <svg class="music-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
        </svg>
        <span class="music-text">Music: OFF</span>
        <div class="playlist-toggle" onclick="event.stopPropagation(); togglePlaylist()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </div>
    </div>

    <!-- Playlist Selector -->
    <div class="playlist-selector" id="playlistSelector">
        <h4>🎵 Pilih Lagu</h4>
        <div id="songList"></div>
    </div>

    <audio id="bgMusic" loop></audio>

    <div class="header-nav">
        <h2>📁 Upload File</h2>
        <div class="nav-buttons">
            <a href="index.php" class="btn">🏠 Kembali ke Beranda</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" style="max-width: 600px; margin: 0 auto;">
            <h2>📤 Upload File Baru</h2>
            <input type="file" name="file" required>
            <button type="submit">🚀 Upload</button>
        </form>

        <div style="margin-top: 60px;">
            <h3 style="color: var(--moon-yellow); text-align: center; margin-bottom: 30px; font-size: 1.8rem;">
                📂 Daftar File Kamu
            </h3>

            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama File</th>
                            <th>Tanggal Upload</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($row = $result->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td>
                                <a href="uploads/<?= htmlspecialchars($row['filename']); ?>" target="_blank" style="display: flex; align-items: center; gap: 10px;">
                                    📄 <?= htmlspecialchars($row['filename']); ?>
                                </a>
                            </td>
                            <td><?= date('d M Y, H:i', strtotime($row['uploaded_at'])); ?></td>
                            <td>
                                <form method="post" action="delete_file.php" style="display:inline; margin: 0;">
                                    <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                    <button type="submit" onclick="return confirm('Yakin ingin menghapus file ini?')" style="width: auto; padding: 8px 16px; font-size: 0.9rem;">
                                        🗑️ Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px;">
                    <div style="font-size: 80px; margin-bottom: 20px;">📭</div>
                    <h3 style="color: var(--moon-yellow); font-size: 1.5rem; margin-bottom: 15px;">Belum Ada File</h3>
                    <p style="color: var(--text-light);">Mulai upload file pertamamu!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // ====== PLAYLIST CONFIGURATION ======
        const playlist = [
            { name: "Lofi Chill", file: "assets/lofi-chill.mp3" },
            { name: "Peaceful Piano", file: "assets/peaceful-piano.mp3" },
            { name: "Night Ambience", file: "assets/night-ambience.mp3" },
            { name: "Relaxing Beats", file: "assets/relaxing-beats.mp3" },
            { name: "Study Music", file: "assets/study-music.mp3" }
        ];

        const bgMusic = document.getElementById('bgMusic');
        const musicPlayer = document.querySelector('.music-player');
        const musicText = document.querySelector('.music-text');
        const playlistSelector = document.getElementById('playlistSelector');
        const songList = document.getElementById('songList');
        let isPlaying = false;
        let currentSongIndex = 0;

        function initPlaylist() {
            songList.innerHTML = '';
            playlist.forEach((song, index) => {
                const songItem = document.createElement('div');
                songItem.className = 'song-item';
                if (index === currentSongIndex) songItem.classList.add('active');
                songItem.innerHTML = `
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
                    </svg>
                    <span>${song.name}</span>
                `;
                songItem.onclick = () => changeSong(index);
                songList.appendChild(songItem);
            });
        }

        function changeSong(index) {
            currentSongIndex = index;
            bgMusic.src = playlist[index].file;
            document.querySelectorAll('.song-item').forEach((item, i) => {
                item.classList.toggle('active', i === index);
            });
            localStorage.setItem('currentSong', index);
            if (isPlaying) bgMusic.play();
        }

        function toggleMusic() {
            if (isPlaying) {
                bgMusic.pause();
                isPlaying = false;
                musicPlayer.classList.remove('playing');
                musicText.textContent = 'Music: OFF';
                localStorage.setItem('musicPlaying', 'false');
            } else {
                bgMusic.play();
                isPlaying = true;
                musicPlayer.classList.add('playing');
                musicText.textContent = 'Music: ON';
                localStorage.setItem('musicPlaying', 'true');
            }
        }

        function togglePlaylist() {
            playlistSelector.classList.toggle('show');
        }

        document.addEventListener('click', (e) => {
            if (!playlistSelector.contains(e.target) && !e.target.closest('.playlist-toggle')) {
                playlistSelector.classList.remove('show');
            }
        });

        window.addEventListener('load', () => {
            const savedSong = localStorage.getItem('currentSong');
            if (savedSong !== null) currentSongIndex = parseInt(savedSong);
            initPlaylist();
            bgMusic.src = playlist[currentSongIndex].file;
            const musicState = localStorage.getItem('musicPlaying');
            if (musicState === 'true') {
                bgMusic.play().catch(e => console.log('Autoplay prevented'));
                isPlaying = true;
                musicPlayer.classList.add('playing');
                musicText.textContent = 'Music: ON';
            }
        });

        bgMusic.addEventListener('ended', () => {
            currentSongIndex = (currentSongIndex + 1) % playlist.length;
            changeSong(currentSongIndex);
            if (isPlaying) bgMusic.play();
        });
    </script>
</body>
</html>
