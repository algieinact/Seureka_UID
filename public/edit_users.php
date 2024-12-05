<?php
require 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM users WHERE id=$id";
    $result = $conn->query($sql);
    $user = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);

    $sql = "UPDATE users SET username='$username', email='$email' WHERE id=$id";

    if ($conn->query($sql) === TRUE) {
        header("Location: list_users.php");
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>

<!-- Form Edit -->
<form method="POST">
    <input type="hidden" name="id" value="<?= $user['id'] ?>">
    <input type="text" name="username" value="<?= $user['username'] ?>" required><br>
    <input type="email" name="email" value="<?= $user['email'] ?>" required><br>
    <button type="submit">Update</button>
</form>
