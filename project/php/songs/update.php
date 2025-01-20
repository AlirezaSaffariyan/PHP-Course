<?php
session_start();
include "../env.php";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$songData = null;

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

// Get song details for update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['song_id'])) {
    $songId = intval($_POST['song_id']);
    
    $stmt = $conn->prepare("SELECT s.*, 
                            al.artist_id,
                            GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') AS artist_names,
                            al.title AS album_title
                            FROM Songs s
                            JOIN SongArtists sa ON s.id = sa.song_id
                            JOIN Artists a ON sa.artist_id = a.id
                            JOIN Albums al ON s.album_id = al.id
                            WHERE s.id = ?
                            GROUP BY s.id, s.title, s.album_id, s.duration, al.title");
    $stmt->bind_param("i", $songId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $songData = $result->fetch_assoc();
    } else {
        $_SESSION['toast_message'] = "Song not found.";
        $_SESSION['toast_type'] = "error";
    }
}

// Update song information
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    // Fields for song details
    $id = $_POST['id'];
    $title = $_POST['title'];
    $genre_id = $_POST['genre_id'];
    $album_id = $_POST['album_id'];
    $track_number = $_POST['track_number'];
    $disc_number = $_POST['disc_number'];
    $artist_ids = $_POST['artist_ids']; // Array of artist IDs

    // Calculate total duration in seconds
    $minutes = intval($_POST['duration_minutes']);
    $seconds = intval($_POST['duration_seconds']);
    $total_duration = ($minutes * 60) + $seconds;

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update song details
        $stmt = $conn->prepare("UPDATE Songs SET 
            title = ?,
            duration = ?,
            genre_id = ?,
            album_id = ?,
            track_number = ?,
            disc_number = ?,
            updated_at = NOW()
            WHERE id = ?");
        $stmt->bind_param("siiiiss", 
            $title,
            $total_duration,
            $genre_id,
            $album_id,
            $track_number,
            $disc_number,
            $id
        );
        $stmt->execute();

        // Update song artists
        $stmt = $conn->prepare("DELETE FROM SongArtists WHERE song_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $stmt = $conn->prepare("INSERT INTO SongArtists (song_id, artist_id) VALUES (?, ?)");
        foreach ($artist_ids as $artist_id) {
            $stmt->bind_param("ii", $id, $artist_id);
            $stmt->execute();
        }

        $conn->commit();
        $_SESSION['toast_message'] = "Song updated successfully.";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Song | Music Player</title>
    <link rel="stylesheet" href="../styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .input-group {
            width: 100%;
            display: flex;
        }

        .input-group label {
            width: 30%;
        }

        .input-group input {
            width: 70%;
        }

        .input-group select {
            width: 70%;
        }
    </style>
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
                document.getElementById('select_song_button').style.display = 'block';
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

        function showSongDetails() {
            const songId = document.getElementById('song_id').value;
            if (songId) {
                document.getElementById('song_details_form').submit();
                document.getElementById('artist_id').style.display = 'none';
                document.getElementById('select_artist_button').style.display = 'none';
                document.getElementById('album_id_section').style.display = 'none';
                document.getElementById('select_album_button').style.display = 'none';
                document.getElementById('song_id_section').style.display = 'none';
                document.getElementById('select_song_button').style.display = 'none';
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
                <h1>Select Song to Update</h1>
                <form id="song_selection_form" method="POST" action="">
                    <select name="artist_id" id="artist_id" required>
                        <option value="">Select an artist</option>
                        <?php foreach ($artists as $artist): ?>
                            <option value="<?= $artist['id'] ?>"><?= htmlspecialchars($artist['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" class="button" id="select_artist_button" value="Select Artist" onclick="showAlbumSelection()">
                    
                    <div id="album_id_section" style="display:none; text-align: center; width: 100%;">
                        <select name="album_id" id="album_id" required style="margin: 0 auto; max-width: 500px;">
                            <option value="">Select an album</option>
                        </select>
                    </div>
                    <div id="select_album_button" style="display:none; text-align: center; width: 100%; margin: 10px auto;">
                        <input type="submit" class="button" id="select_album_button" value="Select Album" onclick="showSongSelection()">
                    </div>

                    <div id="song_id_section" style="display:none; text-align: center; width: 100%; margin: 10px auto;">
                        <select name="song_id" id="song_id" required style="margin: 0 auto; max-width: 500px;">
                            <option value="">Select a song</option>
                        </select>
                    </div>
                    <div id="select_song_button" style="display:none; text-align: center; width: 100%; margin: 0px auto;">
                        <input type="submit" class="button" id="select_song_button" value="Select Song" onclick="showSongDetails()">
                    </div>
                </form>

                <?php if ($songData): ?>
                <form id="song_details_form" method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $songData['id'] ?>">
                    
                    <div class="input-group">
                        <label for="title">Title:</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($songData['title']) ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="duration_minutes">Minutes:</label>
                        <input type="number" name="duration_minutes" value="<?= floor($songData['duration'] / 60) ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="duration_seconds">Seconds:</label>
                        <input type="number" name="duration_seconds" value="<?= $songData['duration'] % 60 ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="genre_id">Genre:</label>
                        <select name="genre_id" required>
                            <?php 
                            $genres = $conn->query("SELECT id, name FROM Genres")->fetch_all(MYSQLI_ASSOC);
                            foreach ($genres as $genre): ?>
                                <option value="<?= $genre['id'] ?>" <?= $genre['id'] == $songData['genre_id'] ? 'selected' : '' ?>><?= $genre['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="album_id">Album:</label>
                        <select name="album_id" required>
                            <?php 
                            $albums = $conn->query("SELECT id, title FROM Albums WHERE artist_id = " . $songData['artist_id'])->fetch_all(MYSQLI_ASSOC);
                            foreach ($albums as $album): ?>
                                <option value="<?= $album['id'] ?>" <?= $album['id'] == $songData['album_id'] ? 'selected' : '' ?>><?= $album['title'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="track_number">Track Number:</label>
                        <input type="number" name="track_number" value="<?= $songData['track_number'] ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="disc_number">Disc Number:</label>
                        <input type="number" name="disc_number" value="<?= $songData['disc_number'] ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="artist_ids[]">Artists:</label>
                        <select name="artist_ids[]" multiple required>
                            <?php foreach ($artists as $artist): ?>
                                <option value="<?= $artist['id'] ?>" <?= strpos($songData['artist_names'], $artist['name']) !== false ? 'selected' : '' ?>><?= $artist['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <small>Hold Ctrl (Windows) or Command (Mac) to select multiple artists</small>
                    <input type="submit" name="update" class="button" value="Update Song">
                </form>
                <?php endif; ?>
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