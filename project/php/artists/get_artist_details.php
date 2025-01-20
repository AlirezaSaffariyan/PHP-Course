<?php
include "../env.php";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$artistId = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM Artists WHERE id = ?");
$stmt->bind_param("i", $artistId);
$stmt->execute();
$result = $stmt->get_result();
$artist = $result->fetch_assoc();

if ($artist):
?>

    <img src="data:image/png;base64,<?php echo base64_encode($artist['profile_picture']); ?>"
        alt="Profile Picture" class="modal-profile-picture">
    <h1><?php
    $artistName = explode(" ", $artist['name']);
    $artistName = array_map('ucfirst', $artistName);
    $artistName = implode(" ", $artistName);
    echo htmlspecialchars($artistName);
    ?></h1>
    <p class="biography"><strong>Biography:</strong> <q><?php echo nl2br(htmlspecialchars($artist['biography'])); ?></q></p>
    <p><strong>Created At:</strong> <?php echo $artist['created_at']; ?></p>
    <p><strong>Updated At:</strong> <?php echo $artist['updated_at']; ?></p>
<?php
else:
    echo "Artist not found.";
endif;

$stmt->close();
$conn->close();
?>