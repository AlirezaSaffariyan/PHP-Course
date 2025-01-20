<?php
include "../env.php";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$album_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT al.*, ar.name, COUNT(s.id) AS songs_count
    FROM Albums AS al
    INNER JOIN Artists AS ar
    ON al.artist_id = ar.id
    LEFT JOIN Songs AS s
    ON al.id = s.album_id
    WHERE al.id = ?
    GROUP BY al.id, ar.name");
$stmt->bind_param("i", $album_id);
$stmt->execute();
$result = $stmt->get_result();
$album = $result->fetch_assoc();

if ($album):
?>

    <img src="data:image/png;base64,<?php echo base64_encode($album['cover_image']); ?>"
        alt="Cover Art" class="modal-cover-art">
    <h1><?php
    echo htmlspecialchars($album['title']);
    ?></h1>
    <h2><?php
    $artistName = explode(" ", $album['name']);
    $artistName = array_map('ucfirst', $artistName);
    $artistName = implode(" ", $artistName);
    echo htmlspecialchars($artistName);
    ?></h2>
    <p><strong>Tracks:</strong> <?php echo $album['songs_count']; ?></p>
    <p><strong>Release Date:</strong> <?php echo $album['release_date']; ?></p>
    <p><strong>Created At:</strong> <?php echo $album['created_at']; ?></p>
    <p><strong>Updated At:</strong> <?php echo $album['updated_at']; ?></p>
<?php
else:
    echo "Album not found.";
endif;

$stmt->close();
$conn->close();
?>