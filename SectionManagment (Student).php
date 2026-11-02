<?php
// Start session and include config
include('config.php');

// Retrieve the student ID from the session (ensure this is set when logging in)
$studentID = $_SESSION['user_id'];

// Query to get the student's department
$studentInfoQuery = "SELECT Student_department FROM student WHERE Student_ID = ?";
$stmt = $conn->prepare($studentInfoQuery);
$stmt->bind_param("i", $studentID);
$stmt->execute();
$stmt->bind_result($studentDepartment);
$stmt->fetch();
$stmt->close();

// Fetch the student's registered sections with full section information
$sectionInfoQuery = "
    SELECT sections.*
    FROM student_course_registration
    JOIN sections ON student_course_registration.Section_ID = sections.Section_ID
    WHERE student_course_registration.Student_ID = ?
";

$stmt = $conn->prepare($sectionInfoQuery);
$stmt->bind_param("i", $studentID);
$stmt->execute();
$registeredSections = $stmt->get_result();
$stmt->close();

// Initialize variables for tracking total theory credit hours
$totalTheoryCreditHours = 0;
$displayedCourses = []; // To keep track of courses that have been displayed
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Style.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    <script src="sidebar.js"></script>

    <div class="main-content">
        <h1>Student Dashboard</h1>

        <!-- Display Registered Sections -->
        <h2>Registered Sections</h2>
        <?php if ($registeredSections->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Section ID</th>
                    <th>Course ID</th>
                    <th>Course Name</th>
                    <th>Lab/Theory</th>
                    <th>Credit Hours</th>
                    <th>Doctor</th>
                    <th>Room</th>
                    <th>Time Slot</th>
                    <th>Level</th>
                </tr>
                <?php while ($row = $registeredSections->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['Section_ID']; ?></td>

                        <!-- Only display Course ID, Course Name, and Level once for each course -->
                        <?php if (!in_array($row['Course_ID'], $displayedCourses)): ?>
                            <td rowspan="2"><?php echo $row['Course_ID']; ?></td>
                            <td rowspan="2"><?php echo $row['Course_Name']; ?></td>
                            <td><?php echo $row['Course_LabOrTheory']; ?></td>
                            <td rowspan="2"><?php echo $row['Course_CreditHours']; ?></td>
                            <td><?php echo $row['Doctor_name']; ?></td>
                            <td><?php echo $row['Room_name']; ?></td>
                            <td><?php echo $row['TimeSlot']; ?></td>
                            <td rowspan="2"><?php echo $row['Level_Name']; ?></td>
                            <?php 
                                // Update total for Theory courses only
                                if ($row['Course_LabOrTheory'] == 'Theory') {
                                    $totalTheoryCreditHours += $row['Course_CreditHours'];
                                }
                                $displayedCourses[] = $row['Course_ID']; // Mark course as displayed
                            ?>
                        <?php else: ?>
                            <!-- Display Lab/Theory for the second row if it's a lab -->
                            <td><?php echo $row['Course_LabOrTheory']; ?></td>
                            <td><?php echo $row['Doctor_name']; ?></td>
                            <td><?php echo $row['Room_name']; ?></td>
                            <td><?php echo $row['TimeSlot']; ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            </table>

            <!-- Display Total Credit Hours for Theory sections -->
            <h3>Total Theory Credit Hours: <?php echo $totalTheoryCreditHours; ?></h3>

        <?php else: ?>
            <p>No registered sections found for this student.</p>
        <?php endif; ?>
    </div>
</body>
</html>
