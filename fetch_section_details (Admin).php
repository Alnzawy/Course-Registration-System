<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['section_id'])) {
    $sectionId = intval($_POST['section_id']);

    // Fetch doctor information for the section
    $sectionQuery = "SELECT Doctor_name, Doctor_department, TimeSlot FROM sections WHERE Section_ID = $sectionId LIMIT 1";
    $sectionResult = $conn->query($sectionQuery);
    $sectionData = $sectionResult->fetch_assoc();

    // Fetch student details enrolled in the section
    $studentQuery = "SELECT s.Student_ID, s.Student_name, s.Student_department, s.Student_Level 
                     FROM student s 
                     JOIN student_course_registration r ON s.Student_ID = r.Student_ID 
                     WHERE r.Section_ID = $sectionId";
    $studentResult = $conn->query($studentQuery);

    // Display doctor information
    echo "<h2>Doctor Information</h2>";
    echo "<p><strong>Name:</strong> " . htmlspecialchars($sectionData['Doctor_name']) . "</p>";
    echo "<p><strong>Department:</strong> " . htmlspecialchars($sectionData['Doctor_department']) . "</p>";
    echo "<p><strong>Time Slot:</strong> " . htmlspecialchars($sectionData['TimeSlot']) . "</p>";

    // Display students enrolled in the section
    echo "<h2>Students in Section</h2>";
    if ($studentResult->num_rows > 0) {
        // Adding a scrollable div for the table
        echo "<div style='max-height: 400px; overflow-y: auto; margin-top: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
        echo "<table style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background-color: #4f46e5; color: white;'>
                <th style='padding: 10px;'>No.</th>
                <th style='padding: 10px;'>Student ID</th>
                <th style='padding: 10px;'>Name</th>
                <th style='padding: 10px;'>Department</th>
                <th style='padding: 10px;'>Level</th>
              </tr>";
        
        $counter = 1;
        while ($student = $studentResult->fetch_assoc()) {
            echo "<tr style='text-align: center;'>
                    <td style='padding: 6px; border: 1px solid #ddd;'>{$counter}</td>
                    <td style='padding: 7px; border: 1px solid #ddd;'>{$student['Student_ID']}</td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>{$student['Student_name']}</td>
                    <td style='padding: 7px; border: 1px solid #ddd;'>{$student['Student_department']}</td>
                    <td style='padding: 6px; border: 1px solid #ddd;'>{$student['Student_Level']}</td>
                  </tr>";
            $counter++;
        }
        echo "</table></div>";
    } else {
        echo "<p>No students enrolled in this section.</p>";
    }
}

$conn->close();
?>
