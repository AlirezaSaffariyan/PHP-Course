<?php
session_start();
include "../env.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $songId = intval($_POST['id']);

    // Delete rows in SongArtists table that reference the song
    $stmt = $conn->prepare("DELETE FROM SongArtists WHERE song_id = ?");
    $stmt->bind_param("i", $songId);
    $stmt->execute();

    // Delete the song
    $stmt = $conn->prepare("DELETE FROM Songs WHERE id = ?");
    $stmt->bind_param("i", $songId);
    
    try {
        if ($stmt->execute()) {
            $_SESSION['toast_message'] = "Song deleted successfully.";
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

// Fetch songs for a specific album
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['album_id'])) {
    $albumId = intval($_GET['album_id']);
    
    $stmt = $conn->prepare("SELECT id, title FROM Songs WHERE album_id = ?");
    $stmt->bind_param("i", $albumId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $songs = [];
    while ($row = $result->fetch_assoc()) {
        $songs[] = $row;
    }
    
    echo json_encode($songs);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Song | Music Player</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
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
                document.getElementById('select_album_button').style.display = 'block';
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to fetch albums');
            }
        }

        async function fetchSongs(albumId) {
            try {
                const response = await fetch(`?album_id=${albumId}`);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                const songs = await response.json();
                
                const songSelect = document.getElementById('song_id');
                songSelect.innerHTML = '<option value="">Select a song</option>';
                songs.forEach(song => {
                    const option = document.createElement('option');
                    option.value = song.id;
                    option.textContent = song.title;
                    songSelect.appendChild(option);
                });

                document.getElementById('song_id_section').style.display = 'block';
                document.getElementById('delete_song_section').style.display = 'block';
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to fetch songs');
            }
        }

        function showAlbumSelection() {
            const artistId = document.getElementById('artist_id').value;
            if (artistId) {
                fetchAlbums(artistId);
                document.getElementById('select_artist_button').style.display = 'none';
            }
        }

        function showSongSelection() {
            const albumId = document.getElementById('album_id').value;
            if (albumId) {
                fetchSongs(albumId);
                document.getElementById('select_album_button').style.display = 'none';
            }
        }
    </script>
</head>
<body>
    <div class="header">Music Player Song Management</div>

    <div class="navigation">
        <a href="./index.php">← Song Management</a>
        <a href="./insert.php">Insert Song</a>
        <a href="./update.php">Update Song</a>
        <a href="./delete.php">Delete Song</a>
        <a href="./read.php">View Songs</a>
    </div>
    
    <div class="container">
        <div class="main-content">
            <div class="card">
                <h1>Delete Song</h1>
                <form action="" method="post" enctype="multipart/form-data">
                    <select name="artist_id" id="artist_id" required>
                        <option value="">Select an artist</option>
                        <?php foreach ($artists as $artist): ?>
                            <option value="<?= $artist['id'] ?>"><?= $artist['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" class="button" value="Select Artist" id="select_artist_button" onclick="showAlbumSelection()">
                    
                    <div id="album_id_section" style="display:none; text-align: center; width: 100%;">
                        <select name="album_id" id="album_id" required style="margin: 0 auto; max-width: 500px;">
                            <option value="">Select an album</option>
                        </select>
                    </div>
                    <div id="select_album_button" style="display:none; text-align: center; width: 100%; margin: 10px auto;">
                        <input type="submit" class="button" id="select_album_button" value="Select Album" onclick="showSongSelection()">
                    </div>

                    <div id="song_id_section" style="display:none; text-align: center; width: 100%; margin: 10px auto;">
                        <select name="id" id="song_id" required style="margin: 0 auto; max-width: 500px;">
                            <option value="">Select a song</option>
                        </select>
                    </div>
                    <div id="delete_song_section" style="display:none; text-align: center; width: 100%; margin: 0px auto;">
                        <input type="submit" class="button" id="delete_song_button" value="Delete Song">
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