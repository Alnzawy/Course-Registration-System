<?php
// Start session and include config
include('config.php');

// Retrieve the student ID from the session
$studentID = $_SESSION['user_id'];

// Query to get the student's department
$studentInfoQuery = "SELECT Student_department FROM student WHERE Student_ID = ?";
$stmt = $conn->prepare($studentInfoQuery);
$stmt->bind_param("i", $studentID);
$stmt->execute();
$stmt->bind_result($studentDepartment);
$stmt->fetch();
$stmt->close();

// Fetch completed courses for the student
$completedCoursesQuery = "SELECT Course_ID FROM completed_courses WHERE Student_ID = ?";
$stmt = $conn->prepare($completedCoursesQuery);
$stmt->bind_param("i", $studentID);
$stmt->execute();
$result = $stmt->get_result();
$completedCourses = [];
while ($row = $result->fetch_assoc()) {
    $completedCourses[] = $row['Course_ID'];
}
$stmt->close();

// Fetch courses assigned this semester
$currentSemesterCoursesQuery = "SELECT Course_ID FROM student_course_registration WHERE Student_ID = ?";
$stmt = $conn->prepare($currentSemesterCoursesQuery);
$stmt->bind_param("i", $studentID);
$stmt->execute();
$result = $stmt->get_result();
$currentSemesterCourses = [];
while ($row = $result->fetch_assoc()) {
    $currentSemesterCourses[] = $row['Course_ID'];
}
$stmt->close();

// Query to get all theory courses for each level in the student's department
$sql = "
    SELECT l.Level_ID, l.Level_name, c.Course_ID, c.Course_Name, c.Course_CreditHours
    FROM level l
    LEFT JOIN course c ON l.Level_name = c.Level_name 
    AND c.Department_name_forCourse = ? 
    AND c.Course_LabOrTheory = 'Theory'
    WHERE l.Department_name = ?
    ORDER BY l.Level_ID, c.Course_Name
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $studentDepartment, $studentDepartment);
$stmt->execute();
$result = $stmt->get_result();

$levels = [];
$maxCourses = 0;
while ($row = $result->fetch_assoc()) {
    $levelName = $row['Level_name'];
    $courseID = $row['Course_ID'];
    $isCompleted = in_array($courseID, $completedCourses);
    $isCurrentSemester = in_array($courseID, $currentSemesterCourses);
    
    $courseName = $row['Course_Name'] ? 
        $row['Course_Name'] . "<br><small>" . $row['Course_CreditHours'] . " Credit Hours</small><br><small>Course ID: " . $courseID . "</small>" 
        : '';
    
    $levels[$levelName][] = [
        'courseName' => $courseName,
        'isCompleted' => $isCompleted,
        'isCurrentSemester' => $isCurrentSemester
    ];
    $maxCourses = max($maxCourses, count($levels[$levelName]));
}
$stmt->close();

// Query to get incomplete theory courses that the student hasn’t completed and isn’t currently registered for
$incompleteTheoryCoursesQuery = "
    SELECT c.Course_ID, c.Course_Name, c.Prerequisite_Course_ID,
        CASE
            WHEN c.Prerequisite_Course_ID IS NULL OR cc_prereq.Course_ID IS NOT NULL THEN 'Eligible'
            ELSE 'Not Eligible'
        END AS Can_Register
    FROM course c
    LEFT JOIN completed_courses cc ON cc.Student_ID = ? AND cc.Course_ID = c.Course_ID
    LEFT JOIN completed_courses cc_prereq ON cc_prereq.Student_ID = ? AND cc_prereq.Course_ID = c.Prerequisite_Course_ID
    LEFT JOIN student_course_registration scr ON scr.Student_ID = ? AND scr.Course_ID = c.Course_ID
    WHERE cc.Course_ID IS NULL -- Course not completed
    AND scr.Course_ID IS NULL -- Not currently registered
    AND c.Department_name_forCourse = ? -- Match department
    AND c.Course_LabOrTheory = 'Theory' -- Only theory courses
";

$stmt = $conn->prepare($incompleteTheoryCoursesQuery);
$stmt->bind_param("iiis", $studentID, $studentID, $studentID, $studentDepartment);
$stmt->execute();
$incompleteTheoryCourses = $stmt->get_result();
$stmt->close();


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard with Calendar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Style.css">
    <style>
        .completed-course {
            background-color: #4e8f4e; /* Darker green */
            color: white;
        }
        .current-semester-course {
            background-color: #d0e9d0; /* Slightly darker light green */
            color: black;
        }
        .color-guide {
            margin-top: 20px;
        }
        .color-box {
            display: inline-block;
            width: 20px;
            height: 20px;
            vertical-align: middle;
            margin-right: 8px;
            border: 1px solid #aaa;
        }
        .completed-guide {
            background-color: #4e8f4e;
        }
        .current-guide {
            background-color: #d0e9d0;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    <script src="sidebar.js"></script>

    <div class="main-content">
        <h2>Student Plan - Department: <?php echo htmlspecialchars($studentDepartment); ?></h2>

        <table>
            <thead>
                <tr>
                    <th>Level</th>
                    <?php
                    for ($i = 1; $i <= $maxCourses; $i++) {
                        echo "<th>Course $i</th>";
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($levels as $levelName => $courses) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($levelName) . "</td>";
                    
                    foreach ($courses as $course) {
                        $class = $course['isCompleted'] ? 'completed-course' : ($course['isCurrentSemester'] ? 'current-semester-course' : '');
                        echo "<td class='$class'>" . $course['courseName'] . "</td>";
                    }
                    
                    $remainingColumns = $maxCourses - count($courses);
                    for ($i = 0; $i < $remainingColumns; $i++) {
                        echo "<td></td>";
                    }
                    
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        

        <!-- Color Guide -->
        <div class="color-guide">
            <p><span class="color-box completed-guide"></span> Completed Course</p>
            <p><span class="color-box current-guide"></span> Current Semester Course</p>
        </div>
        <br>

        <h2>Incomplete Courses</h2>
<table>
    <thead>
        <tr>
            <th>Course ID</th>
            <th>Course Name</th>
            <th>Registration Eligibility</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $incompleteTheoryCourses->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['Course_ID']); ?></td>
                <td><?php echo htmlspecialchars($row['Course_Name']); ?></td>
                <td><?php echo htmlspecialchars($row['Can_Register']); ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>



    </div>
</body>

</html>
