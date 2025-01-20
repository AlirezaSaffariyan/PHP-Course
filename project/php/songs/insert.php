<?php
session_start();
include "../env.php";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$artists = [];
$albums = [];
$genres = [];
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    $artists = $conn->query("SELECT id, name FROM Artists")->fetch_all(MYSQLI_ASSOC);
    $genres = $conn->query("SELECT id, name FROM Genres")->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Calculate total duration in seconds
    $minutes = intval($_POST['duration_minutes']);
    $seconds = intval($_POST['duration_seconds']);
    $total_duration = ($minutes * 60) + $seconds;

    // Fields for song details
    $title = $_POST['title'];
    $genre_id = $_POST['genre_id'];
    $album_id = $_POST['album_id'];
    $track_number = $_POST['track_number'];
    $disc_number = $_POST['disc_number'];
    $artist_ids = $_POST['artist_ids']; // Array of artist IDs

    // Ensure song file field is empty
    $song_file = '';

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert into Songs table
        $stmt = $conn->prepare("INSERT INTO Songs (title, duration, genre_id, album_id, track_number, disc_number, file, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("siiiiss", $title, $total_duration, $genre_id, $album_id, $track_number, $disc_number, $song_file);
        $stmt->execute();
        $song_id = $conn->insert_id;

        // Insert into SongArtists table
        $stmt = $conn->prepare("INSERT INTO SongArtists (song_id, artist_id) VALUES (?, ?)");
        foreach ($artist_ids as $artist_id) {
            $stmt->bind_param("ii", $song_id, $artist_id);
            $stmt->execute();
        }

        $conn->commit();
        $_SESSION['toast_message'] = "New song created successfully.";
        $_SESSION['toast_type'] = "success";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast_message'] = "An error occurred: " . $e->getMessage();
        $_SESSION['toast_type'] = "error";
    }

    $stmt->close();
    $conn->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Song | Music Player</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
    <style>
        #duration-container {
            display: flex;
            gap: 10px;
        }

        #duration-container input {
            flex: 1;
        }
    </style>
    <script>
        async function fetchAlbums(artistId) {
            try {
                const response = await fetch(`fetch_song.php?artist_id=${artistId}`);
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
                document.getElementById('album_id_section').style.display = 'block';
                document.getElementById('select_album_button').style.display = 'block';
            }
        }

        function showSongInputs() {
            const albumId = document.getElementById('album_id').value;
            if (albumId) {
                document.getElementById('song_inputs_section').style.display = 'flex';
                document.getElementById('song_inputs_section').style.flexDirection = 'column';
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
                <h1>Insert New Song</h1>
                <form action="" method="post">
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
                    <div id="select_album_button" style="display:none; text-align: center; width: 100%;">
                        <input type="button" class="button" value="Select Album" id="select_album_button" style="margin: 10px auto;" onclick="showSongInputs()">
                    </div>

                    <div id="song_inputs_section" style="display:none; margin: 10px auto;">
                        <input type="text" name="title" placeholder="Song Title" required>
                        <div id="duration-container">
                            <input type="number" name="duration_minutes" placeholder="Minutes" min="0" max="59" required>
                            <input type="number" name="duration_seconds" placeholder="Seconds" min="0" max="59" required>
                        </div>
                        <select name="genre_id" required>
                            <option value="">Select Genre</option>
                            <?php foreach ($genres as $genre): ?>
                                <option value="<?= $genre['id'] ?>"><?= $genre['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="track_number" placeholder="Track Number" required>
                        <input type="number" name="disc_number" placeholder="Disc Number" required>
                        <select name="artist_ids[]" multiple required>
                            <?php foreach ($artists as $artist): ?>
                                <option value="<?= $artist['id'] ?>"><?= $artist['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small>Hold Ctrl (Windows) or Command (Mac) to select multiple artists</small>
                        <input type="submit" class="button" value="Add Song">
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

        // Clear the toast message
        unset($_SESSION['toast_message']);
        unset($_SESSION['toast_type']);
    }
    ?>
</body>
</html>
