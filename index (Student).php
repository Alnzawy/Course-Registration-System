<?php
// Start session and include config
include('config.php');

 
$sql = "SELECT Student_name,Student_ID, Student_department, Student_Level, Total_CreditHours_Registered FROM student WHERE Student_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student_data = $result->fetch_assoc();

// Check if student data exists
if (!$student_data) {
    echo "Error: No data found for this student.";
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
    <title>Student Dashboard with Calendar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
            color: #333;
            display: flex;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: #111827;
            color: #ffffff;
            height: 100vh;
            padding: 20px;
            position: fixed;
            overflow-y: auto;
        }

        .sidebar h2 {
            color: #ffffff;
            margin-bottom: 30px;
            font-size: 22px;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }

        .sidebar ul li {
            margin: 15px 0;
        }

        .sidebar ul li a {
            color: #ffffff;
            text-decoration: none;
            font-size: 16px;
            display: flex;
            align-items: center;
        }

        .sidebar ul li a i {
            margin-right: 10px;
        }

        .sidebar ul li a:hover {
            color: #4f46e5;
        }

        .sidebar ul li a .dropdown-arrow {
            margin-left: auto;
        }

        .sidebar .submenu {
            display: none;
            list-style-type: none;
            padding-left: 20px;
        }

        .sidebar ul li.active>.submenu {
            display: block;
        }

        .submenu li {
            margin: 10px 0;
        }

        .submenu li a {
            font-size: 14px;
            color: #a0aec0;
        }

        .submenu li a:hover {
            color: #4f46e5;
        }

        /* Dashboard Styles */
        .dashboard-container {
            margin-left: 270px;
            padding: 40px;
            width: calc(100% - 270px);
        }

        .dashboard-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-around;
            margin-bottom: 50px;
        }

        .card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            width: 250px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.2);
        }

        .card .icon {
            font-size: 30px;
            color: #4f46e5;
            margin-bottom: 15px;
        }

        .card .number {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .card .title {
            font-size: 18px;
            font-weight: 600;
            color: #666;
        }

        .info-box {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }

        .info-box div {
            background-color: #fff;
            border-radius: 10px;
            padding: 15px;
            width: 250px;
            text-align: center;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .info-box h3 {
            margin-bottom: 10px;
            font-size: 18px;
            font-weight: 600;
        }

        .info-box p {
            font-size: 16px;
            font-weight: bold;
        }

        /* Calendar Styles */
        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            margin-top: 40px;
        }

        .calendar .day {
            padding: 15px;
            background-color: #fff;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .calendar .day.current-day {
            background-color: #4f46e5;
            color: white;
        }

        .calendar .day:hover {
            background-color: #4f46e5;
            color: white;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
<script src="sidebar.js"></script>

    <!-- Dashboard Section -->
    <div class="dashboard-container" id="dashboard">
    <h1>Welcome, <?php echo htmlspecialchars($student_data['Student_name']); ?></h1>
    <br>

    <div class="dashboard-cards">
        <div class="card">
            <div class="icon"><i class="fas fa-id-badge"></i></div>
            <div class="title">Student ID</div>
            <div class="number"><?php echo htmlspecialchars($student_data['Student_ID']); ?></div>
        </div>
        <div class="card">
            <div class="icon"><i class="fas fa-university"></i></div>
            <div class="number"><?php echo htmlspecialchars($student_data['Student_department']); ?></div>
            <div class="title">Department</div>
        </div>
        <div class="card">
            <div class="icon"><i class="fas fa-graduation-cap"></i></div>
            <div class="number"><?php echo htmlspecialchars($student_data['Student_Level']); ?></div>
            <div class="title">Level</div>
        </div>
        
    </div>
</div>



</body>

</html>
