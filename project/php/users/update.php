<?php
session_start();
include "../env.php";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$userData = null;

// Check if the form to search for a user has been submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $searchValue = $conn->real_escape_string($_POST['id']);
    
    // Check if the input is an ID or username
    $id = (int)$searchValue;
    $query = "SELECT * FROM Users WHERE id = '$id'";
    
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $userData = $result->fetch_assoc(); // Fetch user data
    } else {
        $_SESSION['toast_message'] = "User not found.";
        $_SESSION['toast_type'] = "error";
    }
}

// Update user information
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    // Comprehensive file upload validation
    $profile_picture = null;
    $upload_error = '';

    // Check if a file was uploaded
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Detailed file upload error checking
        switch ($_FILES['profile_picture']['error']) {
            case UPLOAD_ERR_OK:
                // File uploaded successfully
                break;
            case UPLOAD_ERR_INI_SIZE:
                $upload_error = "File is too large (server configuration)";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $upload_error = "File is too large (form configuration)";
                break;
            case UPLOAD_ERR_PARTIAL:
                $upload_error = "File was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $upload_error = "No file was uploaded";
                break;
            default:
                $upload_error = "Unknown upload error";
        }

        // If no previous errors, proceed with file validation
        if (empty($upload_error)) {
            // Validate file size (5MB max)
            $maxFileSize = 5 * 1024 * 1024; // 5MB
            if ($_FILES['profile_picture']['size'] > $maxFileSize) {
                $upload_error = "File is too large. Maximum size is 5MB.";
            }

            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($_FILES['profile_picture']['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                $upload_error = "Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.";
            }
        }

        // If no errors, process the file
        if (empty($upload_error)) {
            // Read file contents
            $profile_picture = file_get_contents($_FILES['profile_picture']['tmp_name']);
        } else {
            // Display upload error
            $_SESSION['toast_message'] = $upload_error . "<br>";
            $_SESSION['toast_type'] = "error";
        }
    }

    // If no new picture is uploaded, use the existing picture
    if ($profile_picture === null && isset($_POST['current_profile_picture'])) {
        // Decode the base64 encoded current profile picture
        $profile_picture = base64_decode($_POST['current_profile_picture']);
    }

    // Prepare update query
    $id = $_POST['id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $date_of_birth = $_POST['date_of_birth'];
    $region = $_POST['region'];

    // Prepare statement
    $stmt = $conn->prepare("UPDATE Users SET 
        username = ?, 
        email = ?, 
        first_name = ?, 
        last_name = ?, 
        date_of_birth = ?, 
        region = ?, 
        profile_picture = ?, 
        updated_at = NOW() 
        WHERE id = ?");

    // Bind parameters
    $stmt->bind_param("sssssssi", 
        $username, 
        $email, 
        $first_name, 
        $last_name, 
        $date_of_birth, 
        $region, 
        $profile_picture, 
        $id
    );
    
    try {
        if ($stmt->execute()) {
            // Set success toast message
            $_SESSION['toast_message'] = "User updated successfully.";
            $_SESSION['toast_type'] = "success";
        }
    } catch (mysqli_sql_exception $e) {
        $_SESSION['toast_message'] = "An unexpected error occurred: " . $e->getMessage();
        $_SESSION['toast_type'] = "error";
    }

    $stmt->close();
    $conn->close();

    // Redirect back to the form
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$users = [];

$result = $conn->query("SELECT * FROM Users");
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update User | Music Player</title>
    <link rel="stylesheet" href="../styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .current-profile-picture {
            max-width: 200px;
            max-height: 200px;
            margin-bottom: 10px;
        }
    </style>
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
                <h1>Select User to Update</h1>
                <!-- Search Form -->
                <form action="" method="post">
                    <select name="id" required>
                        <option value="">Select a user</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" name="search" value="Search" class="button">
                </form>

                <!-- Update Form (pre-populated if user found) -->
                <?php if ($userData): ?>
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $userData['id']; ?>">
                    
                    <!-- Display current profile picture -->
                    <?php if ($userData['profile_picture']): ?>
                        <img src="data:image/png;base64,<?php echo base64_encode($userData['profile_picture']); ?>" 
                            alt="Current Profile Picture" 
                            class="current-profile-picture">
                        <input type="hidden" 
                            name="current_profile_picture" 
                            value="<?php echo base64_encode($userData['profile_picture']); ?>">
                    <?php endif; ?>
                    
                    <input type="text" name="username" placeholder="Username" 
                        value="<?php echo htmlspecialchars($userData['username']); ?>">
                    
                    <input type="email" name="email" placeholder="Email" 
                        value="<?php echo htmlspecialchars($userData['email']); ?>">
                    
                    <input type="text" name="first_name" placeholder="First Name" 
                        value="<?php echo htmlspecialchars($userData['first_name']); ?>">
                    
                    <input type="text" name="last_name" placeholder="Last Name" 
                        value="<?php echo htmlspecialchars($userData['last_name']); ?>">
                    
                    <input type="date" name="date_of_birth" 
                        value="<?php echo $userData['date_of_birth']; ?>">
                    
                    <input type="text" name="region" placeholder="Region" 
                        value="<?php echo htmlspecialchars($userData['region']); ?>">
                    
                    <input type="file" name="profile_picture" accept="image/*">
                    
                    <input type="submit" name="update" class="button" value="Update User">
                </form>
                <?php endif; ?>
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