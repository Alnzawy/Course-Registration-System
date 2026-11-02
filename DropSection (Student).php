<?php
// Start session and include config
include('config.php');

// Retrieve the student ID from the session
$studentID = $_SESSION['user_id'];

// Fetch the student’s current level and department
$currentLevelQuery = "
    SELECT Student_Level, Student_department 
    FROM student 
    WHERE Student_ID = ?
";
$stmt = $conn->prepare($currentLevelQuery);
$stmt->bind_param("i", $studentID);
$stmt->execute();
$stmt->bind_result($studentLevel, $studentDepartment);
$stmt->fetch();
$stmt->close();

// Set the expected course level format based on student's current level and department
$expectedCourseLevel = "Level " . $studentLevel . " " . $studentDepartment;

// Drop section functionality: remove both theory and lab sections for the selected course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['drop_section'])) {
    $sectionID = $_POST['section_id'];

    // Find the Course ID associated with the given Section ID
    $findCourseQuery = "
        SELECT Course_ID FROM sections 
        WHERE Section_ID = ?
    ";
    $stmt = $conn->prepare($findCourseQuery);
    $stmt->bind_param("i", $sectionID);
    $stmt->execute();
    $stmt->bind_result($courseID);
    $stmt->fetch();
    $stmt->close();

    if ($courseID) {
        // Delete both lab and theory sections for this course in student_course_registration
        $deleteSectionsQuery = "
            DELETE FROM student_course_registration 
            WHERE Student_ID = ? AND Course_ID = ?
        ";
        $stmt = $conn->prepare($deleteSectionsQuery);
        $stmt->bind_param("ii", $studentID, $courseID);
        $stmt->execute();
        $stmt->close();

        // Display success message
        echo "<div class='message success'>Sections for Course ID $courseID have been dropped successfully.</div>";
    }
}

// Fetch registered sections only for courses above the student's current level
$sectionInfoQuery = "
    SELECT s.Section_ID, s.Course_ID, s.Course_Name, s.Course_LabOrTheory, s.Course_CreditHours, 
           s.Doctor_name, s.Room_name, s.TimeSlot, s.Level_Name 
    FROM student_course_registration scr
    JOIN sections s ON scr.Section_ID = s.Section_ID
    WHERE scr.Student_ID = ?
    AND s.Level_Name > ?
";
$stmt = $conn->prepare($sectionInfoQuery);
$stmt->bind_param("is", $studentID, $expectedCourseLevel);
$stmt->execute();
$registeredSections = $stmt->get_result();
$stmt->close();

// Initialize variables for tracking total theory credit hours
$totalTheoryCreditHours = 0;
$displayedCourses = []; // Track displayed courses
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
    <style>
        .drop-button {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .drop-button:hover {
            background-color: #c0392b;
        }
        .message {
            text-align: center;
            padding: 10px;
            margin: 20px auto;
            width: 80%;
            border-radius: 5px;
            font-size: 16px;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    <script src="sidebar.js"></script>

    <div class="main-content">
        <h1>Student Dashboard</h1>

        <!-- Display Registered Sections -->
        <h2>Registered Sections (Above Current Level)</h2>
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
                    <th>Action</th>
                </tr>
                <?php while ($row = $registeredSections->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['Section_ID']; ?></td>
                        <?php if (!in_array($row['Course_ID'], $displayedCourses)): ?>
                            <td rowspan="2"><?php echo $row['Course_ID']; ?></td>
                            <td rowspan="2"><?php echo $row['Course_Name']; ?></td>
                            <td><?php echo $row['Course_LabOrTheory']; ?></td>
                            <td rowspan="2"><?php echo $row['Course_CreditHours']; ?></td>
                            <td><?php echo $row['Doctor_name']; ?></td>
                            <td><?php echo $row['Room_name']; ?></td>
                            <td><?php echo $row['TimeSlot']; ?></td>
                            <td rowspan="2"><?php echo $row['Level_Name']; ?></td>
                            <td rowspan="2">
                                <form method="POST" action="">
                                    <input type="hidden" name="section_id" value="<?php echo $row['Section_ID']; ?>">
                                    <button type="submit" name="drop_section" class="drop-button">Drop Section</button>
                                </form>
                            </td>
                            <?php 
                                if ($row['Course_LabOrTheory'] == 'Theory') {
                                    $totalTheoryCreditHours += $row['Course_CreditHours'];
                                }
                                $displayedCourses[] = $row['Course_ID'];
                            ?>
                        <?php else: ?>
                            <td><?php echo $row['Course_LabOrTheory']; ?></td>
                            <td><?php echo $row['Doctor_name']; ?></td>
                            <td><?php echo $row['Room_name']; ?></td>
                            <td><?php echo $row['TimeSlot']; ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            </table>
            <h3>Total Theory Credit Hours: <?php echo $totalTheoryCreditHours; ?></h3>
        <?php else: ?>
            <p>No registered sections found above your current level.</p>
        <?php endif; ?>
    </div>
</body>
</html>
