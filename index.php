<?php
session_start();
include 'config/connect.php';

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diary Book - My Personal Diary</title>
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

    <!-- Background Music (Multiple Sources) -->
    <audio id="bgMusic" loop></audio>

    <!-- Header Navigation -->
    <div class="header-nav">
        <h2>✨ Selamat datang, <?php echo htmlspecialchars($_SESSION['username']); ?>! ✨</h2>
        <div class="nav-buttons">
            <a href="tambah.php" class="btn">+ Tambah Catatan</a>
            <a href="upload.php" class="btn">📁 Upload File</a>
            <a href="logout.php" class="btn logout">Logout</a>
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
                                <svg width="80" height="80" fill="rgba(255,255,255,0.3)" viewBox="0 0 24 24">
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($row['title'] ?? 'Tanpa Judul'); ?></h3>
                            <p><?php echo nl2br(htmlspecialchars($row['content'] ?? '')); ?></p>
                            <span class="date">📅 <?php echo date('d M Y, H:i', strtotime($row['uploaded_at'])); ?></span>
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
        // ====== PLAYLIST CONFIGURATION ======
        // Tambahkan lagu-lagu di sini!
        const playlist = [
            {
                name: "Lofi Chill",
                file: "assets/lofi-chill.mp3"
            },
            {
                name: "Peaceful Piano",
                file: "assets/peaceful-piano.mp3"
            },
            {
                name: "Night Ambience",
                file: "assets/night-ambience.mp3"
            },
            {
                name: "Relaxing Beats",
                file: "assets/relaxing-beats.mp3"
            },
            {
                name: "Study Music",
                file: "assets/study-music.mp3"
            }
        ];

        // Music Player Variables
        const bgMusic = document.getElementById('bgMusic');
        const musicPlayer = document.querySelector('.music-player');
        const musicText = document.querySelector('.music-text');
        const playlistSelector = document.getElementById('playlistSelector');
        const songList = document.getElementById('songList');
        let isPlaying = false;
        let currentSongIndex = 0;

        // Initialize Playlist
        function initPlaylist() {
            songList.innerHTML = '';
            playlist.forEach((song, index) => {
                const songItem = document.createElement('div');
                songItem.className = 'song-item';
                if (index === currentSongIndex) {
                    songItem.classList.add('active');
                }
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

        // Change Song
        function changeSong(index) {
            currentSongIndex = index;
            bgMusic.src = playlist[index].file;
            
            // Update active state
            document.querySelectorAll('.song-item').forEach((item, i) => {
                if (i === index) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });

            // Save current song
            localStorage.setItem('currentSong', index);

            // If music was playing, continue playing new song
            if (isPlaying) {
                bgMusic.play();
            }
        }

        // Toggle Music Play/Pause
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

        // Toggle Playlist Dropdown
        function togglePlaylist() {
            playlistSelector.classList.toggle('show');
        }

        // Close playlist when clicking outside
        document.addEventListener('click', (e) => {
            if (!playlistSelector.contains(e.target) && !e.target.closest('.playlist-toggle')) {
                playlistSelector.classList.remove('show');
            }
        });

        // Load saved state on page load
        window.addEventListener('load', () => {
            // Load saved song
            const savedSong = localStorage.getItem('currentSong');
            if (savedSong !== null) {
                currentSongIndex = parseInt(savedSong);
            }
            
            // Initialize playlist and set current song
            initPlaylist();
            bgMusic.src = playlist[currentSongIndex].file;

            // Load music playing state
            const musicState = localStorage.getItem('musicPlaying');
            if (musicState === 'true') {
                bgMusic.play().catch(e => console.log('Autoplay prevented'));
                isPlaying = true;
                musicPlayer.classList.add('playing');
                musicText.textContent = 'Music: ON';
            }
        });

        // Auto play next song when current ends
        bgMusic.addEventListener('ended', () => {
            currentSongIndex = (currentSongIndex + 1) % playlist.length;
            changeSong(currentSongIndex);
            if (isPlaying) {
                bgMusic.play();
            }
        });

        // Create shooting stars effect
        function createShootingStar() {
            const star = document.createElement('div');
            star.className = 'shooting-star';
            star.style.left = Math.random() * window.innerWidth + 'px';
            star.style.top = Math.random() * (window.innerHeight / 2) + 'px';
            document.body.appendChild(star);

            setTimeout(() => {
                star.remove();
            }, 3000);
        }

        // Create shooting star every 5-10 seconds
        setInterval(() => {
            if (Math.random() > 0.5) {
                createShootingStar();
            }
        }, 7000);

        // Add fade-in animation to cards
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    </script>
</body>
</html>