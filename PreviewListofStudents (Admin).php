<?php 
// Include configuration for database connection

// Database connection
$servername = "localhost";
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$dbname = "CRS"; // Replace with your actual database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set default order and filter values
$orderBy = isset($_GET['orderBy']) ? $_GET['orderBy'] : 'Student_ID';
$orderDir = isset($_GET['orderDir']) && $_GET['orderDir'] == 'desc' ? 'desc' : 'asc';

// Prepare SQL query to get student data
$sql = "SELECT Student_ID, Student_name, Student_department, Student_Level, Total_CreditHours_Registered 
        FROM student 
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
    <h1>Student List</h1>

    <!-- Student List Table -->
    <?php
    // Initialize row counter
    $counter = 0;

    // Check if any students exist
    if ($result->num_rows > 0) {
        echo "<table>";
        echo "<tr>
                <th>No.</th>
                <th><a href='?orderBy=Student_ID&orderDir=" . ($orderBy == 'Student_ID' && $orderDir == 'asc' ? 'desc' : 'asc') . "'>Student ID</a></th>
                <th><a href='?orderBy=Student_name&orderDir=" . ($orderBy == 'Student_name' && $orderDir == 'asc' ? 'desc' : 'asc') . "'>Student Name</a></th>
                <th><a href='?orderBy=Student_department&orderDir=" . ($orderBy == 'Student_department' && $orderDir == 'asc' ? 'desc' : 'asc') . "'>Department</a></th>
                <th><a href='?orderBy=Student_Level&orderDir=" . ($orderBy == 'Student_Level' && $orderDir == 'asc' ? 'desc' : 'asc') . "'>Level</a></th>
              </tr>";

        // Fetch and display each student
        while ($row = $result->fetch_assoc()) {
            $counter++; // Increment the student counter
            
            echo "<tr>
                    <td>{$counter}</td>
                    <td>{$row['Student_ID']}</td>
                    <td>{$row['Student_name']}</td>
                    <td>{$row['Student_department']}</td>
                    <td>{$row['Student_Level']}</td>
                  </tr>";
        }

        echo "</table>";
    } else {
        echo "<p>No students found.</p>";
    }

    // Close the database connection
    $conn->close();
    ?>
</div>




</body>

</html>
