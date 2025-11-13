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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    // ... (kode PHP untuk upload gambar tetap sama) ...
    $originalFilename = basename($_FILES["file"]["name"]);
    $target_dir = "uploads/";
    $fileName = time() . '_' . $originalFilename;
    $target_file = $target_dir . $fileName;
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    if ($_FILES["file"]["size"] > 5 * 1024 * 1024) {
        $message = "Ukuran file maksimal 5MB!"; $messageType = "error";
    } else {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES["file"]["type"];
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                $sql = "INSERT INTO files (user_id, filename, original_filename) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $user_id, $fileName, $originalFilename);
                $stmt->execute();
                $message = "File berhasil diupload!"; $messageType = "success";
            } else { $message = "Upload gagal!"; $messageType = "error"; }
        } else { $message = "Hanya file gambar (JPG, PNG, GIF, WebP) yang diperbolehkan!"; $messageType = "error"; }
    }
}

$sql = "SELECT id, filename, original_filename, uploaded_at, title FROM files WHERE user_id = ? ORDER BY uploaded_at DESC";
$stmt = $conn->prepare($sql);
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
    <title>Upload File - DiaryBook</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; background: var(--card-bg); backdrop-filter: blur(15px); border-radius: 15px; overflow: hidden; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4); margin-top: 20px; }
        th { background: linear-gradient(135deg, var(--accent-purple), var(--accent-pink)); color: white; padding: 16px; text-align: left; font-weight: 600; }
        td { padding: 14px 16px; border-bottom: 1px solid var(--glass-border); color: var(--text-light); vertical-align: middle; }
        tr:hover { background: rgba(124, 58, 237, 0.1); }
        td a { color: var(--accent-purple); text-decoration: none; font-weight: 500; transition: color 0.3s ease; }
        td a:hover { color: var(--moon-yellow); }
        .btn-delete { width: auto; padding: 8px 16px; font-size: 0.9rem; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border: none; border-radius: 20px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 5px; cursor: pointer; }
        .btn-delete:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4); }
        .file-origin { font-size: 0.8rem; color: var(--text-light); opacity: 0.7; display: block; }
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
        <h2>📁 Upload File</h2>
        <div class="nav-buttons">
            <a href="index.php" class="btn btn-icon btn-home" title="Kembali ke Beranda">🏠</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert <?php echo $messageType; ?>" style="padding: 15px 20px; border-radius: 12px; margin-bottom: 25px; text-align: center; font-weight: 600; background: <?php echo $messageType === 'error' ? 'rgba(239, 68, 68, 0.2)' : 'rgba(34, 197, 94, 0.2)'; ?>; border: 2px solid <?php echo $messageType === 'error' ? '#ef4444' : '#22c55e'; ?>; color: <?php echo $messageType === 'error' ? '#fca5a5' : '#86efac'; ?>;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" style="max-width: 600px; margin: 0 auto;">
            <h2>📤 Upload File Baru (Hanya Gambar)</h2>
            <input type="file" name="file" required accept="image/*">
            <button type="submit">🚀 Upload</button>
        </form>

        <div style="margin-top: 60px;">
            <h3 style="color: var(--moon-yellow); text-align: center; margin-bottom: 30px; font-size: 1.8rem;">
                📂 Daftar File Gambar Kamu
            </h3>
            <?php if ($result->num_rows > 0): ?>
                <div class="table-container">
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
                                $displayName = $row['original_filename'] ?? $row['filename'];
                                if (empty($displayName)) $displayName = '(Nama file tidak ada)';
                            ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td>
                                    <a href="uploads/<?= htmlspecialchars($row['filename']); ?>" target="_blank" style="display: flex; align-items: center; gap: 10px;">
                                        📄 <?= htmlspecialchars($displayName); ?>
                                    </a>
                                    <?php if (!empty($row['title'])): ?>
                                        <span class="file-origin">Dari catatan: "<?php echo htmlspecialchars($row['title']); ?>"</span>
                                    <?php else: ?>
                                        <span class="file-origin">Dari menu upload file</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d M Y, H:i', strtotime($row['uploaded_at'])); ?></td>
                                <td>
                                    <a href="delete_file.php?id=<?= $row['id']; ?>" class="btn-delete" onclick="return confirm('Yakin ingin menghapus file ini?')">
                                        🗑️ Hapus
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
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