<?php
session_start();
require 'db.php';

// Periksa apakah user adalah admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$communityId = $_GET['id'] ?? null;

if (!$communityId) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = "";

// Ambil data komunitas
$stmt = $conn->prepare("SELECT * FROM community WHERE id = ?");
$stmt->bind_param("i", $communityId);
$stmt->execute();
$result = $stmt->get_result();
$community = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string(trim($_POST['name']));
    $description = $conn->real_escape_string(trim($_POST['description']));
    $location = $conn->real_escape_string(trim($_POST['location']));
    $photoPath = $community['photo']; // Default to existing photo

    // Validasi input
    if (empty($name) || empty($description) || empty($location)) {
        $error = "All fields are required.";
    } else {
        // Periksa apakah ada file yang diunggah
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photo = $_FILES['photo'];
            $uploadDir = 'uploads/';
            $newPhotoPath = $uploadDir . time() . '_' . basename($photo['name']);

            // Pastikan direktori upload ada
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Validasi jenis file
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array(mime_content_type($photo['tmp_name']), $allowedTypes)) {
                $error = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
            } elseif (!move_uploaded_file($photo['tmp_name'], $newPhotoPath)) {
                $error = "Failed to upload photo.";
            } else {
                // Hapus foto lama jika ada
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
                $photoPath = $newPhotoPath;
            }
        }

        // Jika tidak ada error, update database
        if (empty($error)) {
            $sql = "UPDATE community SET name = ?, description = ?, location = ?, photo = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $name, $description, $location, $photoPath, $communityId);
            if ($stmt->execute()) {
                // Redirect ke admin_dashboard.php dengan notifikasi sukses
                header("Location: admin_dashboard.php?success=Community+updated+successfully");
                exit;
            } else {
                $error = "Failed to update community.";
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
    <title>Update Community</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-white px min-h-screen">
    <div class="container mx-auto px-10 py-8">
        <h1 class="text-black text-3xl font-bold mb-6">Update Community</h1>

        <?php if ($error): ?>
        <div class="bg-red-500 text-white px-4 py-2 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium">Community Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($community['name']); ?>"
                    class="w-full p-2 rounded bg-gray-200 text-black" required>
            </div>
            <div>
                <label for="description" class="block text-sm font-medium">Description</label>
                <textarea id="description" name="description" rows="4" class="w-full p-2 rounded bg-gray-200 text-black"
                    required><?php echo htmlspecialchars($community['description']); ?></textarea>
            </div>
            <div>
                <label for="location" class="block text-sm font-medium">Location</label>
                <input type="text" id="location" name="location"
                    value="<?php echo htmlspecialchars($community['location']); ?>"
                    class="w-full p-2 rounded bg-gray-200 text-black" required>
            </div>
            <div>
                <label for="photo" class="block text-sm font-medium">Current Photo</label>
                <img src="<?php echo htmlspecialchars($community['photo']); ?>" alt="Community Photo"
                    class="h-32 w-32 object-cover rounded mb-4">
                <input type="file" id="photo" name="photo" class="w-full p-2 rounded bg-gray-200 text-black">
            </div>
            <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-700">Update
                Community</button>
        </form>
    </div>
</body>

</html>