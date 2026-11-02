<?php
include 'config.php';

$filterDepartment = isset($_GET['department']) ? $conn->real_escape_string($_GET['department']) : '';
$filterLevel = isset($_GET['level']) ? $conn->real_escape_string($_GET['level']) : '';
$filterCourseName = isset($_GET['course_name']) ? $conn->real_escape_string($_GET['course_name']) : '';
$filterLabOrTheory = isset($_GET['course_type']) ? $conn->real_escape_string($_GET['course_type']) : '';

$orderBy = isset($_GET['orderBy']) ? $conn->real_escape_string($_GET['orderBy']) : 'Section_ID';
$orderDir = isset($_GET['orderDir']) && $_GET['orderDir'] == 'desc' ? 'desc' : 'asc';

$sectionMessages = "";

// Function to assign sections to a doctor while avoiding scheduling conflicts
function assignSection($conn, $doctorId, $doctorName, $type, $limit, &$assignedTimes, &$assignedSections, &$sectionMessages) {
    // Query to select unassigned sections of a given type (Theory or Lab) that don't conflict with already assigned times
    $sectionQuery = "SELECT * FROM sections 
                     WHERE Course_LabOrTheory = '$type' 
                     AND Doctor_ID IS NULL 
                     AND NOT TimeSlot IN ('" . implode("','", $assignedTimes) . "') 
                     LIMIT $limit";
    
    $sectionResult = $conn->query($sectionQuery);

    // Check if there are enough sections to assign based on the specified limit
    if ($sectionResult->num_rows == $limit) {
        while ($section = $sectionResult->fetch_assoc()) {
            $sectionId = $section['Section_ID'];
            $timeSlot = $section['TimeSlot'];

            // Update query to assign the doctor to the section
            $assignQuery = "UPDATE sections SET Doctor_ID = $doctorId, Doctor_name = '$doctorName' WHERE Section_ID = $sectionId";
            if ($conn->query($assignQuery)) {
                $assignedTimes[] = $timeSlot; // Add the assigned timeslot to avoid conflicts
                $assignedSections++; // Increment the assigned sections count
            }
        }
        $sectionMessages .= "Assigned $limit $type sections to Dr. $doctorName (ID: $doctorId)<br>";
    } else {
        // If insufficient sections are available, log a message indicating scheduling issues
        $sectionMessages .= "Could not assign sufficient $type sections to Dr. $doctorName (ID: $doctorId) due to scheduling conflicts.<br>";
    }
}

// Check if the form was submitted via POST request to trigger section assignments
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Query to fetch all doctors from the database
    $doctorQuery = "SELECT * FROM doctor";
    $doctorResult = $conn->query($doctorQuery);

    if ($doctorResult->num_rows > 0) {
        // Loop through each doctor to assign sections
        while ($doctor = $doctorResult->fetch_assoc()) {
            $doctorId = $doctor['Doctor_ID'];
            $doctorName = $doctor['Doctor_name'];
            $assignedSections = 0;

            // Query to retrieve all currently assigned timeslots for this doctor to avoid conflicts
            $existingTimesQuery = "SELECT TimeSlot FROM sections WHERE Doctor_ID = $doctorId";
            $existingTimesResult = $conn->query($existingTimesQuery);
            $assignedTimes = [];
            
            // Collect all assigned times for the doctor in an array
            while ($row = $existingTimesResult->fetch_assoc()) {
                $assignedTimes[] = $row['TimeSlot'];
            }

            // Attempt to assign 2 theory sections and 4 lab sections
            assignSection($conn, $doctorId, $doctorName, 'Theory', 2, $assignedTimes, $assignedSections, $sectionMessages);
            assignSection($conn, $doctorId, $doctorName, 'Lab', 4, $assignedTimes, $assignedSections, $sectionMessages);

            // If at least 6 sections are assigned, update the doctor's weekly teaching hours
            if ($assignedSections >= 6) {
                $updateHoursQuery = "UPDATE doctor SET Weekly_Teaching_Hours = Weekly_Teaching_Hours + 12 WHERE Doctor_ID = $doctorId";
                $conn->query($updateHoursQuery);
            }
        }
    } else {
        // If no doctors are available, set a message indicating this
        $sectionMessages = "No available doctors to assign sections.";
    }
}

// SQL query to fetch sections with optional filters for department, level, and course name
$sql = "SELECT DISTINCT sections.Section_ID, sections.Course_ID, sections.Course_Name, sections.Course_LabOrTheory, 
        sections.Course_CreditHours, sections.Level_Name, sections.Doctor_department, sections.Doctor_ID, 
        sections.Doctor_name, sections.Room_ID, sections.Room_name, sections.TimeSlot, sections.Available_Seats, 
        course.Department_name_forCourse 
        FROM sections
        JOIN course ON sections.Course_ID = course.Course_ID
        WHERE (course.Department_name_forCourse LIKE '%$filterDepartment%' OR '$filterDepartment' = '')
        AND (sections.Level_Name LIKE '%$filterLevel%' OR '$filterLevel' = '')
        AND (sections.Course_Name LIKE '%$filterCourseName%' OR '$filterCourseName' = '')";

// Apply additional filter for Lab/Theory if specified by the user
if ($filterLabOrTheory !== '') {
    $sql .= " AND sections.Course_LabOrTheory = '$filterLabOrTheory'";
}

// Append the order by clause based on selected criteria
$sql .= " ORDER BY $orderBy $orderDir";

// Execute the query and check for errors
$result = $conn->query($sql);
if (!$result) {
    die("Error executing query: " . $conn->error); // Display error if query fails
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
        #assignDoctors {
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

        #assignDoctors:hover {
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
            <a href="AssignDoctors.php"class="active">Assign Doctors</a>
            <a href="AssignSectionsToStudents.php">Assign Sections to Students</a>
        </div>

        <!-- Form to assign times and rooms -->
        <form method="POST" action="">
            <input type="submit" id="assignDoctors" value="Assign Doctors">
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