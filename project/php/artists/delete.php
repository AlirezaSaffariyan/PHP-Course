<?php
session_start();
include "../env.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $artistId = intval($_POST['id']);

    $stmt = $conn->prepare("DELETE FROM Artists WHERE id = ?");
    $stmt->bind_param("i", $artistId);
    
    try {
        if ($stmt->execute()) {
            $_SESSION['toast_message'] = "Artist deleted successfully.";
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

$result = $conn->query("SELECT * FROM Artists");
while ($row = $result->fetch_assoc()) {
    $artists[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Artist | Music Player</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="header">Delete Artist</div>

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
                <h1>Select Artist to Delete</h1>
                <form action="" method="post">
                    <select name="id" required>
                        <option value="">Select an artist</option>
                        <?php foreach ($artists as $artist): ?>
                            <option value="<?php echo $artist['id']; ?>"><?php echo htmlspecialchars($artist['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" class="button" value="Delete Artist">
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