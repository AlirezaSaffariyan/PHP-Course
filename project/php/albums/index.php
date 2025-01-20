<?php
// Define album management endpoints
$albumEndpoints = [
    'Insert Album' => [
        'link' => './insert.php',
        'description' => 'Create new album entries with comprehensive details',
        'icon' => '➕',
        'color' => '#2ecc71'
    ],
    'Update Album' => [
        'link' => './update.php',
        'description' => 'Modify existing album information',
        'icon' => '✏️',
        'color' => '#3498db'
    ],
    'Delete Album' => [
        'link' => './delete.php',
        'description' => 'Remove album entries from the system',
        'icon' => '🗑️',
        'color' => '#e74c3c'
    ],
    'View Albums' => [
        'link' => './read.php',
        'description' => 'Browse and search through all album entries',
        'icon' => '💿',
        'color' => '#f39c12'
    ]
];

// Optional: Add some quick album stats
function getAlbumStats() {
    include "../env.php";
    
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        return [
            'total_albums' => 'N/A',
            'newest_album' => 'N/A',
            'albums_with_songs' => 'N/A'
        ];
    }
    
    $stats = [
        'total_albums' => $conn->query("SELECT COUNT(*) as count FROM Albums")->fetch_assoc()['count'],
        'newest_album' => $conn->query("SELECT title FROM Albums ORDER BY release_date DESC, id DESC LIMIT 1")->fetch_assoc()['title'] ?? 'None',
        'albums_with_songs' => $conn->query("SELECT COUNT(DISTINCT album_id) as count FROM Songs")->fetch_assoc()['count']
    ];
    
    $conn->close();
    return $stats;
}

$albumStats = getAlbumStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Album Management Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --background-dark: #121212;
            --card-background: #1e1e1e;
            --text-color: #ffffff;
            --primary-color: #ffcc00;
            --hover-color: #ffd633;
            --secondary-color: #333;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-dark);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            height: 65vh;
        }

        .header {
            background-color: var(--primary-color);
            color: var(--background-dark);
            text-align: center;
            padding: 30px 0;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            font-family: 'Press Start 2P', cursive;
        }

        .user-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--secondary-color);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }

        .stat-card h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: bold;
        }

        .endpoints {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .endpoint-card {
            background-color: var(--card-background);
            border-radius: 12px;
            padding: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-left: 6px solid;
        }

        .endpoint-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }

        .endpoint-card .icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .endpoint-card h2 {
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .endpoint-card p {
            color: rgba(255,255,255,0.7);
            margin-bottom: 15px;
        }

        .endpoint-card a {
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 600;
            align-self: flex-start;
            transition: color 0.3s ease;
        }

        .endpoint-card a:hover {
            color: var(--hover-color);
        }

        .main-navigation {
            display: flex;
            justify-content: center;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .main-navigation a {
            text-decoration: none;
            color: var(--primary-color);
            background-color: var(--secondary-color);
            padding: 10px 20px;
            border-radius: 5px;
            transition: background-color 0.3s ease, color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .main-navigation a:hover {
            background-color: var(--primary-color);
            color: var(--background-dark);
        }

        .main-navigation a svg {
            width: 20px;
            height: 20px;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            background-color: var(--card-background);
            margin-top: 30px;
        }

        @media (max-width: 768px) {
            .user-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Album Management Dashboard</h1>
        <p>Comprehensive album management system</p>

        <div class="main-navigation">
            <a href="../index.php">
                <!-- SVG Back Arrow -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
                Back to Main Dashboard
            </a>
        </div>
    </div>

    <div class="container">
        <div class="user-stats">
            <div class="stat-card">
                <h3>Total Albums</h3>
                <div class="stat-value"><?php echo $albumStats['total_albums']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Newest Album</h3>
                <div class="stat-value"><?php echo $albumStats['newest_album']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Albums with Songs</h3>
                <div class="stat-value"><?php echo $albumStats['albums_with_songs']; ?></div>
            </div>
        </div>

        <div class="endpoints">
            <?php foreach ($albumEndpoints as $name => $endpoint): ?>
                <div class="endpoint-card" style="border-left-color: <?php echo $endpoint['color']; ?>;">
                    <div>
                        <div class="icon"><?php echo $endpoint['icon']; ?></div>
                        <h2><?php echo $name; ?></h2>
                        <p><?php echo $endpoint['description']; ?></p>
                    </div>
                    <a href="<?php echo $endpoint['link']; ?>">Go to <?php echo $name; ?> →</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="footer">© 2024 Music Player</div>
</body>
</html>
   