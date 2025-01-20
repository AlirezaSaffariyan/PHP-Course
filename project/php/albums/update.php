<?php
session_start();
include "../env.php";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch artists for initial selection
$artists = [];
$result = $conn->query("SELECT *
FROM Artists a
WHERE EXISTS (
    SELECT 1
    FROM Albums al
    WHERE al.artist_id = a.id
)");
while ($row = $result->fetch_assoc()) {
    $artists[] = $row;
}

// Initialize variables
$albumData = null;

// Fetch albums for a specific artist
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['artist_id'])) {
    $artistId = intval($_GET['artist_id']);
    
    $stmt = $conn->prepare("SELECT id, title FROM Albums WHERE artist_id = ?");
    $stmt->bind_param("i", $artistId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $albums = [];
    while ($row = $result->fetch_assoc()) {
        $albums[] = $row;
    }
    
    echo json_encode($albums);
    exit();
}

// Get album details for update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['album_id'])) {
    $albumId = intval($_POST['album_id']);
    
    $stmt = $conn->prepare("SELECT * FROM Albums WHERE id = ?");
    $stmt->bind_param("i", $albumId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $albumData = $result->fetch_assoc();
    } else {
        $_SESSION['toast_message'] = "Album not found.";
        $_SESSION['toast_type'] = "error";
    }
}

// Update album information
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    // File upload handling
    $cover_image = null;
    $upload_error = '';

    // Check if a file was uploaded
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Detailed file upload error checking
        switch ($_FILES['cover_image']['error']) {
            case UPLOAD_ERR_OK:
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
            if ($_FILES['cover_image']['size'] > $maxFileSize) {
                $upload_error = "File is too large. Maximum size is 5MB.";
            }

            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($_FILES['cover_image']['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                $upload_error = "Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.";
            }
        }

        // If no errors, process the file
        if (empty($upload_error)) {
            // Read file contents
            $cover_image = file_get_contents($_FILES['cover_image']['tmp_name']);
        } else {
            // Display upload error
            $_SESSION['toast_message'] = $upload_error . "<br>";
            $_SESSION['toast_type'] = "error";
        }
    }

    // If no new picture is uploaded, use the existing picture
    if ($cover_image === null && isset($_POST['current_cover_image'])) {
        // Decode the base64 encoded current cover image
        $cover_image = base64_decode($_POST['current_cover_image']);
    }

    $id = $_POST['id'];
    $title = $_POST['title'];
    $release_date = $_POST['release_date'];
    $artist_id = $_POST['artist_id'];

    // Prepare statement
    $stmt = $conn->prepare("UPDATE Albums SET 
        title = ?,
        release_date = ?,
        artist_id = ?,
        cover_image = ?,
        updated_at = NOW()
        WHERE id = ?");

    // Bind parameters
    $stmt->bind_param("ssssi", 
        $title,
        $release_date,
        $artist_id,
        $cover_image,
        $id
    );
    
    try {
        if ($stmt->execute()) {
            $_SESSION['toast_message'] = "Album updated successfully.";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Album | Music Player</title>
    <link rel="stylesheet" href="../styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script>
        async function fetchAlbums(artistId) {
            try {
                const response = await fetch(`?artist_id=${artistId}`);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                const albums = await response.json();
                
                const albumSelect = document.getElementById('album_id');
                albumSelect.innerHTML = '<option value="">Select an album</option>';
                albums.forEach(album => {
                    const option = document.createElement('option');
                    option.value = album.id;
                    option.textContent = album.title;
                    albumSelect.appendChild(option);
                });

                document.getElementById('album_id_section').style.display = 'block';
                document.getElementById('update_album_section').style.display = 'block';
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to fetch albums');
            }
        }

        function showAlbumSelection() {
            const artistId = document.getElementById('artist_id').value;
            if (artistId) {
                fetchAlbums(artistId);
                document.getElementById('select_artist_button').style.display = 'none';
            }
        }

        async function fetchAlbumDetails() {
            const albumId = document.getElementById('album_id').value;
            if (albumId) {
                const artistId = document.getElementById('artist_id').value;
                document.getElementById('album_details_form').submit();
            }
        }
    </script>
</head>
<body>
    <div class="header">Music Player Album Management</div>

    <div class="navigation">
        <a href="./index.php">← Album Management</a>
        <a href="./insert.php">Insert Album</a>
        <a href="./update.php">Update Album</a>
        <a href="./delete.php">Delete Album</a>
        <a href="./read.php">View Albums</a>
    </div>

    <div class="container">
        <div class="main-content">
            <div class="card">
                <h1>Update Album</h1>
                <form id="album_details_form" method="POST" enctype="multipart/form-data">
                    <select id="artist_id" name="artist_id" required>
                        <option value="">Select an artist</option>
                        <?php foreach ($artists as $artist): ?>
                            <option value="<?= $artist['id'] ?>"><?= htmlspecialchars($artist['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" class="button" id="select_artist_button" value="Select Artist" onclick="showAlbumSelection()">

                    <div id="album_id_section" style="display:none; text-align: center; width: 100%;">
                        <select id="album_id" name="album_id" required style="margin: 0 auto; max-width: 500px;">
                            <option value="">Select an album</option>
                        </select>
                    </div>
                    <div id="update_album_section" style="display:none; text-align: center; width: 100%;">
                        <input type="submit" class="button" id="select_album_button" value="Select Album" style="margin: 10px auto;" onclick="fetchAlbumDetails()">
                    </div>

                    <?php if ($albumData): ?>
                        <input type="hidden" name="id" value="<?= $albumData['id'] ?>">
                        
                        <!-- Display current cover image -->
                        <?php if ($albumData['cover_image']): ?>
                            <img src="data:image/png;base64,<?php echo base64_encode($albumData['cover_image']); ?>" 
                                alt="Current Cover Image" 
                                class="current-profile-picture">
                            <input type="hidden" 
                                name="current_cover_image" 
                                value="<?php echo base64_encode($albumData['cover_image']); ?>">
                        <?php endif; ?>

                        <input type="text" name="title" value="<?= htmlspecialchars($albumData['title']) ?>" required>
                        <input type="date" name="release_date" value="<?= $albumData['release_date'] ?>" required>
                        <input type="file" name="cover_image" accept="image/*">

                        <div id="add_album_section" style="text-align: center; width: 100%;">
                            <input type="submit" name="update" class="button" value="Update Album" style="margin: 10px auto;">
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="footer">© 2024 Music Player</div>

    <?php
    if (isset($_SESSION['toast_message'])) {
        $toastType = $_SESSION['toast_type'] ?? 'success';
        echo "<div class='toast toast-{$toastType}'>{$_SESSION['toast_message']}</div>";
        
        unset($_SESSION['toast_message']);
        unset($_SESSION['toast_type']);
    }
    ?>
</body>
</html>