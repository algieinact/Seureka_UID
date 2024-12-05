<?php
session_start();
require 'db.php'; // Koneksi database

// Periksa apakah user adalah admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil dan sanitasi input
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = trim($_POST['role']);

    // Validasi input
    if (!empty($username) && !empty($email) && !empty($password) && !empty($role)) {
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Persiapkan statement untuk mencegah SQL Injection
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            // Bind parameter ke statement
            $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);

            // Eksekusi statement
            if ($stmt->execute()) {
                // Redirect ke admin_dashboard dengan pesan sukses
                header('Location: admin_dashboard.php?success=User+added');
                exit;
            } else {
                // Tangani error eksekusi statement
                if ($conn->errno === 1062) { // Error kode 1062 untuk duplikasi entry
                    $error = "Username atau Email sudah digunakan.";
                } else {
                    $error = "Terjadi kesalahan saat menambahkan user: " . htmlspecialchars($conn->error);
                }
            }

            // Tutup statement
            $stmt->close();
        } else {
            // Tangani error prepare statement
            $error = "Terjadi kesalahan: " . htmlspecialchars($conn->error);
        }
    } else {
        $error = "Semua field wajib diisi.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
        <h1 class="text-black text-3xl font-bold mb-6">Add New User</h1>

        <!-- Pesan Error -->
        <?php if (!empty($error)): ?>
        <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form action="create_user.php" method="POST" class="space-y-4">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" id="username" name="username" placeholder="Username"
                    class="w-full border px-4 py-2 rounded-md" required
                    value="<?= isset($username) ? htmlspecialchars($username) : ''; ?>">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" placeholder="Email"
                    class="w-full border px-4 py-2 rounded-md" required
                    value="<?= isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" placeholder="Password"
                    class="w-full border px-4 py-2 rounded-md" required>
            </div>
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select id="role" name="role" class="w-full border px-4 py-2 rounded-md" required>
                    <option value="">-- Select Role --</option>
                    <option value="user" <?= (isset($role) && $role === 'user') ? 'selected' : ''; ?>>User</option>
                    <option value="admin" <?= (isset($role) && $role === 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            <button type="submit"
                class="w-full bg-green-500 text-white font-semibold py-2 rounded-md hover:bg-green-700">
                Create User
            </button>
        </form>
        <div class="mt-6">
            <a href="admin_dashboard.php"
                class="w-full bg-blue-500 text-white font-semibold py-2 rounded-md hover:bg-blue-700 text-center block">
                Back to Dashboard
            </a>
        </div>
    </div>
</body>

</html>