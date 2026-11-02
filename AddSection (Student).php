<?php
// Start session and include config
include('config.php');

// Retrieve the student ID from the session
$studentID = $_SESSION['user_id'];

// Fetch the student's department
$studentDepartmentQuery = "
    SELECT Student_department 
    FROM student 
    WHERE Student_ID = ?
";
$stmt = $conn->prepare($studentDepartmentQuery);
$stmt->bind_param("i", $studentID);
$stmt->execute();
$stmt->bind_result($studentDepartment);
$stmt->fetch();
$stmt->close();

// Define arrays for registered courses and time slots
$registeredCourses = [];
$registeredTimeSlots = [];

// Fetch registered sections for the student to track existing registrations
$registeredSectionsQuery = "
    SELECT scr.Course_ID, s.Course_LabOrTheory, s.TimeSlot
    FROM student_course_registration scr
    JOIN sections s ON scr.Section_ID = s.Section_ID
    WHERE scr.Student_ID = ?
";
$stmt = $conn->prepare($registeredSectionsQuery);
$stmt->bind_param("i", $studentID);
$stmt->execute();
$registeredSections = $stmt->get_result();
$stmt->close();

// Populate registeredCourses and registeredTimeSlots for checks
while ($row = $registeredSections->fetch_assoc()) {
    $registeredCourses[$row['Course_ID']][$row['Course_LabOrTheory']] = true;
    $registeredTimeSlots[] = $row['TimeSlot'];
}

// Fetch available sections based on eligibility criteria, including prerequisites
$availableSectionsQuery = "
    SELECT s.Section_ID, s.Course_ID, s.Course_Name, s.Course_LabOrTheory, s.Course_CreditHours, 
           s.Doctor_name, s.Room_name, s.TimeSlot, s.Available_Seats, s.Level_Name
    FROM sections s
    LEFT JOIN course c ON s.Course_ID = c.Course_ID
    LEFT JOIN completed_courses cc ON cc.Student_ID = ? AND cc.Course_ID = s.Course_ID
    LEFT JOIN completed_courses cc_prereq ON cc_prereq.Student_ID = ? AND cc_prereq.Course_ID = c.Prerequisite_Course_ID
    WHERE cc.Course_ID IS NULL -- Course not completed
    AND s.Available_Seats > 0 -- Ensure seats are available
    AND c.Department_name_forCourse = ? -- Match student's department
    AND (c.Prerequisite_Course_ID IS NULL OR cc_prereq.Course_ID IS NOT NULL) -- Prerequisite completed or none
    AND NOT EXISTS (
        SELECT 1 FROM student_course_registration scr 
        WHERE scr.Student_ID = ? AND scr.Course_ID = s.Course_ID
    ) -- Exclude courses the student is already registered for
    GROUP BY s.Section_ID
";
$stmt = $conn->prepare($availableSectionsQuery);
$stmt->bind_param("iisi", $studentID, $studentID, $studentDepartment, $studentID);
$stmt->execute();
$availableSections = $stmt->get_result();
$stmt->close();

// Initialize $selectedCourses array
$selectedCourses = [];

// Handle form submission to register for sections
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_sections'])) {
    $selectedSections = $_POST['section_ids'] ?? [];

    // Check for single course selection and lab/theory pairing
    foreach ($selectedSections as $sectionId) {
        $sectionInfoQuery = "
            SELECT Course_ID, Course_LabOrTheory
            FROM sections
            WHERE Section_ID = ?
        ";
        $stmt = $conn->prepare($sectionInfoQuery);
        $stmt->bind_param("i", $sectionId);
        $stmt->execute();
        $stmt->bind_result($courseId, $labOrTheory);
        $stmt->fetch();
        $stmt->close();

        // Track selected courses and their types
        $selectedCourses[$courseId][$labOrTheory] = $sectionId;
    }

    if (count($selectedCourses) > 1) {
        echo "<div class='message-container'><p class='message error'>You can only add one course per registration process.</p></div>";
    } else {
        $courseIds = array_keys($selectedCourses);
        $selectedCourseId = $courseIds[0];
        $courseSections = $selectedCourses[$selectedCourseId];

        if (!isset($courseSections['Lab']) || !isset($courseSections['Theory'])) {
            echo "<div class='message-container'><p class='message error'>You must choose both a lab and a theory section for the selected course.</p></div>";
        } else {
            // Register both sections
            foreach ($courseSections as $labOrTheory => $sectionId) {
                $registerQuery = "
                    INSERT INTO student_course_registration (Student_ID, Section_ID, Course_ID, Status, Section_Fit)
                    VALUES (?, ?, ?, 'Registered', 1)
                ";
                $stmt = $conn->prepare($registerQuery);
                $stmt->bind_param("iii", $studentID, $sectionId, $selectedCourseId);
                $stmt->execute();

                // Update available seats in the sections table
                $updateSeatsQuery = "
                    UPDATE sections SET Available_Seats = Available_Seats - 1 
                    WHERE Section_ID = ?
                ";
                $stmt = $conn->prepare($updateSeatsQuery);
                $stmt->bind_param("i", $sectionId);
                $stmt->execute();
            }
            echo "<div class='message-container'><p class='message success'>Successfully registered for the selected course sections.</p></div>";
        }
    }
}
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
        .submit-button {
            background-color: #4f46e5;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .submit-button:hover {
            background-color: #3b37c8;
        }

        .message-container {
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            /* Add space above and below the message */
        }

        .message {
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 18px;
            max-width: 80%;
            /* Optional: limit width */
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <script src="sidebar.js"></script>

    <div class="main-content">
        <h1>Student Dashboard</h1>
        <h2>Available Sections to Register</h2>

        <?php if ($availableSections->num_rows > 0): ?>
            <form method="POST" action="">
                <table>
                    <tr>
                        <th>Select</th>
                        <th>Section ID</th>
                        <th>Course ID</th>
                        <th>Course Name</th>
                        <th>Lab/Theory</th>
                        <th>Credit Hours</th>
                        <th>Doctor</th>
                        <th>Room</th>
                        <th>Time Slot</th>
                        <th>Available Seats</th>
                        <th>Level</th>
                    </tr>
                    <?php while ($row = $availableSections->fetch_assoc()): ?>
                        <?php
                        $courseID = $row['Course_ID'];
                        $courseType = $row['Course_LabOrTheory'];
                        $timeSlot = $row['TimeSlot'];
                        $canRegister = true;

                        // Check if seats are available
                        if ($row['Available_Seats'] <= 0) {
                            $canRegister = false;
                        }

                        // Ensure both lab and theory are required for the same course
                        if (isset($registeredCourses[$courseID])) {
                            if (
                                ($courseType == 'Lab' && !isset($registeredCourses[$courseID]['Theory'])) ||
                                ($courseType == 'Theory' && !isset($registeredCourses[$courseID]['Lab']))
                            ) {
                                $canRegister = false;
                            }
                        }

                        // Check for time conflicts
                        if (in_array($timeSlot, $registeredTimeSlots)) {
                            $canRegister = false;
                        }
                        ?>
                        <tr <?php echo $canRegister ? '' : 'style="background-color: #f8d7da;"'; ?>>
                            <td>
                                <?php if ($canRegister): ?>
                                    <input type="checkbox" name="section_ids[]" value="<?php echo $row['Section_ID']; ?>">
                                    <input type="hidden" name="course_id" value="<?php echo $courseID; ?>">
                                <?php else: ?>
                                    <span>Not Eligible</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['Section_ID']; ?></td>
                            <td><?php echo $row['Course_ID']; ?></td>
                            <td><?php echo $row['Course_Name']; ?></td>
                            <td><?php echo $row['Course_LabOrTheory']; ?></td>
                            <td><?php echo $row['Course_CreditHours']; ?></td>
                            <td><?php echo $row['Doctor_name']; ?></td>
                            <td><?php echo $row['Room_name']; ?></td>
                            <td><?php echo $row['TimeSlot']; ?></td>
                            <td><?php echo $row['Available_Seats']; ?></td>
                            <td><?php echo $row['Level_Name']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
                <button type="submit" name="register_sections" class="submit-button">Register for Selected Sections</button>
            </form>
        <?php else: ?>
            <p>No available sections found for this course.</p>
        <?php endif; ?>
    </div>
</body>
</html>
