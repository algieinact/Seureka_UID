<?php
require 'db.php'; // Koneksi database

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validasi input
    if (empty($username) || empty($email) || empty($password)) {
        die('All fields are required.');
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Role selalu user untuk pendaftaran via form
    $role = 'user';

    // Variabel untuk menyimpan nama file foto profil
    $profilePicture = null;

    // Proses upload file
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        $fileName = time() . '_' . basename($_FILES['profile_picture']['name']); // Nama file unik
        $uploadFile = $uploadDir . $fileName;

        // Pastikan folder upload ada
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Pindahkan file ke folder tujuan
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadFile)) {
            $profilePicture = $fileName; // Simpan nama file untuk database
        } else {
            die('Failed to upload profile picture.');
        }
    }

    // Simpan ke database
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, profile_picture) VALUES (:username, :email, :password, :role, :profile_picture)");
    try {
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $hashedPassword,
            ':role' => $role,
            ':profile_picture' => $profilePicture
        ]);
        echo '<script>
                alert("Registration successful!");
                window.location.href = "login.html";
              </script>';
    } catch (PDOException $e) {
        // Tangani error jika username atau email sudah terdaftar
        if ($e->getCode() == 23000) { // Duplicate entry
            die('Username or Email already exists.');
        }
        die('Error: ' . $e->getMessage());
    }
}
?>