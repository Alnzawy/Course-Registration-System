<?php
include 'config.php'; // Include the database configuration to establish a connection.

$sectionMessages = ""; // Initialize a message variable to store feedback messages about the section assignment process.

// Set filters based on user input, sanitized with `real_escape_string` to prevent SQL injection attacks.
$filterDepartment = isset($_GET['department']) ? $conn->real_escape_string($_GET['department']) : '';
$filterLevel = isset($_GET['level']) ? $conn->real_escape_string($_GET['level']) : '';
$filterCourseName = isset($_GET['course_name']) ? $conn->real_escape_string($_GET['course_name']) : '';
$filterLabOrTheory = isset($_GET['course_type']) ? $conn->real_escape_string($_GET['course_type']) : '';
$orderBy = isset($_GET['orderBy']) ? $conn->real_escape_string($_GET['orderBy']) : 'Section_ID';
$orderDir = isset($_GET['orderDir']) && $_GET['orderDir'] == 'desc' ? 'desc' : 'asc';

// Build SQL query to retrieve sections based on filters (unchanged if no filters are set).
$sql = "SELECT DISTINCT sections.Section_ID, sections.Course_ID, sections.Course_Name, sections.Course_LabOrTheory, 
        sections.Course_CreditHours, sections.Level_Name, sections.Doctor_department, sections.Doctor_ID, 
        sections.Doctor_name, sections.Room_ID, sections.Room_name, sections.TimeSlot, sections.Available_Seats, 
        course.Department_name_forCourse 
        FROM sections
        JOIN course ON sections.Course_ID = course.Course_ID
        WHERE (course.Department_name_forCourse LIKE '%$filterDepartment%' OR '$filterDepartment' = '')
        AND (sections.Level_Name LIKE '%$filterLevel%' OR '$filterLevel' = '')
        AND (sections.Course_Name LIKE '%$filterCourseName%' OR '$filterCourseName' = '')";

// Apply additional filter for course type (Lab or Theory) only if a value is provided.
if ($filterLabOrTheory !== '') {
    $sql .= " AND sections.Course_LabOrTheory = '$filterLabOrTheory'";
}

// Add ordering clause to sort results based on user-defined columns or defaults.
$sql .= " ORDER BY $orderBy $orderDir";

// Execute the query and store the result.
$result = $conn->query($sql);
if (!$result) {
    die("Error executing query: " . $conn->error); // Display error message if the query fails.
}

// If form is submitted via POST, begin section assignment logic for students.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sectionMessages = ""; // Reset messages for each submission.

    // Retrieve all students and courses from the database.
    $students = $conn->query("SELECT Student_ID, Student_Level, Student_department FROM student");
    $courses = $conn->query("SELECT Course_ID, Prerequisite_Course_ID, Level_name, Department_name_forCourse FROM course");

    if ($students && $courses) {
        // Loop through each student and determine eligible courses.
        while ($student = $students->fetch_assoc()) {
            $studentId = $student['Student_ID'];
            $studentLevel = $student['Student_Level'];
            $studentDepartment = $student['Student_department'];

            // Construct the expected course level format based on student's level and department.
            $expectedCourseLevel = "Level " . $studentLevel . " " . $studentDepartment;

            while ($course = $courses->fetch_assoc()) {
                $courseId = $course['Course_ID'];
                $prerequisiteId = $course['Prerequisite_Course_ID'];
                $courseLevel = $course['Level_name'];
                $courseDepartment = $course['Department_name_forCourse'];

                // Check if course level and department match the student's level and department (Rule 3).
                if ($courseLevel != $expectedCourseLevel || $courseDepartment != $studentDepartment) {
                    continue;  // Skip if the course does not meet the student's level and department requirements.
                }

                // Check if the student has already completed this course (Rule 1).
                $completedQuery = "SELECT * FROM completed_courses WHERE Student_ID = $studentId AND Course_ID = $courseId";
                $completedResult = $conn->query($completedQuery);
                
                if ($completedResult && $completedResult->num_rows > 0) {
                    continue;  // Skip this course if already completed by the student.
                }

                // Check if the student has completed the prerequisite course if required (Rule 2).
                if ($prerequisiteId) {
                    $prerequisiteQuery = "SELECT * FROM completed_courses WHERE Student_ID = $studentId AND Course_ID = $prerequisiteId";
                    $prerequisiteResult = $conn->query($prerequisiteQuery);

                    if (!$prerequisiteResult || $prerequisiteResult->num_rows == 0) {
                        continue;  // Skip if prerequisite is not completed.
                    }
                }

                // Ensure the student isn't already registered in a theory section of this course.
                $checkTheoryQuery = "SELECT * FROM student_course_registration WHERE Student_ID = $studentId AND Course_ID = $courseId AND Section_ID IN (SELECT Section_ID FROM sections WHERE Course_ID = $courseId AND Course_LabOrTheory = 'Theory')";
                $theoryAlreadyRegistered = $conn->query($checkTheoryQuery);

                if ($theoryAlreadyRegistered && $theoryAlreadyRegistered->num_rows == 0) {
                    // Register student in a theory section if not already registered.
                    $theorySectionQuery = "SELECT Section_ID, Available_Seats FROM sections WHERE Course_ID = $courseId AND Course_LabOrTheory = 'Theory' LIMIT 1";
                    $theorySectionResult = $conn->query($theorySectionQuery);

                    if ($theorySectionResult && $theorySectionResult->num_rows > 0) {
                        $theorySection = $theorySectionResult->fetch_assoc();
                        $theorySectionId = $theorySection['Section_ID'];
                        $availableSeats = $theorySection['Available_Seats'];

                        // Check the number of students currently registered in the section.
                        $studentCountQuery = "SELECT COUNT(*) AS student_count FROM student_course_registration WHERE Section_ID = $theorySectionId";
                        $studentCountResult = $conn->query($studentCountQuery);
                        $studentCount = $studentCountResult->fetch_assoc()['student_count'];

                        if ($studentCount < $availableSeats) {
                            // Register the student if seats are available.
                            $registerTheoryQuery = "INSERT INTO student_course_registration (Student_ID, Section_ID, Course_ID, Status) VALUES ($studentId, $theorySectionId, $courseId, 'Registered')";
                            if (!$conn->query($registerTheoryQuery)) {
                                $sectionMessages .= "Error registering student $studentId for theory section $theorySectionId: " . $conn->error . "<br>";
                            }
                        } else {
                            $sectionMessages .= "Section $theorySectionId is full. Could not register student $studentId.<br>";
                        }
                    }
                }

                // Check if the student is already registered in a lab section for this course.
                $checkLabQuery = "SELECT * FROM student_course_registration WHERE Student_ID = $studentId AND Course_ID = $courseId AND Section_ID IN (SELECT Section_ID FROM sections WHERE Course_ID = $courseId AND Course_LabOrTheory = 'Lab')";
                $labAlreadyRegistered = $conn->query($checkLabQuery);

                if ($labAlreadyRegistered && $labAlreadyRegistered->num_rows == 0) {
                    // Retrieve two lab sections for this course and assign based on availability.
                    $labSectionsQuery = "SELECT Section_ID, Available_Seats FROM sections WHERE Course_ID = $courseId AND Course_LabOrTheory = 'Lab' ORDER BY Section_ID LIMIT 2";
                    $labSectionsResult = $conn->query($labSectionsQuery);
                
                    if ($labSectionsResult && $labSectionsResult->num_rows == 2) {
                        $labSections = [];
                        while ($labSection = $labSectionsResult->fetch_assoc()) {
                            $labSections[] = $labSection;
                        }
                
                        // Determine which lab section to register based on current enrollment.
                        $firstSectionId = $labSections[0]['Section_ID'];
                        $secondSectionId = $labSections[1]['Section_ID'];
                        $firstSectionSeats = $labSections[0]['Available_Seats'];
                        $secondSectionSeats = $labSections[1]['Available_Seats'];
                
                        // Count currently enrolled students in each section.
                        $firstSectionCount = $conn->query("SELECT COUNT(*) AS count FROM student_course_registration WHERE Section_ID = $firstSectionId")->fetch_assoc()['count'];
                        $secondSectionCount = $conn->query("SELECT COUNT(*) AS count FROM student_course_registration WHERE Section_ID = $secondSectionId")->fetch_assoc()['count'];
                
                        // Choose section based on half capacity or available seats.
                        if ($firstSectionCount < $firstSectionSeats / 2) {
                            $labSectionId = $firstSectionId;
                        } elseif ($secondSectionCount < $secondSectionSeats / 2) {
                            $labSectionId = $secondSectionId;
                        } else {
                            $labSectionId = ($firstSectionCount < $firstSectionSeats) ? $firstSectionId : $secondSectionId;
                        }
                
                        // Register student in the chosen lab section.
                        $registerLabQuery = "INSERT INTO student_course_registration (Student_ID, Section_ID, Course_ID, Status) VALUES ($studentId, $labSectionId, $courseId, 'Registered')";
                        if (!$conn->query($registerLabQuery)) {
                            $sectionMessages .= "Error registering student $studentId for lab section $labSectionId: " . $conn->error . "<br>";
                        }
                    }
                }
                
            }
            $courses->data_seek(0);  // Reset course result set for the next student.
        }
        if (empty($sectionMessages)) {
            $sectionMessages = "Students successfully assigned to sections based on eligibility.<br>";
        }
    } else {
        $sectionMessages = "Error retrieving students or courses."; // Error if fetching students or courses fails.
    }
}
?>







<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Times and Rooms</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Style.css">

    <style>
        /* Add your CSS styling here */
        #assignSectionsToStudents {
            background-color: #4CAF50;
            /* Green background */
            color: white;
            border: none;
            cursor: pointer;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            margin-top: 20px;
            /* Add margin to separate from the table */
        }

        #assignSectionsToStudents:hover {
            background-color: #45a049;
            /* Darker green on hover */
        }

        .message-box {
            margin-top: 20px;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border-radius: 5px;
            font-size: 14px;
        }

        .total-sections {
            font-weight: bold;
        }

        /* Navigation links styling */
        .navigation {
            display: flex;
            justify-content: space-around;
            background-color: #4f46e5;
            padding: 10px;
            margin-bottom: 20px;
        }

        .navigation a {
            text-decoration: none;
            color: white;
            padding: 10px 20px;
            background-color: #5f55ef;
            border-radius: 5px;
            font-weight: bold;
        }

        .navigation a:hover {
            background-color: #4340c8;
        }

        .navigation a.active {
            background-color: #3a36a3;
        }
    </style>
</head>

<body>

    <?php include 'sidebar.php'; ?>
    <script src="sidebar.js"></script>

    <div class="main-content">
        <h1>Sections List</h1>
        <!-- Navigation Links -->
        <div class="navigation">
            <a href="CreateSections.php">Create Sections</a>
            <a href="AssignTimesAndRooms.php" >Assign Times & Rooms</a>
            <a href="AssignDoctors.php">Assign Doctors</a>
            <a href="AssignSectionsToStudents.php"class="active">Assign Sections to Students</a>
        </div>

        <!-- Form to assign times and rooms -->
        <form method="POST" action="">
            <input type="submit" id="assignSectionsToStudents" value="Assign Sections to Students">
        </form>

        <!-- Display message here -->
<!-- Display combined Theory and Lab assignment messages -->
<?php if (!empty($sectionMessages)): ?>
        <div class="message-box">
            <?php echo $sectionMessages; ?>
        </div>
    <?php endif; ?>


       

        <!-- Filter Form -->


        <!-- Sections List Table -->
        <?php
        // Initialize row counter
        $counter = 0;

        // Check if any sections exist
        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<tr>
                <th>No.</th>
                <th><a href='?orderBy=Section_ID&orderDir=" . ($orderBy == 'Section_ID' && $orderDir == 'asc' ? 'desc' : 'asc') . "'>Section ID</a></th>
                <th><a href='?orderBy=Course_ID&orderDir=" . ($orderBy == 'Course_ID' && $orderDir == 'asc' ? 'desc' : 'asc') . "'>Course ID</a></th>
                <th>Course Name</th>
                <th>Lab/Theory</th>
                <th>Credit Hours</th>
                <th>Level Name</th>
                <th>Doctor ID</th>
                <th>Doctor Name</th>
                <th>Room ID</th>
                <th>Room Name</th>
                <th>Time Slot</th>
                <th>Available Seats</th>
              </tr>";

            // Fetch and display each section
            while ($row = $result->fetch_assoc()) {
                $counter++; // Increment the section counter
        
                echo "<tr>
                    <td>{$counter}</td>
                    <td>{$row['Section_ID']}</td>
                    <td>{$row['Course_ID']}</td>
                    <td>{$row['Course_Name']}</td>
                    <td>{$row['Course_LabOrTheory']}</td>
                    <td>{$row['Course_CreditHours']}</td>
                    <td>{$row['Level_Name']}</td>
                    <td>{$row['Doctor_ID']}</td>
                    <td>{$row['Doctor_name']}</td>
                    <td>{$row['Room_ID']}</td>
                    <td>{$row['Room_name']}</td>
                    <td>{$row['TimeSlot']}</td>
                    <td>{$row['Available_Seats']}</td>
                  </tr>";
            }

            echo "</table>";
        } else {
            echo "<p>No sections found.</p>";
        }

        // Close the database connection
        $conn->close();
        ?>
    </div>


</body>

</html>