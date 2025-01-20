<?php
include "../env.php";

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['artist_id'])) {
    header('Content-Type: application/json'); // Ensure response is JSON

    $artistId = intval($_GET['artist_id']);
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
        exit();
    }

    $albums = []; // Initialize the albums array
    $result = $conn->query("SELECT id, title FROM Albums WHERE artist_id = " . $artistId); // Simplified query
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $albums[] = $row;
        }
    }

    if (empty($albums)) {
        echo json_encode(['error' => 'No albums found']);
    } else {
        echo json_encode($albums);
    }

    $conn->close();
    exit(); // Ensure no further output
} else {
    echo json_encode(['error' => 'Invalid request or missing artist_id']);
    exit(); // Ensure no further output
}
