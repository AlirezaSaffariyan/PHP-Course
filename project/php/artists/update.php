<?php
session_start();
include "../env.php";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$artistData = null;

// Check if the form to search for an artist has been submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $searchValue = $conn->real_escape_string($_POST['id']);
    
    // Check if the input is an ID
    $id = (int)$searchValue;
    $query = "SELECT * FROM Artists WHERE id = '$id'";
    
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $artistData = $result->fetch_assoc(); // Fetch artist data
    } else {
        $_SESSION['toast_message'] = "Artist not found.";
        $_SESSION['toast_type'] = "error";
    }
}

// Update artist information
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    // Comprehensive file upload validation
    $profile_picture = null;
    $upload_error = '';

    $_SESSION['toast_message'] = (string)isset($_FILES['profile_picture']);
    $_SESSION['toast_type'] = 'success';

    // Check if a file was uploaded
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Detailed file upload error checking
        switch ($_FILES['profile_picture']['error']) {
            case UPLOAD_ERR_OK:
                $_SESSION['toast_message'] = "No error uploading.";
                $_SESSION['toast_type'] = 'success';
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

    $id = $_POST['id'];
    $name = $_POST['name'];
    $biography = $_POST['biography'];

    // Prepare statement
    $stmt = $conn->prepare("UPDATE Artists SET 
        name = ?,
        biography = ?,
        profile_picture = ?,
        updated_at = NOW()
        WHERE id = ?");

    // Bind parameters
    $stmt->bind_param("sssi", 
        $name,
        $biography,
        $profile_picture,
        $id
    );
    
    try {
        if ($stmt->execute()) {
            $_SESSION['toast_message'] = "Artist updated successfully.";
            $_SESSION['toast_type'] = "success";
        }
    } catch (mysqli_sql_exception $e) {
        $_SESSION['toast_message'] = "An error occurred: " . $e->getMessage();
        $_SESSION['toast_type'] = "error";
    }

    $stmt->close();
    $conn->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$artists = [];

$result = $conn->query("SELECT * FROM Artists");
while ($row = $result->fetch_assoc()) {
    $artists[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Artist | Music Player</title>
    <link rel="stylesheet" href="../styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700& display=swap" rel="stylesheet">
    <style>
        .current-profile-picture {
            width: 200px;
            height: 200px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="header">Update Artist</div>

    <div class="navigation">
        <a href="./index.php">← Artist Management</a>
        <a href="./insert.php">Insert Artist</a>
        <a href="./update.php">Update Artist</a>
        <a href="./delete.php">Delete Artist</a>
        <a href="./read.php">View Artists</a>
    </div>

    <div class="container">
        <div class="main-content">
            <div class="card">
                <h1>Select Artist to Update</h1>
                <!-- Search Form -->
                <form action="" method="post">
                    <select name="id" required>
                        <option value="">Select an artist</option>
                        <?php foreach ($artists as $artist): ?>
                            <option value="<?php echo $artist['id']; ?>"><?php echo htmlspecialchars($artist['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" name="search" value="Search" class="button">
                </form>
                
                <!-- Update Form (pre-populated if artist found) -->
                <?php if ($artistData): ?>
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $artistData['id']; ?>">

                    <!-- Display current profile picture -->
                    <?php if ($artistData['profile_picture']): ?>
                        <img src="data:image/png;base64,<?php echo base64_encode($artistData['profile_picture']); ?>" 
                            alt="Current Profile Picture" 
                            class="current-profile-picture">
                        <input type="hidden" 
                            name="current_profile_picture" 
                            value="<?php echo base64_encode($artistData['profile_picture']); ?>">
                    <?php endif; ?>
                    
                    <input type="text" name="name" value="<?php echo htmlspecialchars($artistData['name']); ?>" required>

                    <textarea name="biography" rows="4"><?php echo htmlspecialchars($artistData['biography']); ?></textarea>

                    <input type="file" name="profile_picture" accept="image/*">

                    <input type="submit" name="update" class="button" value="Update Artist">
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
        
        unset($_SESSION['toast_message']);
        unset($_SESSION['toast_type']);
    }
    ?>
</body>
</html>