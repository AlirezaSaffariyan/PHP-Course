<?php
include "../env.php";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userId = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM Users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user):
?>
    <img src="data:image/png;base64,<?php echo base64_encode($user['profile_picture']); ?>" 
         alt="Profile Picture" class="modal-profile-picture">
    <h2><?php echo htmlspecialchars(ucfirst($user['first_name']) . ' ' . ucfirst($user['last_name'])); ?></h2>
    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
    <p><strong>Region:</strong> <?php echo htmlspecialchars(ucfirst($user['region'])); ?></p>
    <p><strong>Date of Birth:</strong> <?php echo $user['date_of_birth']; ?></p>
    <p><strong>Registered:</strong> <?php echo $user['created_at']; ?></p>
<?php
else:
    echo "User not found.";
endif;

$stmt->close();
$conn->close();
?>