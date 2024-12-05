<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

// Include Database Connection
require_once 'db.php';

$error = "";
$success = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string(trim($_POST['name']));
    $description = $conn->real_escape_string(trim($_POST['description']));
    $location = $conn->real_escape_string(trim($_POST['location']));

    // Validate input
    if (empty($name) || empty($description) || empty($location)) {
        $error = "All fields are required.";
    } elseif (strlen($name) > 255) {
        $error = "Community name cannot exceed 255 characters.";
    } else {
        // Get Current Date
        $created_date = date('Y-m-d');

        // Default Members Count
        $members = 0;

        // Handle File Upload
        $photo = $_FILES['photo'];
        $upload_dir = 'uploads/';
        $photo_path = $upload_dir . time() . '_' . basename($photo['name']);

        // Ensure upload directory exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (!in_array(mime_content_type($photo['tmp_name']), ['image/jpeg', 'image/png', 'image/gif'])) {
            $error = "Invalid file type. Only JPG, PNG, and GIF files are allowed.";
        } elseif (!move_uploaded_file($photo['tmp_name'], $photo_path)) {
            $error = "Failed to upload photo.";
        } else {
            // Insert into Database
            $sql = "INSERT INTO community (name, created_date, photo, description, location, members)
                    VALUES ('$name', '$created_date', '$photo_path', '$description', '$location', $members)";
            if ($conn->query($sql)) {
                $success = "Community added successfully!";
            } else {
                $error = "Error: " . $conn->error;
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
    <title>Add Community</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>

<body class="bg-gray-900 text-white min-h-screen">
    <div class="container mx-auto p-8">
        <h1 class="text-3xl font-bold mb-6">Add New Community</h1>
        <?php if ($error): ?>
        <div class="bg-red-500 text-white p-4 mb-4 rounded">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="bg-green-500 text-white p-4 mb-4 rounded">
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium">Community Name</label>
                <input type="text" id="name" name="name" class="w-full p-2 rounded bg-gray-800 text-white" required>
            </div>
            <div>
                <label for="photo" class="block text-sm font-medium">Community Photo</label>
                <input type="file" id="photo" name="photo" class="w-full p-2 rounded bg-gray-800 text-white"
                    accept="image/*" required>
            </div>
            <div>
                <label for="description" class="block text-sm font-medium">Description</label>
                <textarea id="description" name="description" rows="4" class="w-full p-2 rounded bg-gray-800 text-white"
                    required></textarea>
            </div>
            <div>
                <label for="location" class="block text-sm font-medium">Location</label>
                <input type="text" id="location" name="location" class="w-full p-2 rounded bg-gray-800 text-white"
                    required>
            </div>
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-6 py-2 rounded text-white font-bold">
                Add Community
            </button>
        </form>
    </div>
</body>

</html>