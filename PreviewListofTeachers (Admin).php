<?php 
// Include configuration for database connection
include('config.php');

// Set default order and filter values
$filterDepartment = isset($_GET['department']) ? $_GET['department'] : '';
$orderBy = isset($_GET['orderBy']) ? $_GET['orderBy'] : 'Doctor_ID';
$orderDir = isset($_GET['orderDir']) && $_GET['orderDir'] == 'desc' ? 'desc' : 'asc';

// Prepare SQL query with filters and ordering
$sql = "SELECT Doctor_ID, Doctor_name, Doctor_department, Weekly_Teaching_Hours 
        FROM doctor 
        WHERE (Doctor_department LIKE '%$filterDepartment%' OR '$filterDepartment' = '')
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

    <!-- Main Content -->
    <div class="main-content">
        <h1>Doctor List</h1>

 

        <!-- Doctor List Table -->
        <?php
        // Initialize row counter and total teaching hours
        $counter = 0;
        $totalTeachingHours = 0;

        // Check if any doctors exist
        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<tr>
                    <th>No.</th>
                    <th><a href='?orderBy=Doctor_ID&orderDir=" . ($orderBy == 'Doctor_ID' && $orderDir == 'asc' ? 'desc' : 'asc') . "'>Doctor ID</a></th>
                    <th><a href='?orderBy=Doctor_name&orderDir=" . ($orderBy == 'Doctor_name' && $orderDir == 'asc' ? 'desc' : 'asc') . "'>Doctor Name</a></th>
                    <th><a href='?orderBy=Doctor_department&orderDir=" . ($orderBy == 'Doctor_department' && $orderDir == 'asc' ? 'desc' : 'asc') . "'>Department</a></th>
                    <th><a href='?orderBy=Weekly_Teaching_Hours&orderDir=" . ($orderBy == 'Weekly_Teaching_Hours' && $orderDir == 'asc' ? 'desc' : 'asc') . "'>Weekly Teaching Hours</a></th>
                  </tr>";
            
            // Fetch and display each doctor
            while ($row = $result->fetch_assoc()) {
                $counter++; // Increment the doctor counter
                $totalTeachingHours += $row['Weekly_Teaching_Hours']; // Sum up the weekly teaching hours
                
                echo "<tr>
                        <td>{$counter}</td>
                        <td>{$row['Doctor_ID']}</td>
                        <td>{$row['Doctor_name']}</td>
                        <td>{$row['Doctor_department']}</td>
                        <td>{$row['Weekly_Teaching_Hours']}</td>
                      </tr>";
            }

            // Add the total row
            echo "<tr>
                    <td colspan='4' style='font-weight:bold;'>Total</td>
                    <td style='font-weight:bold;'>{$totalTeachingHours}</td> <!-- Total teaching hours -->
                  </tr>";

            echo "</table>";
        } else {
            echo "<p>No doctors found.</p>";
        }

        // Close the database connection
        $conn->close();
        ?>
    </div>

</body>
</html>
