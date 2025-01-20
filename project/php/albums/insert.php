<?php
session_start();
include "../env.php";

function getAlbumDetails($albumId) {
    $url = "https://musicbrainz.org/ws/2/release-group/{$albumId}?inc=releases&fmt=json";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "MusicPlayer/1.0 ( ar.saffariyan@gmail.com )");

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $_SESSION['toast_message'] = 'cURL Error: ' . curl_error($ch);
        $_SESSION['toast_type'] = "error";
    } else {
        $data = json_decode($response, true);
    }
    curl_close($ch);

    return $data;
}

function searchArtistByName($artistName) {
    $url = "https://musicbrainz.org/ws/2/artist/?query=" . urlencode($artistName) . "&fmt=json";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "MusicPlayer/1.0 ( ar.saffariyan@gmail.com )");

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return ['error' => 'cURL Error: ' . curl_error($ch)];
    } else {
        $data = json_decode($response, true);
    }
    curl_close($ch);

    if (!isset($data['artists'][0]['id'])) {
        return ['error' => 'No artists found'];
    }

    return $data['artists'][0]['id'];
}

function getArtistAlbums($artistId) {
    $url = "https://musicbrainz.org/ws/2/release-group?artist={$artistId}&type=album&fmt=json";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Musicplayer/1.0 ( ar.saffariyan@gmail.com )");

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return ['error' => 'cURL Error: ' . curl_error($ch)];
    } else {
        $data = json_decode($response, true);
    }
    curl_close($ch);

    if (!isset($data['release-groups'])) {
        return ['error' => 'No albums found'];
    }

    return $data['release-groups'];
}


if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['artist_name'])) {
    $artistName = $_GET['artist_name'];
    $artistId = searchArtistByName($artistName);

    if (isset($artistId['error'])) {
        echo json_encode($artistId);
    } else {
        $albums = getArtistAlbums($artistId);
        echo json_encode($albums);
    }

    exit(); // Ensure no further output
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $artist_id = $_POST['artist_id'];
    $album_id = $_POST['album_id'];
    $artist_name = $conn->query("SELECT name FROM Artists where id = " . $artist_id);
    $artist_name = $artist_name->fetch_assoc()['name'];
    
    // Get album details from MusicBrainz
    $album_details = getAlbumDetails($album_id);

    $title = $album_details['title'];
    $release_date = $album_details['first-release-date'] ?? date('Y-m-d');
    
    // Check if the release date is a year (4 digits)
    if (preg_match('/^\d{4}$/', $release_date)) {
        // Convert to the format YYYY-MM-DD
        $release_date = $release_date . '-01-01';
    }
    
    // Try to get the album cover art
    $cover_image = shell_exec('python3 ./get_album_cover_art.py "' . $artist_name . '" "' . $title . '"');
    $cover_image = file_get_contents($cover_image);

    $stmt = $conn->prepare("INSERT INTO Albums (title, release_date, cover_image, artist_id, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("sssi", $title, $release_date, $cover_image, $artist_id);
    
    try {
        if ($stmt->execute()) {
            $_SESSION['toast_message'] = "New album created successfully.";
            $_SESSION['toast_type'] = "success";
        }
    } catch (mysqli_sql_exception $e) {
        $_SESSION['toast_message'] = "An error occurred: " . $e->getMessage();
        $_SESSION['toast_type'] = "error";
    }
    // An error occurred: Incorrect date value: '1988' for column 'release_date' at row 1
    $stmt->close();
    $conn->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$conn = new mysqli($host, $user, $pass, $db);
$artists = [];
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    $result = $conn->query("SELECT id, name FROM Artists");
    while ($row = $result->fetch_assoc()) {
        $artists[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Album | Music Player</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
    <script>
        async function fetchAlbums(artistName) {
            try {
                console.log('Fetching albums for artist:', artistName);
                const searchResponse = await fetch(`?artist_name=${encodeURIComponent(artistName)}`);
                
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
                document.getElementById('add_album_section').style.display = 'block';
            } catch (error) {
                console.error('Error fetching albums:', error);
            }
        }

        function showAlbumSelection() {
            const artistName = document.getElementById('artist_id').selectedOptions[0].textContent;
            if (artistName) {
                fetchAlbums(artistName);
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
                <h1>Insert New Album</h1>
                <form action="" method="post" enctype="multipart/form-data">
                    <select name="artist_id" id="artist_id" required>
                        <option value="">Select Artist</option>
                        <?php foreach ($artists as $artist): ?>
                            <option value="<?= $artist['id'] ?>"><?= $artist['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="button" class="button" value="Select Artist" id="select_artist_button" onclick="showAlbumSelection()">
                    
                    <div id="album_id_section" style="display:none; text-align: center; width: 100%;">
                        <select name="album_id" id="album_id" required style="margin: 0 auto; max-width: 500px;">
                            <option value="">Select Album</option>
                        </select>
                    </div>
                    
                    <div id="add_album_section" style="display:none; text-align: center; width: 100%;">
                        <input type="submit" class="button" value="Add Album" style="margin: 10px auto;">
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
