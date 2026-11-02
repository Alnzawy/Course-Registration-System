<?php 
include('config.php');
?>

<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="Style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
</head>

<body>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    <script src="sidebar.js"></script>

    <!-- Dashboard Section -->
    <div class="dashboard-container" id="dashboard">
        <h1>Admin Dashboard Overview</h1>
        <br>
        <div class="dashboard-cards">
            <!-- Number of Students -->
            <div class="card">
                <div class="icon"><i class="fas fa-user-graduate"></i></div>
                <div class="number">
                    <?php
                    $studentCountResult = $conn->query("SELECT COUNT(*) as studentCount FROM student");
                    $studentCount = $studentCountResult->fetch_assoc()['studentCount'];
                    echo $studentCount;
                    ?>
                </div>
                <div class="title">Students</div>
            </div>

            <!-- Number of Rooms -->
            <div class="card">
                <div class="icon"><i class="fas fa-door-closed"></i></div>
                <div class="number">
                    <?php
                    $roomCountResult = $conn->query("SELECT COUNT(*) as roomCount FROM room");
                    $roomCount = $roomCountResult->fetch_assoc()['roomCount'];
                    echo $roomCount;
                    ?>
                </div>
                <div class="title">Rooms</div>
                <div class="number">
                    Labs: 
                    <?php
                    $labRoomResult = $conn->query("SELECT COUNT(*) as labCount FROM room WHERE Room_LabOrTheory = 'Lab'");
                    $labCount = $labRoomResult->fetch_assoc()['labCount'];
                    echo $labCount;
                    ?> 
                     Theory: 
                    <?php
                    $theoryRoomResult = $conn->query("SELECT COUNT(*) as theoryCount FROM room WHERE Room_LabOrTheory = 'Theory'");
                    $theoryCount = $theoryRoomResult->fetch_assoc()['theoryCount'];
                    echo $theoryCount;
                    ?>
                </div>
            </div>

            

            <!-- Number of Doctors -->
            <div class="card">
                <div class="icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="number">
                    <?php
                    $doctorCountResult = $conn->query("SELECT COUNT(*) as doctorCount FROM doctor");
                    $doctorCount = $doctorCountResult->fetch_assoc()['doctorCount'];
                    echo $doctorCount;
                    ?>
                </div>
                <div class="title">Doctors</div>
            </div>

            <!-- Total Number of Courses -->
            <div class="card">
                <div class="icon"><i class="fas fa-book"></i></div>
                <div class="number">
                    <?php
                    $courseCountResult = $conn->query("SELECT COUNT(*) as courseCount FROM course WHERE Course_LabOrTheory = 'Theory'");
                    $courseCount = $courseCountResult->fetch_assoc()['courseCount'];
                    echo $courseCount;
                    ?>
                </div>
                <div class="title">Courses</div>
            </div>
        </div>
        

        


</body>

</html>


