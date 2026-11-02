<?php
// Start the session

// Include config file for session and database connection
include('config.php');

// Retrieve the Doctor_ID (teacher's ID) from the session
$teacher_id = $_SESSION['user_id']; // Assuming 'user_id' holds the Doctor's ID

// Query to fetch sections taught by the doctor along with necessary details
$sql_sections = "
    SELECT 
        s.Section_ID, 
        s.Course_ID, 
        s.Course_Name, 
        s.Course_LabOrTheory, 
        s.Course_CreditHours, 
        s.Level_Name,
        s.Doctor_ID, 
        d.Doctor_name AS Doctor_Name, 
        r.Room_ID, 
        r.Room_Name, 
        s.TimeSlot, 
        s.Available_Seats,
        (SELECT COUNT(scr.Student_ID) FROM student_course_registration scr WHERE scr.Section_ID = s.Section_ID) AS Students_Enrolled
    FROM 
        sections s
    JOIN 
        doctor d ON s.Doctor_ID = d.Doctor_ID
    JOIN 
        room r ON s.Room_ID = r.Room_ID
    WHERE 
        s.Doctor_ID = ?
";

$stmt_sections = $conn->prepare($sql_sections);
$stmt_sections->bind_param("i", $teacher_id);
$stmt_sections->execute();
$result_sections = $stmt_sections->get_result();

$sections = [];
while ($section = $result_sections->fetch_assoc()) {
    // For each section, get the list of students
    $section_id = $section['Section_ID'];

    // Query to fetch students in the current section
    $sql_students = "
        SELECT 
            s.Student_ID, 
            s.Student_name, 
            s.Student_level 
        FROM 
            student s
        JOIN 
            student_course_registration scr ON s.Student_ID = scr.Student_ID
        WHERE 
            scr.Section_ID = ?";
    $stmt_students = $conn->prepare($sql_students);
    $stmt_students->bind_param("i", $section_id);
    $stmt_students->execute();
    $result_students = $stmt_students->get_result();

    // Add students to the section
    $students = [];
    while ($student = $result_students->fetch_assoc()) {
        $students[] = $student;
    }

    // Store the section with its students
    $section['students'] = $students;
    $sections[] = $section;
}

// Close all statements and connection
$stmt_sections->close();
$stmt_students->close();
$conn->close();
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

    <!-- Sections List Table -->
    <?php
    if (count($sections) > 0) {
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
        foreach ($sections as $section) {
            $counter++;
            echo "<tr>
                    <td>{$counter}</td>
                    <td>{$section['Section_ID']}</td>
                    <td>{$section['Course_ID']}</td>
                    <td>{$section['Course_Name']}</td>
                    <td>{$section['Course_LabOrTheory']}</td>
                    <td>{$section['Course_CreditHours']}</td>
                    <td>{$section['Level_Name']}</td>
                    <td>{$section['Doctor_ID']}</td>
                    <td>{$section['Doctor_Name']}</td>
                    <td>{$section['Room_ID']}</td>
                    <td>{$section['Room_Name']}</td>
                    <td>{$section['TimeSlot']}</td>
                    <td>{$section['Available_Seats']}</td>
                    <td>{$section['Students_Enrolled']}</td>
                    <td><button class='preview-btn' onclick=\"openModal({$section['Section_ID']})\">Preview Details</button></td>
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
            <span class="close" onclick="closeModal()">×</span>
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
