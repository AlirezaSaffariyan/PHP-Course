<?php
session_start();
include "../env.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $albumId = intval($_POST['id']);

    $stmt = $conn->prepare("DELETE FROM Albums WHERE id = ?");
    $stmt->bind_param("i", $albumId);
    
    try {
        if ($stmt->execute()) {
            $_SESSION['toast_message'] = "Album deleted successfully.";
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
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Album | Music Player</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
    <script>
        async function fetchAlbums(artistId) {
            try {
                console.log('Fetching albums for artist:', artistId);
                const searchResponse = await fetch(`fetch_albums.php?artist_id=${artistId}`);
                console.log('Response:', searchResponse);

                if (!searchResponse.ok) {
                    throw new Error('Network response was not ok');
                }

                const responseBody = await searchResponse.text();
                console.log('Raw response:', responseBody);

                // Trim any extraneous whitespace
                const parsedResponse = JSON.parse(responseBody.trim());

                if (parsedResponse.error) {
                    console.error('Error from server:', parsedResponse.error);
                    return; // Exit if there is an error
                }

                console.log('Albums fetched:', parsedResponse);

                const albumSelect = document.getElementById('album_id');
                albumSelect.innerHTML = '';
                parsedResponse.forEach(album => {
                    console.log('Adding album to selection:', album);
                    const option = document.createElement('option');
                    option.value = album.id;
                    option.textContent = album.title;
                    albumSelect.appendChild(option);
                });

                document.getElementById('album_id_section').style.display = 'block';
                document.getElementById('delete_album_section').style.display = 'block';
            } catch (error) {
            console.error('Error fetching albums:', error);
            }
        }


        function showAlbumSelection() {
            const artistId = document.getElementById('artist_id').selectedOptions[0].value;
            if (artistId) {
                fetchAlbums(artistId);
                document.getElementById('select_artist_button').style.display = 'none';
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
                <h1>Delete Album</h1>
                <form action="" method="post" enctype="multipart/form-data">
                    <select name="artist_id" id="artist_id" required>
                        <option value="">Select Artist</option>
                        <?php foreach ($artists as $artist): ?>
                            <option value="<?= $artist['id'] ?>"><?= $artist['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="button" class="button" value="Select Artist" id="select_artist_button" onclick="showAlbumSelection()">
                    
                    <div id="album_id_section" style="display:none; text-align: center; width: 100%;">
                        <select name="id" id="album_id" required style="margin: 0 auto; max-width: 500px;">
                            <option value="">Select Album</option>
                        </select>
                    </div>
                    
                    <div id="delete_album_section" style="display:none; text-align: center; width: 100%;">
                        <input type="submit" class="button" value="Delete Album" style="margin: 10px auto;">
                    </div>
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
        
        unset($_SESSION['toast_message']);
        unset($_SESSION['toast_type']);
    }
    ?>
</body>
</html>