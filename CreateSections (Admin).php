<?php
include 'config.php';

$counter = 0;
$totalCreditHours = 0;
$totalTheorySections = 0; // To track total theory sections
$totalLabSections = 0;    // To track total lab sections

// Initialize a message variable
$sectionMessages = "";

// Set default order and filter values
$filterDepartment = isset($_GET['department']) ? $_GET['department'] : '';
$filterLevel = isset($_GET['level']) ? $_GET['level'] : '';
$orderBy = isset($_GET['orderBy']) ? $_GET['orderBy'] : 'Course_ID';
$orderDir = isset($_GET['orderDir']) && $_GET['orderDir'] == 'desc' ? 'desc' : 'asc';

// Prepare SQL query with filters and ordering
$sql = "SELECT Course_ID, Course_Name, Course_CreditHours, Department_name_forCourse, Level_name 
        FROM course 
        WHERE (Department_name_forCourse LIKE '%$filterDepartment%' OR '$filterDepartment' = '') 
        AND (Level_name LIKE '%$filterLevel%' OR '$filterLevel' = '')
        GROUP BY Course_ID, Course_Name, Course_CreditHours, Department_name_forCourse, Level_name
        ORDER BY $orderBy $orderDir";

$result = $conn->query($sql);

// SQL query to get courses that have sections



// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['courses'])) {
        $selectedCourses = $_POST['courses']; // Get selected courses
        $sectionsData = $_POST['sections'];   // Get section details (theory and lab sections)

        foreach ($selectedCourses as $courseID) {
            // Check if sections already exist for the course in the sections table
            $checkSql = "SELECT * FROM sections WHERE Course_ID = $courseID";
            $checkResult = $conn->query($checkSql);

            if ($checkResult->num_rows > 0) {
                // If sections already exist for the course, add a message
                $sectionMessages .= "Sections already exist for Course ID $courseID, skipping section creation.<br>";
                continue; // Skip the insertion for this course
            }

            // Fetch course details from the database
            $sql = "SELECT Course_Name, Course_CreditHours, Level_name FROM course WHERE Course_ID = $courseID";
            $courseResult = $conn->query($sql);

            if ($courseResult->num_rows > 0) {
                $course = $courseResult->fetch_assoc();
                $courseName = $course['Course_Name'];
                $creditHours = $course['Course_CreditHours'];
                $levelName = $course['Level_name'];

                // Insert theory sections and count
                for ($i = 0; $i < $sectionsData[$courseID]['theory']; $i++) {
                    $insertSql = "INSERT INTO sections (Course_ID, Course_Name, Course_LabOrTheory, Course_RealHours, Course_CreditHours, LecturePerWeek, Level_Name)
                                  VALUES ($courseID, '$courseName', 'Theory', 0, $creditHours, 0, '$levelName')";
                    $conn->query($insertSql);
                    $totalTheorySections++; // Increment total theory sections
                }

                // Insert lab sections and count
                for ($i = 0; $i < $sectionsData[$courseID]['lab']; $i++) {
                    $insertSql = "INSERT INTO sections (Course_ID, Course_Name, Course_LabOrTheory, Course_RealHours, Course_CreditHours, LecturePerWeek, Level_Name)
                                  VALUES ($courseID, '$courseName', 'Lab', 0, $creditHours, 0, '$levelName')";
                    $conn->query($insertSql);
                    $totalLabSections++; // Increment total lab sections
                }

                // Add a success message to the variable
                $sectionMessages .= "Sections created for Course ID $courseID (Theory: {$sectionsData[$courseID]['theory']}, Lab: {$sectionsData[$courseID]['lab']}).<br>";
            }
        }
    } else {
        $sectionMessages .= "No courses selected.<br>";
    }
}
$courseQuery = "SELECT Course_ID, Course_Name, Course_CreditHours, Department_name_forCourse, Level_name,
           (SELECT COUNT(s.Student_ID)
            FROM student s
            LEFT JOIN completed_courses cc ON cc.Student_ID = s.Student_ID AND cc.Course_ID = course.Course_ID
            WHERE cc.Course_ID IS NULL
            AND s.Student_department = course.Department_name_forCourse
            
            AND (
                (course.Level_name LIKE 'Level 1%' AND course.Prerequisite_Course_ID IS NULL) OR  -- Updated to include all Level 1 courses
                (course.Level_name NOT LIKE 'Level 1%' AND course.Prerequisite_Course_ID IS NOT NULL
                    AND EXISTS (
                        SELECT 1
                        FROM completed_courses prereq
                        WHERE prereq.Student_ID = s.Student_ID
                        AND prereq.Course_ID = course.Prerequisite_Course_ID
                    ))
            )
           ) AS Total_Eligible_Students
           FROM course
           WHERE (Department_name_forCourse LIKE '%$filterDepartment%' OR '$filterDepartment' = '') 
           AND (Level_name LIKE '%$filterLevel%' OR '$filterLevel' = '')
           GROUP BY Course_ID, Course_Name, Course_CreditHours, Department_name_forCourse, Level_name
           ORDER BY $orderBy $orderDir";

$result = $conn->query($courseQuery);

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

    <style>
        #CreateSections {
            background-color: #FFD700;
            /* Yellow background */
            color: black;
            border: none;
            cursor: pointer;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            margin-top: 20px;
            /* Add margin to separate from the table */
        }

        #CreateSections:hover {
            background-color: #FFC107;
            /* Darker yellow on hover */
        }

        /* Navigation links styling */
        .navigation {
            display: flex;
            justify-content: space-around;
            background-color: #4f46e5;
            /* Adjusted to fit a more modern and professional look */
            padding: 10px;
            margin-bottom: 20px;
        }

        .navigation a {
            text-decoration: none;
            color: white;
            padding: 10px 20px;
            background-color: #5f55ef;
            /* Lighter color for inactive links */
            border-radius: 5px;
            font-weight: bold;
        }

        .navigation a:hover {
            background-color: #4340c8;
            /* Darker on hover */
        }

        .navigation a.active {
            background-color: #3a36a3;
            /* Active link color */
        }

        /* Styles for messages and total sections */
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
    </style>
</head>

<body>

    <?php include 'sidebar.php'; ?>
    <script src="sidebar.js"></script>

    <div class="main-content">
        <h1>Course List & Section Creation</h1>

        <!-- Navigation Links -->
        <div class="navigation">
            <a href="CreateSections.php" class="active">Create Sections</a>
            <a href="AssignTimesAndRooms.php">Assign Times & Rooms</a>
            <a href="AssignDoctors.php">Assign Doctors</a>
            <a href="AssignSectionsToStudents.php">Assign Sections to Students</a>


        </div>



        <!-- Course List Table -->
        <form method="POST" action="">
            <?php
            // Initialize row counter and total credit hours
            $counter = 0;
            $totalCreditHours = 0;

            // Check if any courses exist
            if ($result->num_rows > 0) {
                echo "<table>";
                echo "<tr>
                        <th>Select</th>
                        <th>No.</th>
                        <th><a href='?orderBy=Course_ID&orderDir=" . ($orderBy == 'Course_ID' && $orderDir == 'asc' ? 'desc' : 'asc') . "'>Course ID</a></th>
                        <th><a href='?orderBy=Course_Name&orderDir=" . ($orderBy == 'Course_Name' && $orderDir == 'asc' ? 'desc' : 'asc') . "'>Course Name</a></th>
<th>Total Eligible Students</th>
                        <th><a href='?orderBy=Level_name&orderDir=" . ($orderBy == 'Level_name' && $orderDir == 'asc' ? 'desc' : 'asc') . "'>Level</a></th>
                        <th>Theory Sections <br>(Min 40)</th>
                        <th>Lab Sections <br>(Min 22)</th>
                      </tr>";

                // Fetch and display each course
                while ($row = $result->fetch_assoc()) {
                    $counter++; // Increment the course counter
                    $totalCreditHours += $row['Course_CreditHours']; // Sum up the credit hours
                
                    // Total eligible students for the course (retrieved from the database)
                    $totalEligibleStudents = $row['Total_Eligible_Students'];
                
                    // Calculate number of theory and lab sections based on eligible students
                    $defaultTheorySections = ceil($totalEligibleStudents / 40); // Minimum 40 students per theory section
                    $defaultLabSections = ceil($totalEligibleStudents / 22); // Minimum 18 students per lab section
                
                    echo "<tr>
                            <td><input type='checkbox' name='courses[]' value='{$row['Course_ID']}' class='course-checkbox' checked></td>
                            <td>{$counter}</td>
                            <td>{$row['Course_ID']}</td>
                            <td>{$row['Course_Name']}</td>
                            <td>{$row['Total_Eligible_Students']}</td>
                            <td>{$row['Level_name']}</td>
                            <td>
                                <select name='sections[{$row['Course_ID']}][theory]' class='theory-sections' onchange='updateTotals()'>
                                    <option value='{$defaultTheorySections}' selected>{$defaultTheorySections}</option>
                                    <option value='" . ($defaultTheorySections + 1) . "'>" . ($defaultTheorySections + 1) . "</option>
                                    <option value='" . ($defaultTheorySections + 2) . "'>" . ($defaultTheorySections + 2) . "</option>
                                </select>
                            </td>
                            <td>
                                <select name='sections[{$row['Course_ID']}][lab]' class='lab-sections' onchange='updateTotals()'>
                                    <option value='{$defaultLabSections}' selected>{$defaultLabSections}</option>
                                    <option value='" . ($defaultLabSections + 1) . "'>" . ($defaultLabSections + 1) . "</option>
                                    <option value='" . ($defaultLabSections + 2) . "'>" . ($defaultLabSections + 2) . "</option>
                                </select>
                            </td>
                          </tr>";
                }

               
            } else {
                echo "<p>No courses found.</p>";
            }

            // Close the database connection
            $conn->close();
            ?>
            <br>
            <input type="submit" id="CreateSections" value="Create Sections">
        </form>

        <!-- Display message here -->
        <?php if (!empty($sectionMessages)): ?>
            <div class="message-box">
                <?php echo $sectionMessages; ?>
            </div>
        <?php endif; ?>

    </div>

  

 



</body>

</html>