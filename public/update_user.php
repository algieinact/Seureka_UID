<?php
session_start();
require 'db.php'; // Koneksi database

// Periksa apakah admin sudah login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Ambil ID pengguna yang akan diupdate (dikirimkan melalui URL atau form)
if (isset($_GET['id'])) {
    $userId = $_GET['id'];
} elseif (isset($_POST['id'])) {
    $userId = $_POST['id'];
} else {
    die('User ID is required.');
}

// Ambil data pengguna dari database
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die('User not found.');
}

// Proses form update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    $role = $_POST['role'];
    $profilePicture = $user['profile_picture'];

    // Validasi input
    if (empty($username) || empty($email)) {
        $error = 'Username and Email are required.';
    } elseif (!empty($newPassword) && $newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        // Jika ada file yang diunggah
        if (!empty($_FILES['profile_picture']['name'])) {
            $uploadDir = 'uploads/';
            $fileName = basename($_FILES['profile_picture']['name']);
            $targetFile = $uploadDir . $fileName;

            // Validasi file gambar
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $fileExtension = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowedExtensions)) {
                $error = 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.';
            } elseif (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
                $profilePicture = $fileName; // Update foto profil
            } else {
                $error = 'Failed to upload the profile picture.';
            }
        }

        // Jika tidak ada error, update data pengguna
        if (empty($error)) {
            $hashedPassword = !empty($newPassword) ? password_hash($newPassword, PASSWORD_BCRYPT) : null;

            // Query update data pengguna
            $updateQuery = "UPDATE users SET username = ?, email = ?, profile_picture = ?, role = ?";
            $params = [$username, $email, $profilePicture, $role];

            if ($hashedPassword) {
                $updateQuery .= ", password = ?";
                $params[] = $hashedPassword;
            }

            $updateQuery .= " WHERE id = ?";
            $params[] = $userId;

            // Persiapkan query
            $stmt = $conn->prepare($updateQuery);

            if ($hashedPassword) {
                $stmt->bind_param(
                    "sssssi",
                    $username,
                    $email,
                    $profilePicture,
                    $role,
                    $hashedPassword,
                    $userId
                );
            } else {
                $stmt->bind_param(
                    "ssssi",
                    $username,
                    $email,
                    $profilePicture,
                    $role,
                    $userId
                );
            }

            // Eksekusi query
            if ($stmt->execute()) {
                // Redirect ke admin_dashboard dengan pesan sukses
                header("Location: admin_dashboard.php?success=User updated successfully.");
                exit;
            } else {
                $error = 'Failed to update user.';
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update User</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
        <h2 class="text-2xl font-bold mb-6">Update User</h2>

        <!-- Pesan sukses/error -->
        <?php if (!empty($error)): ?>
        <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4">
            <?= htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form action="update_user.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="id" value="<?= htmlspecialchars($userId); ?>">

            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']); ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
            </div>
            <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" id="new_password" name="new_password" placeholder="Enter new password"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm
                    Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
            </div>
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select id="role" name="role"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            <div>
                <label for="profile_picture" class="block text-sm font-medium text-gray-700 mb-1">Profile
                    Picture</label>
                <input type="file" id="profile_picture" name="profile_picture"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                <?php if (!empty($user['profile_picture'])): ?>
                <div class="mt-2">
                    <img src="uploads/<?= htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture"
                        class="h-20 w-20 rounded-full">
                </div>
                <?php endif; ?>
            </div>
            <button type="submit"
                class="w-full bg-purple-600 text-white font-semibold py-2 rounded-md hover:bg-purple-700 focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                Update User
            </button>

        </form>
    </div>
</body>

</html>