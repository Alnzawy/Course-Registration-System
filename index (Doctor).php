<?php
// Include config file for session and database connection
include('config.php');

// Retrieve the Teacher_ID (previously Doctor_ID) from the session
$teacher_id = $_SESSION['user_id']; // Assuming 'user_id' holds the Teacher's ID

// Prepare SQL statement to retrieve teacher data
$sql = "SELECT Doctor_name AS Teacher_name, Doctor_department AS Teacher_department, Weekly_Teaching_Hours FROM doctor WHERE Doctor_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);

// Execute and fetch results
$stmt->execute();
$result = $stmt->get_result();
$teacher_data = $result->fetch_assoc();

// Check if teacher data exists
if (!$teacher_data) {
    echo "Error: No data found for this Teacher.";
    exit();
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard with Calendar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Style.css">
</head>

<body>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    <script src="sidebar.js"></script>

    <!-- Dashboard Section -->
    <div class="dashboard-container" id="dashboard">
        <h1>Welcome, <?php echo htmlspecialchars($teacher_data['Teacher_name']); ?>!</h1>

        <div class="dashboard-cards">
          
        
            <div class="card">
                <div class="icon"><i class="fas fa-book"></i></div>
                <div class="number">              <p><?php echo htmlspecialchars($teacher_data['Teacher_department']); ?></p>
                </div>
                <div class="title">Department</div>
                
            </div>
            <div class="card">
                <div class="icon"><i class="fas fa-graduation-cap"></i></div>
                <div class="number"><?php echo htmlspecialchars($teacher_data['Weekly_Teaching_Hours']); ?></div>
                <div class="title">Weekly Teaching Hours</div>
            </div>
        </div>

       

</body>

</html>
