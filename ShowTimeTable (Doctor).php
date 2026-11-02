<?php
// Include config file for session and database connection
include('config.php');

// Retrieve the Teacher_ID (Doctor_ID) from the session
$teacher_id = $_SESSION['user_id']; // Assuming 'user_id' holds the Teacher's ID

// Prepare SQL statement to retrieve teacher data
$sql = "SELECT Doctor_name AS Teacher_name, Doctor_department AS Teacher_department, Weekly_Teaching_Hours FROM doctor WHERE Doctor_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);

// Execute and fetch results
$stmt->execute();
$result = $stmt->get_result();
$teacher_data = $result->fetch_assoc();

if (!$teacher_data) {
    echo "Error: No data found for this Teacher.";
    exit();
}

// Initialize weekly time slots
$timeSlots = [
    "Sun" => ["08:00-08:50", "09:00-09:50", "10:00-10:50", "11:00-11:50", "12:00-12:50", "01:00-01:50", "02:00-02:50", "03:00-03:50", "04:00-04:50", "05:00-05:50"],
    "Mon" => ["08:00-08:50", "09:00-09:50", "10:00-10:50", "11:00-11:50", "12:00-12:50", "01:00-01:50", "02:00-02:50", "03:00-03:50", "04:00-04:50", "05:00-05:50"],
    "Tue" => ["08:00-08:50", "09:00-09:50", "10:00-10:50", "11:00-11:50", "12:00-12:50", "01:00-01:50", "02:00-02:50", "03:00-03:50", "04:00-04:50", "05:00-05:50"],
    "Wed" => ["08:00-08:50", "09:00-09:50", "10:00-10:50", "11:00-11:50", "12:00-12:50", "01:00-01:50", "02:00-02:50", "03:00-03:50", "04:00-04:50", "05:00-05:50"],
    "Thu" => ["08:00-08:50", "09:00-09:50", "10:00-10:50", "11:00-11:50", "12:00-12:50", "01:00-01:50", "02:00-02:50", "03:00-03:50", "04:00-04:50", "05:00-05:50"]
];

// Fetch the doctor’s timetable sections
$timetableQuery = "SELECT Section_ID, Course_Name, Course_LabOrTheory, TimeSlot FROM sections WHERE Doctor_ID = ?";
$timetableStmt = $conn->prepare($timetableQuery);
$timetableStmt->bind_param("i", $teacher_id);
$timetableStmt->execute();
$timetableResult = $timetableStmt->get_result();

$timetable = [];

// Process each section to organize it by day and time slot
while ($section = $timetableResult->fetch_assoc()) {
    $times = explode(", ", $section['TimeSlot']); // Separate multiple times
    foreach ($times as $timeEntry) {
        // Check if the entry is a lab with multiple hours separated by " - "
        if (strpos($timeEntry, '-') !== false) {
            // Lab times, separate individual time slots by " - "
            $dayAndTimes = explode(" ", $timeEntry, 2);
            $day = $dayAndTimes[0];
            $labTimes = explode(" - ", $dayAndTimes[1]);

            foreach ($labTimes as $labTime) {
                $timetable[$day][$labTime][] = [
                    "Course_Name" => $section['Course_Name'],
                    "Section_ID" => $section['Section_ID'],
                    "Course_LabOrTheory" => $section['Course_LabOrTheory']
                ];
            }
        } else {
            // Regular theory class
            [$day, $time] = explode(" ", $timeEntry);
            $timetable[$day][$time][] = [
                "Course_Name" => $section['Course_Name'],
                "Section_ID" => $section['Section_ID'],
                "Course_LabOrTheory" => $section['Course_LabOrTheory']
            ];
        }
    }
}

$timetableStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Timetable</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Style.css">
    <style>
        .no-class { background-color: lightgrey; }
        .class-cell { background-color: #4CAF50; color: white; padding: 8px; }
        .lab-cell { background-color: #2E7D32; color: white; padding: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #4f46e5; color: white; }
    </style>
</head>
<body>

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>
<script src="sidebar.js"></script>

<div class="main-content">
    <h1>Welcome, <?php echo htmlspecialchars($teacher_data['Teacher_name']); ?>!</h1>
    <h2>Your Weekly Timetable</h2>

    <table>
        <thead>
            <tr>
                <th>Day / Time</th>
                <?php foreach ($timeSlots['Sun'] as $slot): ?>
                    <th><?php echo $slot; ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($timeSlots as $day => $slots): ?>
                <tr>
                    <td><strong><?php echo $day; ?></strong></td>
                    <?php foreach ($slots as $slot): ?>
                        <td class="<?php echo isset($timetable[$day][$slot]) ? (isset($timetable[$day][$slot][0]['Course_LabOrTheory']) && $timetable[$day][$slot][0]['Course_LabOrTheory'] === 'Lab' ? 'lab-cell' : 'class-cell') : 'no-class'; ?>">
                            <?php if (isset($timetable[$day][$slot])): ?>
                                <?php 
                                    foreach ($timetable[$day][$slot] as $section) {
                                        echo "Sec: {$section['Section_ID']}<br>{$section['Course_Name']}<br>({$section['Course_LabOrTheory']})<br><br>";
                                    }
                                ?>
                            <?php else: ?>
                                No Class
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>

<?php $conn->close(); ?>
