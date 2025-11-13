<?php
session_start();
include 'config/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data file + catatan
$query = "SELECT id, filename, uploaded_at, title, content FROM files WHERE user_id = ? ORDER BY uploaded_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

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
    <title>Diary Book - My Personal Diary</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .card-actions { display: flex; gap: 10px; margin-top: 20px; }
        .card-actions a { display: inline-block; padding: 9px 15px; border-radius: 20px; text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: all 0.3s ease; text-align: center; flex: 1; border: 2px solid transparent; }
        .card-actions .btn-edit { background: linear-gradient(135deg, var(--accent-purple), var(--accent-pink)); color: white; }
        .card-actions .btn-edit:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(124, 58, 237, 0.4); }
    </style>
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
        <h2>✨ Selamat datang, <?php echo htmlspecialchars($_SESSION['username']); ?>! ✨</h2>
        <div class="nav-buttons">
            <a href="tambah.php" class="btn btn-icon" title="Tambah Catatan Baru">✍️</a>
            <a href="upload.php" class="btn btn-icon" title="Upload File/Gambar">🔼</a>
            <a href="manage_songs.php" class="btn btn-icon" title="Kelola Lagu">🎶</a>
            <a href="logout.php" class="btn btn-icon logout" title="Logout">🚪</a>
        </div>
    </div>

    <div class="container">
        <?php if ($result->num_rows > 0): ?>
            <div class="cards">
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <div class="card">
                        <?php if (!empty($row['filename'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($row['filename']); ?>" alt="Foto Catatan">
                        <?php else: ?>
                            <div style="width: 100%; height: 240px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                <svg width="80" height="80" fill="rgba(255,255,255,0.3)" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <div class="card-body-content">
                                <h3><?php echo htmlspecialchars($row['title'] ?? 'Tanpa Judul'); ?></h3>
                                <p><?php echo nl2br(htmlspecialchars($row['content'] ?? '')); ?></p>
                                <span class="date">📅 <?php echo date('d M Y, H:i', strtotime($row['uploaded_at'])); ?></span>
                            </div>
                            <div class="card-actions">
                                <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn-edit">✏️ Edit Catatan</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 60px 20px; animation: fadeInUp 0.6s ease;">
                <div style="font-size: 80px; margin-bottom: 20px;">📔</div>
                <h3 style="color: var(--moon-yellow); font-size: 1.8rem; margin-bottom: 15px;">Belum Ada Catatan</h3>
                <p style="color: var(--text-light); margin-bottom: 30px;">Mulai menulis catatan pertamamu dan simpan momen berhargamu!</p>
                <a href="tambah.php" class="btn">✍️ Tulis Catatan Pertama</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // (JavaScript tidak berubah dari sebelumnya,
        // karena fungsi toggleMusic() sudah 
        // menambahkan/menghapus kelas '.playing')
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
                songList.innerHTML = '<div style="padding: 10px; text-align: center; opacity: 0.7;">Belum ada lagu. Silakan upload di menu Kelola Lagu.</div>';
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
            if (playlist.length === 0) {
                alert("Playlist kosong. Silakan upload lagu terlebih dahulu.");
                return;
            }
            if (isPlaying) {
                bgMusic.pause();
                isPlaying = false;
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
            if (!advancedControls.classList.contains('show')) {
                playlistSelector.classList.remove('show');
            }
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
        function createShootingStar() {
            const star = document.createElement('div');
            star.className = 'shooting-star';
            star.style.left = Math.random() * window.innerWidth + 'px';
            star.style.top = Math.random() * (window.innerHeight / 2) + 'px';
            document.body.appendChild(star);
            setTimeout(() => star.remove(), 3000);
        }
        setInterval(() => { if (Math.random() > 0.5) createShootingStar(); }, 7000);
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => { card.style.animationDelay = `${index * 0.1}s`; });
    </script>
</body>
</html>