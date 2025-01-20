<?php
// import db info
include "../env.php";

// open db connection
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo 'Connected successfully<br>
Initializing tables...<br>';

// function for checking result of table creation
function checkRes($result) {
    global $tableName, $conn;
    if ($result) {
        echo "Table \"{$tableName}\" has been created successfully!";
    } else {
        "Error creating table \"{$tableName}\":<br>" . $conn->error;
    }
    echo "<br>";
}

// Users
$tableName = 'Users';

$query = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(32) UNIQUE NOT NULL,
  email VARCHAR(254) UNIQUE NOT NULL,
  password VARCHAR(128) NOT NULL,
  first_name VARCHAR(254) NOT NULL,
  last_name VARCHAR(254) NOT NULL,
  date_of_birth DATE NOT NULL,
  profile_picture LONGBLOB NULL,  
  region VARCHAR(90) NOT NULL,
  created_at DATE NOT NULL,
  updated_at DATE NOT NULL
)";

$result = $conn->query($query);

checkRes($result);

// Artists
$tableName = 'Artists';

$query = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(32) NOT NULL,
  biography TEXT NULL,
  profile_picture LONGBLOB NULL,
  created_at DATE NOT NULL,
  updated_at DATE NOT NULL
)";

$result = $conn->query($query);

checkRes($result);

// Albums
$tableName = 'Albums';

$query = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  release_date DATE NOT NULL,
  cover_image LONGBLOB NULL,
  artist_id INT NOT NULL,
  created_at DATE NOT NULL,
  updated_at DATE NOT NULL,
  FOREIGN KEY (artist_id) REFERENCES Artists (id)
)";

$result = $conn->query($query);

checkRes($result);

// Songs
$tableName = 'Songs';

$query = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  duration INT NOT NULL,
  genre_id INT NOT NULL,
  album_id INT NOT NULL,
  track_number INT NOT NULL,
  disc_number INT NOT NULL,
  file LONGBLOB NOT NULL,
  created_at DATE NOT NULL,
  updated_at DATE NOT NULL,
  FOREIGN KEY (album_id) REFERENCES Albums (id)
)";

$result = $conn->query($query);

checkRes($result);

// SongArtists
$tableName = 'SongArtists';

$query = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
  song_id INT NOT NULL,
  artist_id INT NOT NULL,
  PRIMARY KEY (song_id, artist_id),
  FOREIGN KEY (song_id) REFERENCES Songs (id),
  FOREIGN KEY (artist_id) REFERENCES Artists (id)
)";

$result = $conn->query($query);

checkRes($result);

// Genres
$tableName = 'Genres';

$query = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
  id INT AUTO_INCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL,
  created_at DATE NOT NULL,
  updated_at DATE NOT NULL
)";

$result = $conn->query($query);

checkRes($result);

// close db connection
$conn->close();