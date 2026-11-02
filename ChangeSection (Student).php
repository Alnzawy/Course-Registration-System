<?php
// Start session and include config
include('config.php');

// Retrieve the student ID from the session
$studentID = $_SESSION['user_id'];

// Fetch student's registered courses and sections
$registeredSectionsQuery = "
    SELECT scr.Course_ID, scr.Section_ID AS Registered_Section_ID, s.Course_Name, s.Course_LabOrTheory, s.TimeSlot
    FROM student_course_registration scr
    JOIN sections s ON scr.Section_ID = s.Section_ID
    WHERE scr.Student_ID = ?
";
$stmt = $conn->prepare($registeredSectionsQuery);
$stmt->bind_param("i", $studentID);
$stmt->execute();
$registeredSections = $stmt->get_result();
$stmt->close();

// Handle form submission for changing sections
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_section'])) {
    $newSectionID = $_POST['new_section_id'];
    $oldSectionID = $_POST['old_section_id'];
    $courseID = $_POST['course_id'];

    // Debugging statements to check the values
    echo "<p>Debugging: Selected New Section ID: $newSectionID</p>";
    echo "<p>Debugging: Old Section ID: $oldSectionID</p>";
    echo "<p>Debugging: Course ID: $courseID</p>";

    // Update the section by replacing old with new in student_course_registration
    $updateSectionQuery = "
        UPDATE student_course_registration
        SET Section_ID = ?
        WHERE Student_ID = ? AND Section_ID = ? AND Course_ID = ?
    ";
    $stmt = $conn->prepare($updateSectionQuery);
    $stmt->bind_param("iiii", $newSectionID, $studentID, $oldSectionID, $courseID);
    $stmt->execute();
    $stmt->close();

    // Update available seats: decrement for new, increment for old
    $decrementSeatsQuery = "
        UPDATE sections SET Available_Seats = Available_Seats - 1 WHERE Section_ID = ?
    ";
    $stmt = $conn->prepare($decrementSeatsQuery);
    $stmt->bind_param("i", $newSectionID);
    $stmt->execute();
    $stmt->close();

    $incrementSeatsQuery = "
        UPDATE sections SET Available_Seats = Available_Seats + 1 WHERE Section_ID = ?
    ";
    $stmt = $conn->prepare($incrementSeatsQuery);
    $stmt->bind_param("i", $oldSectionID);
    $stmt->execute();
    $stmt->close();

    echo "<div class='message-container'><p class='message success'>Successfully changed section for course $courseID.</p></div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Section</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Style.css">
    <style>
        /* Button styles */
        .change-section-button {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .change-section-button:hover {
            background-color: #d32f2f;
        }

        /* Message styles */
        .message-container {
            text-align: center;
            margin: 20px 0;
        }
        .message.success {
            color: #155724;
            background-color: #d4edda;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 18px;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <script src="sidebar.js"></script>

    <div class="main-content">
        <h1>Change Section</h1>
        <h2>Available Sections for Changing Registered Courses</h2>
        
        <table>
            <tr>
                <th>Current Section ID</th>
                <th>Current Section Type</th>
                <th>Select</th>
                <th>New Section ID</th>
                <th>Course Name</th>
                <th>Course Number</th>
                <th>Lab/Theory</th>
                <th>Time Slot</th>
                <th>Available Seats</th>
            </tr>
            <?php while ($registeredSection = $registeredSections->fetch_assoc()): ?>
                <?php
                // Fetch available sections for the same course but exclude the current registered section
                // and only include sections with the same type (Lab or Theory)
                $availableSectionsQuery = "
                    SELECT s.Section_ID, s.Course_ID, s.Course_Name, s.Course_LabOrTheory, s.TimeSlot, s.Available_Seats
                    FROM sections s
                    WHERE s.Course_ID = ? AND s.Section_ID != ? AND s.Course_LabOrTheory = ? AND s.Available_Seats > 0
                ";
                $stmt = $conn->prepare($availableSectionsQuery);
                $stmt->bind_param("iis", $registeredSection['Course_ID'], $registeredSection['Registered_Section_ID'], $registeredSection['Course_LabOrTheory']);
                $stmt->execute();
                $availableSections = $stmt->get_result();
                $stmt->close();

                $availableSectionsCount = $availableSections->num_rows;
                ?>
                
                <!-- Display the registered section row with rowspan for current section -->
                <tr>
                    <td rowspan="<?php echo $availableSectionsCount > 0 ? $availableSectionsCount : 1; ?>">
                        <?php echo htmlspecialchars($registeredSection['Registered_Section_ID']); ?>
                    </td>
                    <td rowspan="<?php echo $availableSectionsCount > 0 ? $availableSectionsCount : 1; ?>">
                        <?php echo htmlspecialchars($registeredSection['Course_LabOrTheory']); ?>
                    </td>
                    
                    <?php if ($availableSectionsCount > 0): ?>
                        <?php $section = $availableSections->fetch_assoc(); ?>
                        <form method="POST" action="">
                            <td>
                                <input type="radio" name="new_section_id" value="<?php echo $section['Section_ID']; ?>" required>
                                <input type="hidden" name="old_section_id" value="<?php echo $registeredSection['Registered_Section_ID']; ?>">
                                <input type="hidden" name="course_id" value="<?php echo $registeredSection['Course_ID']; ?>">
                            </td>
                            <td><?php echo htmlspecialchars($section['Section_ID']); ?></td>
                            <td><?php echo htmlspecialchars($section['Course_Name']); ?></td>
                            <td><?php echo htmlspecialchars($registeredSection['Course_ID']); ?></td>
                            <td><?php echo htmlspecialchars($section['Course_LabOrTheory']); ?></td>
                            <td><?php echo htmlspecialchars($section['TimeSlot']); ?></td>
                            <td><?php echo htmlspecialchars($section['Available_Seats']); ?></td>
                            <td><button type="submit" name="change_section" class="change-section-button">Change Section</button></td>
                        </form>
                    </tr>

                    <!-- Display remaining available sections for this course -->
                    <?php while ($section = $availableSections->fetch_assoc()): ?>
                        <tr>
                            <form method="POST" action="">
                                <td>
                                    <input type="radio" name="new_section_id" value="<?php echo $section['Section_ID']; ?>" required>
                                    <input type="hidden" name="old_section_id" value="<?php echo $registeredSection['Registered_Section_ID']; ?>">
                                    <input type="hidden" name="course_id" value="<?php echo $registeredSection['Course_ID']; ?>">
                                </td>
                                <td><?php echo htmlspecialchars($section['Section_ID']); ?></td>
                                <td><?php echo htmlspecialchars($section['Course_Name']); ?></td>
                                <td><?php echo htmlspecialchars($registeredSection['Course_ID']); ?></td>
                                <td><?php echo htmlspecialchars($section['Course_LabOrTheory']); ?></td>
                                <td><?php echo htmlspecialchars($section['TimeSlot']); ?></td>
                                <td><?php echo htmlspecialchars($section['Available_Seats']); ?></td>
                                <td><button type="submit" name="change_section" class="change-section-button">Change Section</button></td>
                            </form>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <td colspan="7">No alternative sections available</td>
                <?php endif; ?>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
