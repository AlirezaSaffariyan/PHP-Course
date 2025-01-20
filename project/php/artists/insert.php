<?php
session_start();
include "../env.php";

function loginToSpotify() {
    include "../env.php";

    // The URL for the Spotify API token
    $url = "https://accounts.spotify.com/api/token";
    
    // Prepare the data to be sent
    $data = [
        'grant_type' => 'client_credentials',
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
    ];
    
    // Initialize a cURL session
    $ch = curl_init($url);
    
    // Set the necessary cURL options
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // This sets the POST fields
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
    
    // Execute the cURL request
    $response = curl_exec($ch);
    
    // Check for errors
    if (curl_errno($ch)) {
        $_SESSION['toast_message'] = 'cURL Error: ' . curl_error($ch);
        $_SESSION['toast_type'] = "error";
    } else {
        // Decode the JSON response
        $responseData = json_decode($response, true);
    }
    
    // Close the cURL session
    curl_close($ch);

    return $responseData['access_token'];
}

function getArtistSpotifyID($artistName, $accessToken) {
    $artistName = urlencode($artistName);

    $url = "https://api.spotify.com/v1/search?q=" . $artistName . "&type=artist";

    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
    ]);

    // Execute the cURL request
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        $_SESSION['toast_message'] = 'cURL Error: ' . curl_error($ch);
        $_SESSION['toast_type'] = "error";
    } else {
        // Decode the JSON response
        $data = json_decode($response, true);
    }

    // Close the cURL session
    curl_close($ch);

    if (isset($data['artists']['items'][0]['id'])) {
        return $data['artists']['items'][0]['id'];
    } else {
        return "Artist ID not found";
    }
}

function getArtist($artistID, $accessToken) {
    $artistName = urlencode($artistID);

    $url = "https://api.spotify.com/v1/artists/" . $artistID;

    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
    ]);

    // Execute the cURL request
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        $_SESSION['toast_message'] = 'cURL Error: ' . curl_error($ch);
        $_SESSION['toast_type'] = "error";
    } else {
        // Decode the JSON response
        $data = json_decode($response, true);
    }

    // Close the cURL session
    curl_close($ch);

    if (isset($data)) {
        return $data;
    } else {
        return "Artist not found";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $name = $_POST['name'];
    $access_token = loginToSpotify();
    $artist_id = getArtistSpotifyID($name, $access_token);
    $artist = getArtist($artist_id, $access_token);

    if ($_POST['biography']) {
        $biography = $_POST['biography'];
    } else {
        $biography = shell_exec('python3 ./get_artist_biography.py "' . $name . '"');
    }

    // Generate visual hash for profile picture using the artist name
    // $profile_picture = file_get_contents("https://robohash.org/{$name}.png");
    $profile_picture = file_get_contents($artist['images'][0]['url']);

    // Prepare and execute the insert statement
    $stmt = $conn->prepare("INSERT INTO Artists (name, biography, profile_picture, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("sss", $name, $biography, $profile_picture);
    
    try {
        if ($stmt->execute()) {
            $_SESSION['toast_message'] = "New artist created successfully.";
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
    <title>Insert Artist | Music Player</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="header">Music Player Artist Management</div>

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
                <h1>Insert New Artist</h1>
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="text" name="name" placeholder="Artist Name" required>
                    <textarea name="biography" placeholder="Artist Biography" rows="4"></textarea>
                    <input type="submit" class="button" value="Insert Artist">
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