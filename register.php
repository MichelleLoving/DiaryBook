<?php
include 'config/connect.php';
session_start(); // Sebaiknya ditambahkan

$error_message = ""; // Variabel untuk menampilkan error di dalam box

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Cek jika input kosong
    if (empty($username) || empty($password)) {
        $error_message = "Username dan password tidak boleh kosong!";
    } else {
        // Cek dulu apakah username sudah ada
        $sql_check = "SELECT id FROM users WHERE username = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error_message = "Username sudah terpakai. Silakan pilih yang lain.";
        } else {
            // Jika aman, hash password (ini dari kode Anda, sudah benar)
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Masukkan pengguna baru ke database
            $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $username, $password_hash);

            if ($stmt->execute()) {
                // Jika berhasil, arahkan ke halaman login
                header("Location: login.php?status=registered");
                exit();
            } else {
                $error_message = "Error: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - DiaryBook</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="stars"></div>
    <div class="stars-small"></div>
    
    <div class="login-container">
        <div class="login-box">
            
            <form method="post" action="">
                <h2>Registrasi</h2>

                <?php if (!empty($error_message)): ?>
                    <p class="error"><?php echo $error_message; ?></p>
                <?php endif; ?>

                <div class="input-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="input-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit">Daftar</button>
                
                <p class="register-text">
                Sudah punya akun? <a href="login.php">Login di sini</a>
                </p>
            </form>

        </div>
    </div>
    
</body>
</html>