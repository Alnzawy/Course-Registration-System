<?php 
include 'config.php'; 

// Escape user input to avoid SQL injection
$filterDepartment = isset($_GET['department']) ? $conn->real_escape_string($_GET['department']) : '';
$filterLevel = isset($_GET['level']) ? $conn->real_escape_string($_GET['level']) : '';
$filterCourseName = isset($_GET['course_name']) ? $conn->real_escape_string($_GET['course_name']) : '';
$filterLabOrTheory = isset($_GET['course_type']) ? $conn->real_escape_string($_GET['course_type']) : '';
$filterRoom = isset($_GET['room']) ? $conn->real_escape_string($_GET['room']) : ''; 
$orderBy = isset($_GET['orderBy']) ? $conn->real_escape_string($_GET['orderBy']) : 'Section_ID';
$orderDir = isset($_GET['orderDir']) && $_GET['orderDir'] == 'desc' ? 'desc' : 'asc';

// Query to get all rooms for the dropdown filter
$roomSql = "SELECT Room_ID, Room_Name FROM room";
$roomResult = $conn->query($roomSql);

// Prepare SQL query to fetch distinct sections with a student count for each section
$sql = "SELECT DISTINCT sections.Section_ID, sections.Course_ID, sections.Course_Name, sections.Course_LabOrTheory, 
        sections.Course_CreditHours, sections.Level_Name, sections.Doctor_department, sections.Doctor_ID, 
        sections.Doctor_name, sections.Room_ID, sections.Room_name, sections.TimeSlot, sections.Available_Seats,
        course.Department_name_forCourse,
        (SELECT COUNT(*) FROM student_course_registration WHERE student_course_registration.Section_ID = sections.Section_ID) AS Students_Enrolled
        FROM sections
        JOIN course ON sections.Course_ID = course.Course_ID
        WHERE (course.Department_name_forCourse LIKE '%$filterDepartment%' OR '$filterDepartment' = '')
        AND (sections.Level_Name LIKE '%$filterLevel%' OR '$filterLevel' = '')
        AND (sections.Course_Name LIKE '%$filterCourseName%' OR '$filterCourseName' = '')";

// Apply filters for Lab/Theory and Room
if ($filterLabOrTheory !== '') {
    $sql .= " AND sections.Course_LabOrTheory = '$filterLabOrTheory'";
}
if ($filterRoom !== '') {
    $sql .= " AND sections.Room_ID = '$filterRoom'";
}

$sql .= " ORDER BY $orderBy $orderDir";

// Execute the query
$result = $conn->query($sql);

if (!$result) {
    die("Error executing query: " . $conn->error);
}
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
        .preview-btn {
            background-color: #007bff;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .preview-btn:hover { background-color: #0056b3; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); }
        .modal-content { background-color: #fff; margin: 5% auto; padding: 20px; width: 50%; border-radius: 8px; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #000; }
    </style>
</head>

<body>

<?php include 'sidebar.php'; ?>
<script src="sidebar.js"></script>

<div class="main-content">
    <h1>Sections List</h1>

    <!-- Filter Form -->
    <div class="filters">
        <form method="GET" action="">
            <label for="department">Department:</label>
            <select name="department" id="department">
                <option value="">All Departments</option>
                <option value="CS" <?php if ($filterDepartment == 'CS') echo 'selected'; ?>>CS</option>
                <option value="IT" <?php if ($filterDepartment == 'IT') echo 'selected'; ?>>IT</option>
                <option value="IS" <?php if ($filterDepartment == 'IS') echo 'selected'; ?>>IS</option>
            </select>

            <label for="level">Level:</label>
            <select name="level" id="level">
                <option value="">All Levels</option>
                <option value="Level 1" <?php if ($filterLevel == 'Level 1') echo 'selected'; ?>>Level 1</option>
                <option value="Level 2" <?php if ($filterLevel == 'Level 2') echo 'selected'; ?>>Level 2</option>
                <option value="Level 3" <?php if ($filterLevel == 'Level 3') echo 'selected'; ?>>Level 3</option>
                <option value="Level 4" <?php if ($filterLevel == 'Level 4') echo 'selected'; ?>>Level 4</option>
                <option value="Level 5" <?php if ($filterLevel == 'Level 5') echo 'selected'; ?>>Level 5</option>
                <option value="Level 6" <?php if ($filterLevel == 'Level 6') echo 'selected'; ?>>Level 6</option>
            </select>

            <label for="course_name">Course Name:</label>
            <input type="text" name="course_name" id="course_name" value="<?php echo htmlspecialchars($filterCourseName); ?>">

            <label for="course_type">Lab/Theory:</label>
            <select name="course_type" id="course_type">
                <option value="">Both</option>
                <option value="Lab" <?php if ($filterLabOrTheory == 'Lab') echo 'selected'; ?>>Lab</option>
                <option value="Theory" <?php if ($filterLabOrTheory == 'Theory') echo 'selected'; ?>>Theory</option>
            </select>
            
            <!-- Room Filter -->
            <label for="room">Filter by Room:</label>
            <select name="room" id="room">
                <option value="">Select Room</option>
                <?php
                if ($roomResult->num_rows > 0) {
                    while ($roomRow = $roomResult->fetch_assoc()) {
                        $selected = ($roomRow['Room_ID'] == $filterRoom) ? 'selected' : '';
                        echo "<option value='{$roomRow['Room_ID']}' $selected>{$roomRow['Room_Name']}</option>";
                    }
                }
                ?>
            </select>

            <input type="submit" value="Apply Filters">
        </form>
    </div>

    <!-- Sections List Table -->
    <?php
    if ($result->num_rows > 0) {
        echo "<table>";
        echo "<tr>
                <th>No.</th>
                <th>Section ID</th>
                <th>Course ID</th>
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
                <th>Students Enrolled</th>
                <th>Preview Details</th>
              </tr>";
        
        $counter = 0;
        while ($row = $result->fetch_assoc()) {
            $counter++;
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
                    <td>{$row['Students_Enrolled']}</td>
                    <td><button class='preview-btn' onclick=\"openModal({$row['Section_ID']})\">Preview Details</button></td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No sections found.</p>";
    }
    ?>

    <!-- Modal for Section Details -->
    <div id="sectionModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Section Details</h2>
            <div id="sectionDetails"></div>
        </div>
    </div>
</div>

<script>
    function openModal(sectionId) {
        // Fetch section details using AJAX
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "fetch_section_details.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onload = function() {
            if (xhr.status === 200) {
                document.getElementById("sectionDetails").innerHTML = xhr.responseText;
                document.getElementById("sectionModal").style.display = "block";
            }
        };
        xhr.send("section_id=" + sectionId);
    }

    function closeModal() {
        document.getElementById("sectionModal").style.display = "none";
    }
</script>

</body>
</html>
