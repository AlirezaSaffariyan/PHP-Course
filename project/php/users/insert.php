<?php
session_start();
include "../env.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $date_of_birth = $_POST['date_of_birth'];
    $region = $_POST['region'];

    // Generate visual hash for profile picture using the username
    $profile_picture = file_get_contents("https://robohash.org/{$username}.png");

    // Prepare and execute the insert statement
    $stmt = $conn->prepare("INSERT INTO Users (username, email, password, first_name, last_name, date_of_birth, region, profile_picture, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("ssssssss", $username, $email, $password, $first_name, $last_name, $date_of_birth, $region, $profile_picture);
    
    try {
        if ($stmt->execute()) {
            // Set success toast message
            $_SESSION['toast_message'] = "New user created successfully.";
            $_SESSION['toast_type'] = "success";
        }
    } catch (mysqli_sql_exception $e) {
        // Handle duplicate entry errors
        if ($e->getCode() == 1062) {
            // Check if the error is for username or email
            if (strpos($e->getMessage(), 'username') !== false) {
                $_SESSION['toast_message'] = "Username already exists. Please choose a different username.";
                $_SESSION['toast_type'] = "error";
            } elseif (strpos($e->getMessage(), 'email') !== false) {
                $_SESSION['toast_message'] = "Email already exists. Please use a different email address.";
                $_SESSION['toast_type'] = "error";
            } else {
                $_SESSION['toast_message'] = "An error occurred: " . $e->getMessage();
                $_SESSION['toast_type'] = "error";
            }
        } else {
            // For other types of errors
            $_SESSION['toast_message'] = "An unexpected error occurred: " . $e->getMessage();
            $_SESSION['toast_type'] = "error";
        }
    }

    $stmt->close();
    $conn->close();

    // Redirect back to the form
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert User | Music Player</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="header">Music Player User Management</div>

    <div class="navigation">
        <a href="./index.php">← User Management</a>
        <a href="./insert.php">Insert User</a>
        <a href="./update.php">Update User</a>
        <a href="./delete.php">Delete User</a>
        <a href="./read.php">View Users</a>
    </div>
    
    <div class="container">
        <div class="main-content">
            <div class="card">
                <h1>Insert New User</h1>
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <input type="text" name="first_name" placeholder="First Name" required>
                    <input type="text" name="last_name" placeholder="Last Name" required>
                    <input type="date" name="date_of_birth" required>
                    <input type="text" name="region" placeholder="Region" required>
                    <input type="file" name="profile_picture" accept="image/*">
                    <input type="submit" class="button" value="Insert User">
                </form>
            </div>
        </div>
    </div>
    
    <div class="footer">© 2024 Music Player</div>

    <?php
    // Display toast message if it exists
    if (isset($_SESSION['toast_message'])) {
        $toastType = $_SESSION['toast_type'] ?? 'success';
        echo "<div class='toast toast-{$toastType}'>{$_SESSION['toast_message']}</div>";
        
        // Clear the toast message
        unset($_SESSION['toast_message']);
        unset($_SESSION['toast_type']);
    }
    ?>
</body>
</html>