<?php
session_start();
include 'config/connect.php';

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
        
        // Buat folder jika belum ada
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES["file"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        
        // Validasi tipe file (hanya gambar)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES["file"]["type"];
        
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
                // sukses upload
            } else {
                echo "<script>alert('Gagal mengupload file.');</script>";
            }
        } else {
            echo "<script>alert('Hanya file gambar yang diperbolehkan!');</script>";
        }
    }

    $query = "INSERT INTO files (user_id, filename, uploaded_at, title, content) VALUES (?, ?, NOW(), ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $user_id, $fileName, $title, $content);
    
    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        echo "<script>alert('Gagal menyimpan catatan.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Catatan - Diary Book</title>
    <link rel="stylesheet" href="css/style.css">
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

    <div class="container" style="max-width: 700px;">
        <form action="" method="POST" enctype="multipart/form-data">
            <h2>✍️ Tambah Catatan Baru</h2>
            
            <label>📌 Judul Catatan:</label>
            <input type="text" name="title" placeholder="Masukkan judul catatan..." required>

            <label>📝 Isi Catatan:</label>
            <textarea name="content" rows="8" placeholder="Tuliskan catatanmu di sini..." required></textarea>

            <label>📷 Upload Foto (Opsional):</label>
            <input type="file" name="file" accept="image/*">

            <div style="display: flex; gap: 15px; margin-top: 10px;">
                <button type="submit" style="flex: 1;">💾 Simpan Catatan</button>
                <a href="index.php" class="btn cancel" style="flex: 1; text-align: center; padding: 14px 0; text-decoration: none;">❌ Batal</a>
            </div>
        </form>
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

        const fileInput = document.querySelector('input[type="file"]');
        fileInput.addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) console.log('File selected:', fileName);
        });
    </script>
</body>
</html>
