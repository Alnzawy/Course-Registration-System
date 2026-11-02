<?php 
include 'config.php'; 

// Set default order and filter values
$filterDepartment = isset($_GET['department']) ? $_GET['department'] : '';
$filterRoomType = isset($_GET['roomType']) ? $_GET['roomType'] : '';
$orderBy = isset($_GET['orderBy']) ? $_GET['orderBy'] : 'Room_ID';
$orderDir = isset($_GET['orderDir']) && $_GET['orderDir'] == 'desc' ? 'desc' : 'asc';

// Prepare SQL query with filters and ordering
$sql = "SELECT Room_ID, Room_name, Room_LabOrTheory, Size 
        FROM room 
        WHERE (Room_name LIKE '%$filterDepartment%' OR '$filterDepartment' = '') 
        AND (Room_LabOrTheory LIKE '%$filterRoomType%' OR '$filterRoomType' = '')
        ORDER BY $orderBy $orderDir";

$result = $conn->query($sql);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Style.css">
    
</head>

<body>

<?php include 'sidebar.php'; ?>
<script src="sidebar.js"></script>


<div class="main-content">
        <h1>Room List</h1>



        <!-- Room List Table -->
        <?php
        // Initialize row counter
        $counter = 0;

        // Check if any rooms exist
        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<tr>
                    <th>No.</th>
                    <th><a href='?orderBy=Room_ID&orderDir=" . ($orderBy == 'Room_ID' && $orderDir == 'asc' ? 'desc' : 'asc') . "'>Room ID</a></th>
                    <th><a href='?orderBy=Room_name&orderDir=" . ($orderBy == 'Room_name' && $orderDir == 'asc' ? 'desc' : 'asc') . "'>Room Name</a></th>
                    <th><a href='?orderBy=Room_LabOrTheory&orderDir=" . ($orderBy == 'Room_LabOrTheory' && $orderDir == 'asc' ? 'desc' : 'asc') . "'>Room Type</a></th>
                    <th><a href='?orderBy=Size&orderDir=" . ($orderBy == 'Size' && $orderDir == 'asc' ? 'desc' : 'asc') . "'>Size</a></th>
                  </tr>";
            
            // Fetch and display each room
            while ($row = $result->fetch_assoc()) {
                $counter++; // Increment the room counter
                
                echo "<tr>
                        <td>{$counter}</td>
                        <td>{$row['Room_ID']}</td>
                        <td>{$row['Room_name']}</td>
                        <td>{$row['Room_LabOrTheory']}</td>
                        <td>{$row['Size']}</td>
                      </tr>";
            }

            echo "</table>";
        } else {
            echo "<p>No rooms found.</p>";
        }

        // Close the database connection
        $conn->close();
        ?>
    </div>
</body>

</html>