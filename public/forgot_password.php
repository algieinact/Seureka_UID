<?php
require 'db.php'; // Koneksi database

$error = '';
$success = '';
$step = 1; // Langkah default adalah validasi username

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['step']) && $_POST['step'] == '1') {
        // Langkah 1: Validasi username
        $username = $_POST['username'];

        // Validasi input username
        if (empty($username)) {
            $error = 'Username is required.';
        } else {
            // Periksa apakah username ada di database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Username valid, lanjutkan ke langkah berikutnya
                $step = 2;
            } else {
                $error = 'Username not found.';
            }
        }
    } elseif (isset($_POST['step']) && $_POST['step'] == '2') {
        // Langkah 2: Proses reset password
        $username = $_POST['username'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        // Validasi input password
        if (empty($newPassword) || empty($confirmPassword)) {
            $error = 'Both password fields are required.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            // Hash password baru
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

            // Update password di database
            $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE username = :username");
            $stmt->execute([
                ':password' => $hashedPassword,
                ':username' => $username
            ]);

            // Set success message for pop-up
            echo "<script>
                    alert('Password has been reset successfully!');
                    window.location.href = 'login.html'; // Redirect to login page
                  </script>";
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seureka - Reset Password</title>
    <link href="./output.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-black flex min-h-screen items-center justify-start px-16"
    style="background-image: url('image/bgLogin.png'); background-size: cover; background-position: center; background-repeat: no-repeat;">

    <!-- Kontainer Utama -->
    <div
        class="backdrop-filter backdrop-blur-md bg-opacity-35 border border-purple-900 rounded-lg p-8 w-full max-w-md text-white">
        <h2 class="text-3xl font-bold mb-6">Reset Password</h2>

        <?php if (!empty($error)): ?>
        <p class="text-red-500 text-sm mb-4"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
        <p class="text-green-500 text-sm mb-4"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <?php if ($step == 1): ?>
        <!-- Form Langkah 1: Input Username -->
        <p class="text-sm text-gray-400 mb-4">Enter your username to reset your password.</p>
        <form action="forgot_password.php" method="POST" class="space-y-4">
            <input type="hidden" name="step" value="1">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-300 mb-1">Username</label>
                <input type="text" name="username" id="username" placeholder="Enter your username"
                    class="w-full px-4 py-2 border border-gray-800 bg-gray-800 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-700"
                    required>
            </div>
            <button type="submit"
                class="bg-purple-700 text-white font-semibold w-full py-3 rounded-md hover:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2 mb-4">
                Next
            </button>
        </form>
        <?php elseif ($step == 2): ?>
        <!-- Form Langkah 2: Reset Password -->
        <p class="text-sm text-gray-400 mb-4">Enter a new password for your account.</p>
        <form action="forgot_password.php" method="POST" class="space-y-4">
            <input type="hidden" name="step" value="2">
            <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
            <div>
                <label for="new_password" class="block text-sm font-medium text-gray-300 mb-1">New Password</label>
                <input type="password" name="new_password" id="new_password" placeholder="Enter new password"
                    class="w-full px-4 py-2 border border-gray-800 bg-gray-800 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-700"
                    required>
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-300 mb-1">Confirm New
                    Password</label>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password"
                    class="w-full px-4 py-2 border border-gray-800 bg-gray-800 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-700"
                    required>
            </div>
            <button type="submit"
                class="bg-purple-700 text-white font-semibold w-full py-3 rounded-md hover:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2 mb-4">
                Reset Password
            </button>
        </form>
        <?php endif; ?>
    </div>
</body>

</html>