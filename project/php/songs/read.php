<?php
include "../env.php";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// **Pagination Setup**
$resultsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $resultsPerPage;

// **Search and Filter Parameters**
$searchQuery = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sortBy = isset($_GET['sort']) ? $conn->real_escape_string($_GET['sort']) : 'id';
$sortOrder = isset($_GET['order']) && in_array($_GET['order'], ['ASC', 'DESC']) ? $_GET['order'] : 'ASC';

// **Dynamic SQL Query Construction**
$whereClause = '';
if (!empty($searchQuery)) {
    $whereClause = "WHERE Songs.title LIKE '%{$searchQuery}%' 
                    OR Artists.name LIKE '%{$searchQuery}%'";
}

// **Count Total Results**
$totalResultsQuery = "SELECT COUNT(DISTINCT Songs.id) as total 
                      FROM Songs 
                      JOIN SongArtists ON Songs.id = SongArtists.song_id
                      JOIN Artists ON SongArtists.artist_id = Artists.id 
                      {$whereClause}";
$totalResultsResult = $conn->query($totalResultsQuery);
$totalResults = $totalResultsResult->fetch_assoc()['total'];
$totalPages = ceil($totalResults / $resultsPerPage);

// **Main Query with Pagination and Sorting**
$sql = "SELECT Songs.*, 
               GROUP_CONCAT(DISTINCT Artists.name SEPARATOR ', ') AS artist_names,
               Albums.title AS album_title
        FROM Songs 
        JOIN SongArtists ON Songs.id = SongArtists.song_id
        JOIN Artists ON SongArtists.artist_id = Artists.id
        LEFT JOIN Albums ON Songs.album_id = Albums.id
        {$whereClause} 
        GROUP BY Songs.id
        ORDER BY {$sortBy} {$sortOrder} 
        LIMIT {$resultsPerPage} OFFSET {$offset}";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Song Management | Music Player</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
    <style>
        .card {
            width: 100%;
            max-width: 1200px; /* Increased from 500px to 1200px */
            padding: 30px;
            overflow-x: auto; /* Added horizontal scroll if needed */
        }

        /* Enhanced Table Styles */
        .table-container {
            display: flex;
            justify-content: center;
            margin: 20px 0; /* Optional: Add some margin for spacing */
        }

        .advanced-table {
            width: 100%;
            overflow-x: auto;
            max-width: 1200px; /* Optional: Set a max width for the table */
        }

        .advanced-table table {
            width: 100%;
            min-width: 1000px; /* Increased minimum width */
            border-collapse: collapse;
        }

        .advanced-table thead {
            background-color: var(--primary-color);
            color: var(--background-dark);
        }

        .advanced-table th, 
        .advanced-table td {
            padding: 12px;
            text-align: center;
            border: 1px solid #333;
            white-space: nowrap; /* Prevents text wrapping */
            overflow: hidden;
            text-overflow: ellipsis; /* Adds ... if text is too long */
            max-width: 250px; /* Limits column width */
        }


        .advanced-table th {
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .advanced-table th:hover {
            background-color: rgba(255,204,0,0.8);
        }

        .search-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-container form {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 10px;
            width: 100%;
            max-width: 800px;
        }

        .search-container input, 
        .search-container select {
            flex-grow: 1;
            max-width: 200px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination a, .pagination span {
            padding: 10px 15px;
            margin: 0 5px;
            border: 1px solid var(--card-background);
            text-decoration: none;
            color: var(--text-color);
            transition: background-color 0.3s ease;
        }

        .pagination .current {
            background-color: var(--primary-color);
            color: var(--background-dark);
            border-radius: 9em;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .search-container form {
                flex-direction: column;
                align-items: stretch;
            }

            .search-container input, 
            .search-container select {
                max-width: none;
            }
        }

        @media (max-width: 1400px) {
            .card {
                width: 95%;
                margin: 0 auto;
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .card {
                padding: 15px;
                width: 100%;
            }
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.7);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: var(--card-background);
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            text-align: center;
        }

        .modal-close {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: var(--primary-color);
        }

        .modal-cover-art {
            width: 200px;
            height: 200px;
            border-radius: 40px;
            border: 4px solid var(--primary-color);
            display: block;
            margin: 0 auto 20px;
            padding: auto;
        }

        .sortable-header {
            cursor: pointer;
            user-select: none;
            transition: background-color 0.3s ease;
        }

        .sortable-header:hover {
            background-color: rgba(255,204,0,0.2);
        }
    </style>
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
                <h1>List Songs</h1>
                
                <!-- **Search and Filter Section** -->
                <div class="search-container">
                    <form action="" method="get" style="display: flex; gap: 10px;">
                        <input type="text" name="search" placeholder="Search songs..." 
                               value="<?php echo htmlspecialchars($searchQuery); ?>">
                        <select name="sort">
                            <option value="id" <?php echo $sortBy == 'id' ? 'selected' : ''; ?>>Sort by ID</option>
                            <option value="title" <?php echo $sortBy == 'title' ? 'selected' : ''; ?>>Song Title</option>
                            <option value="duration" <?php echo $sortBy == 'duration' ? 'selected' : ''; ?>>Duration</option>
                            <option value="disc_number" <?php echo $sortBy == 'disc_number' ? 'selected' : ''; ?>>Disc Number</option>
                            <option value="track_number" <?php echo $sortBy == 'track_number' ? 'selected' : ''; ?>>Track Number</option>
                        </select>
                        <select name="order">
                            <option value="ASC" <?php echo $sortOrder == 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                            <option value="DESC" <?php echo $sortOrder == 'DESC' ? 'selected' : ''; ?>>Descending</option>
                        </select>
                        <input type="submit" value="Apply" class="button">
                    </form>
                </div>

                <!-- **Song Table** -->
                <?php if ($result->num_rows > 0): ?>
                    <div class="table-container">
                        <table class="advanced-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th data-column="id">ID</th>
                                    <th data-column="title">Song Title</th>
                                    <th>Artists</th>
                                    <th>Album</th>
                                    <th data-column="duration">Duration</th>
                                    <th data-column="disc_number">Disc Number</th>
                                    <th data-column="track_number">Track Number</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $counter = 1 ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $counter; ?></td>
                                    <td><?php echo $row['id']; ?></td>
                                    <td onclick="openSongDetailsModal(<?php echo $row['id']; ?>)" style="cursor: pointer;">
                                        <?php
                                        $songTitle = explode(" ", $row['title']);
                                        $songTitle = array_map('ucfirst', $songTitle);
                                        $songTitle = implode(" ", $songTitle);
                                        echo htmlspecialchars($songTitle);
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['artist_names']); ?></td>
                                    <td><?php echo htmlspecialchars($row['album_title'] ?? 'N/A'); ?></td>
                                    <td><?php echo gmdate("i:s", $row['duration']); ?></td>
                                    <td><?php echo $row['disc_number']; ?></td>
                                    <td><?php echo $row['track_number']; ?></td>
                                </tr>
                                <?php $counter += 1 ?>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- **Pagination Section** -->
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchQuery); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>" 
                               class="<?php echo $i === $page ? 'current' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>

                <?php else: ?>
                    <p>No songs found.</p>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <div class="footer">© 2024 Music Player</div>

    <div id="songDetailsModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <div id="modalSongDetails"></div>
        </div>
    </div>

    <script>
        // Function to open album details modal
        function openSongDetailsModal(songId) {
            fetch(`./songDetails.php?id=${songId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('modalSongDetails').innerHTML = data;
                    document.getElementById('songDetailsModal').style.display = 'flex';
                })
                .catch(error => console.error('Error:', error));
        }

        // Function to close the modal
        function closeModal() {
            document.getElementById('songDetailsModal').style.display = 'none';
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('songDetailsModal');
            const modalContent = document.querySelector('.modal-content');

            // Close modal when clicking outside the modal content
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            // Prevent clicks inside the modal content from closing the modal
            modalContent.addEventListener('click', (event) => {
                event.stopPropagation();
            });

            // Close modal with Escape key
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });

            // Sorting functionality
            const headers = document.querySelectorAll('th');
            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const columnName = header.getAttribute('data-column');
                    
                    if (columnName) {
                        const currentUrl = new URL(window.location);
                        
                        const currentSort = currentUrl.searchParams.get('sort');
                        const currentOrder = currentUrl.searchParams.get('order');

                        let newOrder = 'ASC';
                        if (currentSort === columnName && currentOrder === 'ASC') {
                            newOrder = 'DESC';
                        }

                        currentUrl.searchParams.set('sort', columnName);
                        currentUrl.searchParams.set('order', newOrder);

                        window.location.href = currentUrl.toString();
                    }
                });
            });
        });

        // Function to handle sorting
        function sortTable(columnName) {
            const currentUrl = new URL(window.location);
            const currentSort = currentUrl.searchParams.get('sort');
            const currentOrder = currentUrl.searchParams.get('order');

            // Determine new sort order
            let newOrder = 'ASC';
            if (currentSort === columnName && currentOrder === 'ASC') {
                newOrder = 'DESC';
            }

            // Update URL parameters
            currentUrl.searchParams.set('sort', columnName);
            currentUrl.searchParams.set('order', newOrder);

            // Redirect to the new URL
            window.location.href = currentUrl.toString();
        }

        // Add click event to table headers
        document.addEventListener('DOMContentLoaded', () => {
            const headers = document.querySelectorAll('.sortable-header');
            headers.forEach(header => {
                header.addEventListener('click', () => {
                    sortTable(header.dataset.column);
                });
            });
        });
    </script>
</body>
</html>

<?php
// Modify the table headers to be sortable
echo '<thead>
    <tr>
        <th class="sortable-header" data-column="id">ID</th>
        <th class="sortable-header" data-column="name">Song Title</th>
        <th>Artists</th>
        <th>Album</th>
        <th class="sortable-header" data-column="duration">Duration</th>
        <th class="sortable-header" data-column="disc_number">Disc Number</th>
        <th class="sortable-header" data-column="track_number">Track Number</th>
    </tr>
</thead>';

$conn->close();
?>