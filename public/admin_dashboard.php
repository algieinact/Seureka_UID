<?php
session_start();
require 'db.php';

// Periksa apakah user adalah admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Ambil notifikasi sukses dari query string
$success = $_GET['success'] ?? null;

// Proses penghapusan pengguna
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user_id'])) {
    $deleteUserId = $_POST['delete_user_id'];

    if ($deleteUserId == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $deleteUserId);
        if ($stmt->execute()) {
            $success = "User successfully deleted.";
        } else {
            $error = "Failed to delete user.";
        }
        $stmt->close();
    }
}

// Ambil semua pengguna
$stmt = $conn->prepare("SELECT id, username, email, role FROM users ORDER BY role DESC, username ASC");
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Hitung jumlah role
$stmt = $conn->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$stmt->execute();
$result = $stmt->get_result();
$roleCounts = [];
while ($row = $result->fetch_assoc()) {
    $roleCounts[$row['role']] = $row['count'];
}
$stmt->close();

// SELECT role: Mengambil kolom role dari tabel users.
// COUNT(*) as count: Menghitung jumlah baris (pengguna) dalam setiap grup role.
// GROUP BY role: Mengelompokkan data berdasarkan nilai kolom role.

// Ambil semua komunitas
$stmt = $conn->prepare("SELECT id, name, created_date, photo, description, location, members FROM community ORDER BY created_date DESC");
$stmt->execute();
$result = $stmt->get_result();
$communities = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-white px min-h-screen">
    <div class="container mx-auto px-10 py-8">
        <h1 class="text-black text-3xl font-bold mb-6">Dashboard <span
                class="text-purple-400"><?php echo htmlspecialchars($_SESSION['username']); ?></span>. Hello Admin!</h1>

        <!-- Pesan Sukses/Error -->
        <?php if (!empty($error)): ?>
        <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
        <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4">
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <!-- Navigasi -->
        <div class="flex gap-4 mb-8">
            <a href="create_user.php" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-700">Add New
                User</a>
            <a href="create_community.php" class="bg-purple-500 text-white px-6 py-2 rounded hover:bg-purple-700">Add
                New
                Community</a>
        </div>

        <!-- Statistik Pengguna -->
        <h1 class="text-3xl text-black font-bold mb-4">Pengguna</h1>
        <div class="mb-4">
            <p class="text-lg text-black">
                Total Admin: <span class="font-bold"><?php echo $roleCounts['admin'] ?? 0; ?></span>
            </p>
            <p class="text-lg text-black">
                Total User: <span class="font-bold"><?php echo $roleCounts['user'] ?? 0; ?></span>
            </p>
        </div>

        <!-- Tabel Pengguna -->
        <div class="overflow-x-auto bg-gray-300 rounded-lg shadow-md mb-8">
            <table class="table-auto w-full border-collapse">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="border border-black px-4 py-2">ID</th>
                        <th class="border border-black px-4 py-2">Username</th>
                        <th class="border border-black px-4 py-2">Email</th>
                        <th class="border border-black px-4 py-2">Role</th>
                        <th class="border border-black px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr class="text-center text-black">
                        <td class="border border-black px-4 py-2"><?php echo htmlspecialchars($user['id']); ?></td>
                        <td class="border border-black px-4 py-2"><?php echo htmlspecialchars($user['username']); ?>
                        </td>
                        <td class="border border-black px-4 py-2"><?php echo htmlspecialchars($user['email']); ?>
                        </td>
                        <td class="border border-black px-4 py-2"><?php echo htmlspecialchars($user['role']); ?>
                        </td>
                        <td class="border border-black px-4 py-2">
                            <a href="update_user.php?id=<?php echo htmlspecialchars($user['id']); ?>"
                                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-700">Update</a>
                            <?php if ($user['role'] !== 'admin'): ?>
                            <form action="admin_dashboard.php" method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this user?');" class="inline">
                                <input type="hidden" name="delete_user_id"
                                    value="<?php echo htmlspecialchars($user['id']); ?>">
                                <button type="submit"
                                    class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-700">Delete</button>
                            </form>
                            <?php else: ?>
                            <span class="text-gray-500">Cannot Delete</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Tabel Community -->
        <h1 class="text-3xl text-black font-bold mb-4">Communities</h1>
        <div class="overflow-x-auto bg-gray-300 rounded-lg shadow-md">
            <table class="table-auto w-full border-collapse">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="border border-black px-4 py-2">ID</th>
                        <th class="border border-black px-4 py-2">Name</th>
                        <th class="border border-black px-4 py-2">Photo</th>
                        <th class="border border-black px-4 py-2">Description</th>
                        <th class="border border-black px-4 py-2">Location</th>
                        <th class="border border-black px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($communities as $community): ?>
                    <tr class="text-center text-black">
                        <td class="border border-black px-4 py-2"><?php echo htmlspecialchars($community['id']); ?></td>
                        <td class="border border-black px-4 py-2"><?php echo htmlspecialchars($community['name']); ?>
                        </td>
                        <td class="border border-black px-4 py-2">
                            <img src="<?php echo htmlspecialchars($community['photo']); ?>" alt="Photo"
                                class="h-16 w-16 object-cover rounded">
                        </td>
                        <td class="border border-black px-4 py-2">
                            <?php echo htmlspecialchars($community['description']); ?></td>
                        <td class="border border-black px-4 py-2">
                            <?php echo htmlspecialchars($community['location']); ?></td>
                        <td class="border border-black px-4 py-2">
                            <a href="update_community.php?id=<?php echo htmlspecialchars($community['id']); ?>"
                                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-700">Update</a>
                            <form action="admin_dashboard.php" method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this community?');"
                                class="inline">
                                <input type="hidden" name="delete_community_id"
                                    value="<?php echo htmlspecialchars($community['id']); ?>">
                                <button type="submit"
                                    class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-700">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Tombol Logout -->
        <div class="mt-6">
            <a href="logout.php" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-700">Logout</a>
        </div>
    </div>
</body>

</html>