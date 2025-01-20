<?php
// Define an array of available endpoints with additional metadata
$endpoints = [
    'Users' => [
        'link' => './users/index.php',
        'description' => 'Manage user accounts, profiles, and authentication',
        'icon' => '👤',
        'color' => '#ffcc00'
    ],
    'Artists' => [
        'link' => './artists/index.php',
        'description' => 'Create and manage artist profiles and information',
        'icon' => '🎤',
        'color' => '#ff6b6b'
    ],
    'Albums' => [
        'link' => './albums/index.php',
        'description' => 'Organize and track music albums',
        'icon' => '💿',
        'color' => '#4ecdc4'
    ],
    'Songs' => [
        'link' => './songs/index.php',
        'description' => 'Manage individual music tracks',
        'icon' => '🎵',
        'color' => '#45b7d1'
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Music Player Management Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --background-dark: #121212;
            --card-background: #1e1e1e;
            --text-color: #ffffff;
            --primary-color: #ffcc00;
            --hover-color: #ffd633;
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
            height: 70vh;
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
        }

        .header p {
            margin-top: 10px;
            color: rgba(0,0,0,0.7);
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

        .footer {
            text-align: center;
            padding: 20px;
            background-color: var(--card-background);
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Music Player Management Dashboard</h1>
        <p>Comprehensive management system for your music application</p>
    </div>

    <div class="container">
        <div class="endpoints">
            <?php foreach ($endpoints as $name => $endpoint): ?>
                <div class="endpoint-card" style="border-left-color: <?php echo $endpoint['color']; ?>;">
                    <div>
                        <div class="icon"><?php echo $endpoint['icon']; ?></div>
                        <h2><?php echo $name; ?></h2>
                        <p><?php echo $endpoint['description']; ?></p>
                    </div>
                    <a href="<?php echo $endpoint['link']; ?>">Manage <?php echo $name; ?> →</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="footer">© 2024 Music Player</div>
</body>
</html>