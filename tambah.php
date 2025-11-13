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
    $originalFilename = null;

    if (!empty($_FILES["file"]["name"])) {
        $targetDir = "uploads/";
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
        $originalFilename = basename($_FILES["file"]["name"]);
        $fileName = time() . '_' . $originalFilename;
        $targetFilePath = $targetDir . $fileName;
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES["file"]["type"];
        if (in_array($fileType, $allowedTypes)) {
            if (!move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
                echo "<script>alert('Gagal mengupload file.');</script>";
            }
        } else {
            echo "<script>alert('Hanya file gambar yang diperbolehkan!');</script>";
        }
    }

    $query = "INSERT INTO files (user_id, filename, original_filename, uploaded_at, title, content) VALUES (?, ?, ?, NOW(), ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issss", $user_id, $fileName, $originalFilename, $title, $content);
    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        echo "<script>alert('Gagal menyimpan catatan.');</script>";
    }
}

// Ambil daftar lagu (case-insensitive)
$song_dir = 'assets/songs/';
$playlist_json = [];
if (file_exists($song_dir)) {
    $song_files = array_diff(scandir($song_dir), ['.', '..']);
    foreach ($song_files as $file) {
        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'mp3') {
            $playlist_json[] = [
                'name' => pathinfo($file, PATHINFO_FILENAME),
                'file' => $song_dir . $file
            ];
        }
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

    <div class="advanced-controls" id="advancedControls">
        <button id="prevSong" class="control-btn">⏮️</button>
        <div class="volume-control">
            <span>🔉</span>
            <input type="range" id="volumeSlider" min="0" max="1" step="0.1" value="1">
        </div>
        <button id="nextSong" class="control-btn">⏭️</button>
        <button id="togglePlaylistBtn" class="control-btn">🎵</button>
    </div>

    <div class="music-player" id="musicPlayer">
        <svg class="music-icon icon-play" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"></path></svg>
        <svg class="music-icon icon-pause" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"></path></svg>
    </div>

    <div class="playlist-selector" id="playlistSelector">
        <h4>🎵 Pilih Lagu</h4>
        <div id="songList"></div>
    </div>
    <audio id="bgMusic" loop></audio>

    <div class="header-nav">
        <h2>✍️ Tambah Catatan Baru</h2>
        <div class="nav-buttons">
            <a href="index.php" class="btn btn-icon btn-home" title="Kembali ke Beranda">🏠</a>
        </div>
    </div>

    <div class="container" style="max-width: 700px;">
        <form action="" method="POST" enctype="multipart/form-data">
            <label>📌 Judul Catatan:</label>
            <input type="text" name="title" placeholder="Masukkan judul catatan..." required>
            <label>📝 Isi Catatan:</label>
            <textarea name="content" rows="8" placeholder="Tuliskan catatanmu di sini..." required></textarea>
            <label>📷 Upload Foto (Opsional):</label>
            <input type="file" name="file" accept="image/*">

            <div style="display: flex; gap: 15px; margin-top: 10px;">
                <button type="submit" style="flex: 1;">💾 Simpan Catatan</button>
            </div>
        </form>
    </div>

    <script>
        // (JavaScript SAMA PERSIS dengan index.php)
        const playlist = <?php echo json_encode($playlist_json); ?>;
        const bgMusic = document.getElementById('bgMusic');
        const musicPlayer = document.getElementById('musicPlayer');
        const playlistSelector = document.getElementById('playlistSelector');
        const songList = document.getElementById('songList');
        const advancedControls = document.getElementById('advancedControls');
        const prevSongBtn = document.getElementById('prevSong');
        const nextSongBtn = document.getElementById('nextSong');
        const volumeSlider = document.getElementById('volumeSlider');
        const togglePlaylistBtn = document.getElementById('togglePlaylistBtn');
        let isPlaying = false;
        let currentSongIndex = 0;
        function initPlaylist() {
            songList.innerHTML = '';
            if (playlist.length === 0) {
                songList.innerHTML = '<div style="padding: 10px; text-align: center; opacity: 0.7;">Belum ada lagu.</div>';
                return;
            }
            playlist.forEach((song, index) => {
                const songItem = document.createElement('div');
                songItem.className = 'song-item';
                if (index === currentSongIndex) songItem.classList.add('active');
                songItem.innerHTML = `<svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg><span>${song.name}</span>`;
                songItem.onclick = () => changeSong(index, true);
                songList.appendChild(songItem);
            });
        }
        function changeSong(index, playNow = true) {
            if (playlist.length === 0) return;
            currentSongIndex = index;
            bgMusic.src = playlist[index].file;
            document.querySelectorAll('.song-item').forEach((item, i) => item.classList.toggle('active', i === index));
            localStorage.setItem('currentSong', index);
            if (playNow) {
                bgMusic.play().catch(e => console.log('Autoplay dicegah'));
                if (!isPlaying) {
                    isPlaying = true;
                    musicPlayer.classList.add('playing');
                    localStorage.setItem('musicPlaying', 'true');
                }
            }
        }
        function playNext() {
            if (playlist.length === 0) return;
            currentSongIndex = (currentSongIndex + 1) % playlist.length;
            changeSong(currentSongIndex, isPlaying);
        }
        function playPrevious() {
            if (playlist.length === 0) return;
            currentSongIndex = (currentSongIndex - 1 + playlist.length) % playlist.length;
            changeSong(currentSongIndex, isPlaying);
        }
        function toggleMusic() {
            if (playlist.length === 0) { alert("Playlist kosong."); return; }
            if (isPlaying) {
                bgMusic.pause(); isPlaying = false;
                musicPlayer.classList.remove('playing');
                localStorage.setItem('musicPlaying', 'false');
            } else {
                bgMusic.play().catch(e => console.log('Autoplay dicegah'));
                isPlaying = true;
                musicPlayer.classList.add('playing');
                localStorage.setItem('musicPlaying', 'true');
            }
        }
        musicPlayer.addEventListener('click', toggleMusic);
        musicPlayer.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            advancedControls.classList.toggle('show');
            if (!advancedControls.classList.contains('show')) playlistSelector.classList.remove('show');
        });
        togglePlaylistBtn.addEventListener('click', (e) => { e.stopPropagation(); playlistSelector.classList.toggle('show'); });
        volumeSlider.addEventListener('input', (e) => { bgMusic.volume = e.target.value; localStorage.setItem('musicVolume', e.target.value); });
        prevSongBtn.addEventListener('click', (e) => { e.stopPropagation(); playPrevious(); });
        nextSongBtn.addEventListener('click', (e) => { e.stopPropagation(); playNext(); });
        document.addEventListener('click', (e) => {
            if (!playlistSelector.contains(e.target) && !e.target.closest('#togglePlaylistBtn')) playlistSelector.classList.remove('show');
            if (!advancedControls.contains(e.target) && !e.target.closest('.music-player')) advancedControls.classList.remove('show');
        });
        window.addEventListener('load', () => {
            initPlaylist();
            if (playlist.length > 0) {
                const savedSong = localStorage.getItem('currentSong');
                if (savedSong !== null && savedSong < playlist.length) currentSongIndex = parseInt(savedSong);
                else currentSongIndex = 0;
                bgMusic.src = playlist[currentSongIndex].file;
            }
            const savedVolume = localStorage.getItem('musicVolume');
            if (savedVolume !== null) { bgMusic.volume = savedVolume; volumeSlider.value = savedVolume; }
            const musicState = localStorage.getItem('musicPlaying');
            if (musicState === 'true' && playlist.length > 0) {
                bgMusic.play().catch(e => console.log('Autoplay dicegah'));
                isPlaying = true;
                musicPlayer.classList.add('playing');
            }
        });
        bgMusic.addEventListener('ended', () => { playNext(); });
    </script>
</body>
</html>
