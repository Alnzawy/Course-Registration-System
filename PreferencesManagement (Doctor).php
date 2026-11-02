<?php
// Include config file for database connection
include('config.php');

// Start session to retrieve Teacher_ID (Doctor_ID)
$teacher_id = $_SESSION['user_id']; // Assuming 'user_id' holds the Teacher's ID

// Prepare SQL statement to retrieve teacher data
$sql = "SELECT Doctor_name AS Teacher_name, Doctor_department AS Teacher_department, Weekly_Teaching_Hours FROM doctor WHERE Doctor_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);

// Execute and fetch results
$stmt->execute();
$result = $stmt->get_result();
$teacher_data = $result->fetch_assoc();

// Check if teacher data exists
if (!$teacher_data) {
    echo "Error: No data found for this Teacher.";
    exit();
}

// Get the teacher's department
$teacher_department = $teacher_data['Teacher_department'];

// Fetch only theory courses for the teacher's department
$courseQuery = "SELECT Course_ID, Course_Name FROM course WHERE Department_name_forCourse = ? AND Course_LabOrTheory = 'Theory'";
$courseStmt = $conn->prepare($courseQuery);
$courseStmt->bind_param("s", $teacher_department);
$courseStmt->execute();
$courseResult = $courseStmt->get_result();

// Store all available theory courses in an array
$courses = [];
while ($row = $courseResult->fetch_assoc()) {
    $courses[] = $row;
}

// Check if the doctor has previously selected courses
$previousCoursesQuery = "SELECT Course_ID FROM doctor_courses WHERE Doctor_ID = ?";
$previousStmt = $conn->prepare($previousCoursesQuery);
$previousStmt->bind_param("i", $teacher_id);
$previousStmt->execute();
$previousResult = $previousStmt->get_result();

// Store previously selected courses
$previouslySelectedCourses = [];
while ($row = $previousResult->fetch_assoc()) {
    $previouslySelectedCourses[] = $row['Course_ID'];
}

// Check if selected courses are already taken by another doctor
$courseInUseQuery = "SELECT Course_ID FROM doctor_courses";
$courseInUseStmt = $conn->prepare($courseInUseQuery);
$courseInUseStmt->execute();
$courseInUseResult = $courseInUseStmt->get_result();

// Store courses already taken by other doctors
$courseInUse = [];
while ($row = $courseInUseResult->fetch_assoc()) {
    $courseInUse[] = $row['Course_ID'];
}

// Close statements
$stmt->close();
$courseStmt->close();
$previousStmt->close();
$courseInUseStmt->close();

// Initialize message variable
$message = "";

// Check for form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the selected courses and doctor ID
    $selected_courses = $_POST['selected_courses'] ?? [];
    $doctor_id = $_POST['doctor_id'];

    // Ensure exactly 2 courses are selected
    if (count($selected_courses) != 2) {
        $message = "Error: You must select exactly 2 courses.";
    } else {
        // Prepare SQL to insert selected courses for the doctor
        $insertQuery = "INSERT INTO doctor_courses (Doctor_ID, Course_ID) VALUES (?, ?)";
        $insertStmt = $conn->prepare($insertQuery);

        // Track success of inserts
        $insertsSuccessful = true;

        foreach ($selected_courses as $course_id) {
            // Check if the course is already taken
            if (in_array($course_id, $courseInUse)) {
                $message = "Error: Course ID $course_id is already taken by another doctor.";
                $insertsSuccessful = false;
                break; // Break out of the loop if a conflict is found
            }
            // Check if the doctor has already selected this course
            if (in_array($course_id, $previouslySelectedCourses)) {
                $message = "Error: You have already selected Course ID $course_id.";
                $insertsSuccessful = false;
                break; // Break out of the loop if a conflict is found
            }
            $insertStmt->bind_param("ii", $doctor_id, $course_id);
            if (!$insertStmt->execute()) {
                $message = "Failed to insert course ID $course_id for doctor ID $doctor_id: " . $insertStmt->error . "<br>";
                $insertsSuccessful = false;
            }
        }

        // Close the statement
        $insertStmt->close();
        $conn->close();

        if ($insertsSuccessful) {
            $message = "Courses have been successfully saved for the doctor.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard with Calendar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Style.css">
    <style>
        /* Style for submit button */
        .submit-button {
            background-color: #4f46e5; /* Primary color */
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .submit-button:hover {
            background-color: #3b37c8; /* Darker shade on hover */
        }

        /* Notification message styling */
        .notification {
            background-color: #f8d7da; /* Light red */
            color: #721c24; /* Dark red */
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin: 20px 0;
            border-radius: 5px;
        }

        /* Highlight selected courses */
        .selected-course {
            background-color: #d1e7dd; /* Light green */
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    <script src="sidebar.js"></script>

    <div class="main-content">
        <h1>Welcome, <?php echo htmlspecialchars($teacher_data['Teacher_name']); ?>!</h1>
        
        <!-- Display notification message -->
        <?php if ($message): ?>
            <div class="notification"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <h2>Select Your Courses:</h2>

        <form method="POST" action="">
            <table>
                <tr>
                    <th>Select</th>
                    <th>Course ID</th>
                    <th>Course Name</th>
                </tr>
                <?php foreach ($courses as $course): ?>
                <tr class="<?php echo in_array($course['Course_ID'], $previouslySelectedCourses) ? 'selected-course' : ''; ?>">
                    <td>
                        <input type="checkbox" name="selected_courses[]" value="<?php echo htmlspecialchars($course['Course_ID']); ?>"
                        <?php echo in_array($course['Course_ID'], $previouslySelectedCourses) || in_array($course['Course_ID'], $courseInUse) ? 'disabled' : ''; ?>>
                    </td>
                    <td><?php echo htmlspecialchars($course['Course_ID']); ?></td>
                    <td><?php echo htmlspecialchars($course['Course_Name']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <input type="hidden" name="doctor_id" value="<?php echo htmlspecialchars($teacher_id); ?>">
            <input type="submit" class="submit-button" value="Submit Courses">
        </form>
    </div>

    <script>
        function validateForm() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
            if (checkboxes.length !== 2) {
                alert("Please select exactly 2 courses.");
                return false;
            }
            return true;
        }
    </script>

</body>
</html>
