<?php
include 'config.php'; // Includes database configuration to establish connection

$sqlFetchSections = "SELECT * FROM sections";
$resultFetchSections = $conn->query($sqlFetchSections); // Fetch all sections from the database

$sectionMessages = ""; // Initialize a variable to store messages about section assignments

// Fetch all available rooms for scheduling
$sqlAvailableRooms = "SELECT * FROM room";
$resultAvailableRooms = $conn->query($sqlAvailableRooms); // Query to retrieve all rooms

// Define time slots for theory sections, from 08:00 to 11:50 on each weekday (50-minute intervals)
$timeSlots = [
    "Sun" => ["08:00-08:50", "09:00-09:50", "10:00-10:50", "11:00-11:50"],
    "Mon" => ["08:00-08:50", "09:00-09:50", "10:00-10:50", "11:00-11:50"],
    "Tue" => ["08:00-08:50", "09:00-09:50", "10:00-10:50", "11:00-11:50"],
    "Wed" => ["08:00-08:50", "09:00-09:50", "10:00-10:50", "11:00-11:50"],
    "Thu" => ["08:00-08:50", "09:00-09:50", "10:00-10:50", "11:00-11:50"]
];

$days = array_keys($timeSlots); // Array of days (Sun, Mon, Tue, Wed, Thu) for iterating

// Only proceed with assignments if form is submitted via POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    ini_set('max_execution_time', 300); // Extend max execution time to avoid timeouts in long-running scripts

    // Store all available rooms in an array to use repeatedly in scheduling
    $rooms = [];
    while ($room = $resultAvailableRooms->fetch_assoc()) {
        $rooms[] = $room;
    }

    // Store all sections in an array, grouped by level for easier scheduling
    $sections = [];
    while ($section = $resultFetchSections->fetch_assoc()) {
        $level = $section['Level_Name']; // Use level name as a grouping key
        $sections[$level][] = $section;
    }

    // Initialize occupancy trackers to manage room and level usage
    $roomOccupancy = []; // Tracks occupied times in each room
    $levelOccupancy = []; // Tracks times used for each level to avoid conflicts

    // Loop through each level and its sections for scheduling
    foreach ($sections as $level => $levelSections) {
        foreach ($levelSections as $section) {
            $sectionID = $section['Section_ID'];
            $courseName = $section['Course_Name'];
            $creditHours = $section['Course_CreditHours'];
            $labOrTheory = $section['Course_LabOrTheory'];
            $roomAssigned = false; // Track if a room has been successfully assigned

            if ($labOrTheory == 'Theory') { // Only process theory sections in this logic block
                // Determine number of lectures based on credit hours: 3 credits = 2 lectures, 4 credits = 3 lectures
                $lecturesPerWeek = ($creditHours == 3) ? 2 : 3;

                $assignedSlots = []; // Array to track assigned times for this section
                $usedDays = [];  // Array to track days already used for this section to avoid duplicate scheduling

                // Attempt to assign a room and time slot for each lecture in the section
                foreach ($rooms as $room) {
                    if ($room['Room_LabOrTheory'] == 'Theory') { // Ensure room is suitable for theory
                        $roomID = $room['Room_ID'];
                        $roomName = $room['Room_name'];
                        $availableSeats = $room['Size'];

                        // Reset assigned slots and used days for each new room attempt
                        $assignedSlots = [];
                        $usedDays = [];

                        // Assign lectures on different days in this room
                        foreach ($days as $day) {
                            if (count($assignedSlots) >= $lecturesPerWeek) break; // Stop when required lectures are assigned

                            if (in_array($day, $usedDays)) continue; // Skip if day is already used for this section

                            // Initialize occupancy tracking for this room and level on the specific day
                            if (!isset($roomOccupancy[$roomID][$day])) {
                                $roomOccupancy[$roomID][$day] = [];
                            }
                            if (!isset($levelOccupancy[$level][$day])) {
                                $levelOccupancy[$level][$day] = [];
                            }

                            // Assign available time slot on the day if no conflicts
                            foreach ($timeSlots[$day] as $slot) {
                                if (!in_array($slot, $roomOccupancy[$roomID][$day]) && !in_array($slot, $levelOccupancy[$level][$day])) {
                                    $assignedSlots[] = "$day $slot"; // Record assigned slot
                                    $usedDays[] = $day; // Mark day as used

                                    // Mark the slot as occupied in room and level occupancy trackers
                                    $roomOccupancy[$roomID][$day][] = $slot;
                                    $levelOccupancy[$level][$day][] = $slot;
                                    break; // Move to the next day after assigning one slot per day
                                }
                            }
                        }

                        // After successful scheduling of required lectures, save assignment to the database
                        if (count($assignedSlots) === $lecturesPerWeek) {
                            $timeSlotString = implode(", ", $assignedSlots); // Format assigned slots for database

                            // Update section with assigned room, seats, and times
                            $sqlAssignRoomTime = "UPDATE sections 
                                                  SET Room_ID = $roomID, Room_name = '$roomName', Available_Seats = $availableSeats, TimeSlot = '$timeSlotString', Room_LabOrTheory = 'Theory'
                                                  WHERE Section_ID = $sectionID";
                            if ($conn->query($sqlAssignRoomTime)) {
                                $sectionMessages .= "Assigned Theory Section $sectionID (Course: $courseName) to Room $roomID at $timeSlotString.<br>";
                                $roomAssigned = true; // Mark the section as successfully assigned
                                break; // Move to the next section after successful assignment
                            } else {
                                // Log error if database update fails
                                $sectionMessages .= "Error assigning Section $sectionID: " . $conn->error . "<br>";
                                break; // Stop further attempts if an error occurs
                            }
                        }
                    }
                }

                // If unable to assign after trying all rooms and slots, log an error and stop further assignments
                if (!$roomAssigned) {
                    $sectionMessages .= "Unable to assign Theory Section $sectionID (Course: $courseName) after trying all rooms and slots. Stopping further assignment.<br>";
                    break; // Exit the section loop to avoid assigning further sections
                }
            }
        }
    }
}
?>




<?php
include 'config.php'; // Include database configuration to establish connection

// Fetch all lab sections from the `sections` table
$sqlFetchSections = "SELECT * FROM sections WHERE Course_LabOrTheory = 'Lab'";
$resultFetchSections = $conn->query($sqlFetchSections);

$sectionMessages = ""; // Initialize a variable to store feedback messages

// Fetch all available lab rooms from the `room` table
$sqlAvailableRooms = "SELECT * FROM room WHERE Room_LabOrTheory = 'Lab'";
$resultAvailableRooms = $conn->query($sqlAvailableRooms);

// Define time slots (12:00 to 05:50 PM) for lab sections for each weekday, in 50-minute intervals
$timeSlots = [
    "Sun" => ["12:00-12:50", "01:00-01:50", "02:00-02:50", "03:00-03:50", "04:00-04:50", "05:00-05:50"],
    "Mon" => ["12:00-12:50", "01:00-01:50", "02:00-02:50", "03:00-03:50", "04:00-04:50", "05:00-05:50"],
    "Tue" => ["12:00-12:50", "01:00-01:50", "02:00-02:50", "03:00-03:50", "04:00-04:50", "05:00-05:50"],
    "Wed" => ["12:00-12:50", "01:00-01:50", "02:00-02:50", "03:00-03:50", "04:00-04:50", "05:00-05:50"],
    "Thu" => ["12:00-12:50", "01:00-01:50", "02:00-02:50", "03:00-03:50", "04:00-04:50", "05:00-05:50"]
];

// Only proceed if the form is submitted via POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    ini_set('max_execution_time', 300); // Extend max execution time to avoid timeouts in long-running scripts

    // Store all available rooms in an array to avoid repeated database queries
    $rooms = [];
    while ($room = $resultAvailableRooms->fetch_assoc()) {
        $rooms[] = $room;
    }

    // Group all lab sections by level for easier scheduling
    $sections = [];
    while ($section = $resultFetchSections->fetch_assoc()) {
        $level = $section['Level_Name']; // Group by level name
        $sections[$level][] = $section;
    }

    $days = array_keys($timeSlots); // Array of days (Sun, Mon, Tue, Wed, Thu) for scheduling
    $totalRooms = count($rooms); // Total number of rooms available
    $totalDays = count($days); // Total number of days in the schedule

    $roomOccupancy = []; // Track occupied time slots for each room
    $levelOccupancy = []; // Track time slots used for each level to avoid conflicts
    $unassignedSections = []; // Track sections that could not be assigned

    // Attempt to assign each section to a room and timeslot
    foreach ($sections as $level => $levelSections) {
        foreach ($levelSections as $section) {
            $sectionID = $section['Section_ID'];
            $courseName = $section['Course_Name'];
            $creditHours = $section['Course_CreditHours'];
            $requiredDuration = $creditHours == 3 ? 2 : 3;  // 2 slots for 3 credit hours, 3 slots for higher

            // Initialize trackers for room and day assignments
            $currentDayIndex = 0;
            $currentRoomIndex = 0;
            $attempts = 0;
            $roomAssigned = false;

            // Loop through rooms and days to find an available time slot
            while (!$roomAssigned && $attempts < $totalRooms * $totalDays) {
                $room = $rooms[$currentRoomIndex];
                $day = $days[$currentDayIndex];

                // Ensure room is suitable for lab sections
                if ($room['Room_LabOrTheory'] == 'Lab') {
                    $roomID = $room['Room_ID'];
                    $roomName = $room['Room_name'];
                    $availableSeats = $room['Size'];

                    // Initialize occupancy trackers for the current room and level if not already set
                    if (!isset($roomOccupancy[$roomID][$day])) $roomOccupancy[$roomID][$day] = [];
                    if (!isset($levelOccupancy[$level][$day])) $levelOccupancy[$level][$day] = [];

                    // Check for available slots on the current day
                    $slots = $timeSlots[$day];
                    for ($i = 0; $i <= count($slots) - $requiredDuration; $i++) {
                        $slotSequence = array_slice($slots, $i, $requiredDuration); // Select the required sequence of slots
                        $allSlots = implode(" - ", $slotSequence); // Combine slots for the final time display
                        $proposedTimeSlot = "$day $allSlots";

                        // Check if the slot sequence is free in both room and level schedules
                        $overlap = false;
                        foreach ($slotSequence as $slot) {
                            if (in_array($slot, $roomOccupancy[$roomID][$day]) || in_array($slot, $levelOccupancy[$level][$day])) {
                                $overlap = true;
                                break;
                            }
                        }

                        // If no conflicts, assign the section to this room and time slot
                        if (!$overlap) {
                            $sqlAssignRoomTime = "UPDATE sections 
                                                  SET Room_ID = $roomID, Room_name = '$roomName', Available_Seats = $availableSeats, TimeSlot = '$proposedTimeSlot', Room_LabOrTheory = 'Lab'
                                                  WHERE Section_ID = $sectionID";
                            if ($conn->query($sqlAssignRoomTime)) {
                                $sectionMessages .= "Assigned Lab Section $sectionID (Course: $courseName) to Room $roomID at $proposedTimeSlot.<br>";

                                // Update occupancy trackers with the assigned slots
                                foreach ($slotSequence as $slot) {
                                    $roomOccupancy[$roomID][$day][] = $slot;
                                    $levelOccupancy[$level][$day][] = $slot;
                                }
                                $roomAssigned = true; // Mark the section as assigned
                                break;
                            } else {
                                $sectionMessages .= "Error assigning Section $sectionID: " . $conn->error . "<br>";
                            }
                        }
                    }
                }

                // Move to the next room and day in the schedule rotation
                $currentRoomIndex = ($currentRoomIndex + 1) % $totalRooms;
                if ($currentRoomIndex == 0) {
                    $currentDayIndex = ($currentDayIndex + 1) % $totalDays;
                }
                $attempts++;
            }

            // If unable to assign after all attempts, add to unassigned sections list
            if (!$roomAssigned) {
                $unassignedSections[] = $section;
            }
        }
    }

    // Retry assignment for any unassigned sections with a similar loop
    if (!empty($unassignedSections)) {
        foreach ($unassignedSections as $section) {
            $sectionID = $section['Section_ID'];
            $courseName = $section['Course_Name'];
            $requiredDuration = $section['Course_CreditHours'] == 3 ? 2 : 3; // Duration based on credit hours
            $roomAssigned = false;

            // Retry logic with tracking variables reset
            $attempts = 0;
            $currentRoomIndex = 0;
            $currentDayIndex = 0;

            while (!$roomAssigned && $attempts < $totalRooms * $totalDays) {
                $room = $rooms[$currentRoomIndex];
                $day = $days[$currentDayIndex];

                // Ensure the room is lab-compatible
                if ($room['Room_LabOrTheory'] == 'Lab') {
                    $roomID = $room['Room_ID'];
                    $roomName = $room['Room_name'];
                    $availableSeats = $room['Size'];

                    // Initialize occupancy trackers if necessary
                    if (!isset($roomOccupancy[$roomID][$day])) $roomOccupancy[$roomID][$day] = [];
                    if (!isset($levelOccupancy[$section['Level_Name']][$day])) $levelOccupancy[$section['Level_Name']][$day] = [];

                    $slots = $timeSlots[$day];
                    for ($i = 0; $i <= count($slots) - $requiredDuration; $i++) {
                        $slotSequence = array_slice($slots, $i, $requiredDuration);
                        $allSlots = implode(" - ", $slotSequence);
                        $proposedTimeSlot = "$day $allSlots";

                        $overlap = false;
                        foreach ($slotSequence as $slot) {
                            if (in_array($slot, $roomOccupancy[$roomID][$day]) || in_array($slot, $levelOccupancy[$section['Level_Name']][$day])) {
                                $overlap = true;
                                break;
                            }
                        }

                        if (!$overlap) {
                            $sqlAssignRoomTime = "UPDATE sections 
                                                  SET Room_ID = $roomID, Room_name = '$roomName', Available_Seats = $availableSeats, TimeSlot = '$proposedTimeSlot', Room_LabOrTheory = 'Lab'
                                                  WHERE Section_ID = $sectionID";
                            if ($conn->query($sqlAssignRoomTime)) {
                                $sectionMessages .= "Reassigned Lab Section $sectionID (Course: $courseName) to Room $roomID at $proposedTimeSlot.<br>";
                                foreach ($slotSequence as $slot) {
                                    $roomOccupancy[$roomID][$day][] = $slot;
                                    $levelOccupancy[$section['Level_Name']][$day][] = $slot;
                                }
                                $roomAssigned = true;
                                break;
                            } else {
                                $sectionMessages .= "Error reassigning Section $sectionID: " . $conn->error . "<br>";
                            }
                        }
                    }
                }

                $currentRoomIndex = ($currentRoomIndex + 1) % $totalRooms;
                if ($currentRoomIndex == 0) {
                    $currentDayIndex = ($currentDayIndex + 1) % $totalDays;
                }
                $attempts++;
            }

            if (!$roomAssigned) {
                $sectionMessages .= "Unable to reassign Lab Section $sectionID (Course: $courseName) after retry.<br>";
            }
        }
    }
}
?>



<?php



// Escape user input to avoid SQL injection
$filterDepartment = isset($_GET['department']) ? $conn->real_escape_string($_GET['department']) : '';
$filterLevel = isset($_GET['level']) ? $conn->real_escape_string($_GET['level']) : '';
$filterCourseName = isset($_GET['course_name']) ? $conn->real_escape_string($_GET['course_name']) : '';
$filterLabOrTheory = isset($_GET['course_type']) ? $conn->real_escape_string($_GET['course_type']) : '';
$orderBy = isset($_GET['orderBy']) ? $conn->real_escape_string($_GET['orderBy']) : 'Section_ID';
$orderDir = isset($_GET['orderDir']) && $_GET['orderDir'] == 'desc' ? 'desc' : 'asc';

// Prepare SQL query to fetch distinct sections
$sql = "SELECT DISTINCT sections.Section_ID, sections.Course_ID, sections.Course_Name, sections.Course_LabOrTheory, 
        sections.Course_CreditHours, sections.Level_Name, sections.Doctor_department, sections.Doctor_ID, 
        sections.Doctor_name, sections.Room_ID, sections.Room_name, sections.TimeSlot, sections.Available_Seats, 
        course.Department_name_forCourse 
        FROM sections
        JOIN course ON sections.Course_ID = course.Course_ID
        WHERE (course.Department_name_forCourse LIKE '%$filterDepartment%' OR '$filterDepartment' = '')
        AND (sections.Level_Name LIKE '%$filterLevel%' OR '$filterLevel' = '')
        AND (sections.Course_Name LIKE '%$filterCourseName%' OR '$filterCourseName' = '')";

// Only apply Lab/Theory filter if a value is provided
if ($filterLabOrTheory !== '') {
    $sql .= " AND sections.Course_LabOrTheory = '$filterLabOrTheory'";
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
    <title>Assign Times and Rooms</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Style.css">

    <style>
        /* Add your CSS styling here */
        #assignTimesRooms {
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

        #assignTimesRooms:hover {
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
            <a href="AssignTimesAndRooms.php" class="active">Assign Times & Rooms</a>
            <a href="AssignDoctors.php">Assign Doctors</a>
            <a href="AssignSectionsToStudents.php">Assign Sections to Students</a>
        </div>

        <!-- Form to assign times and rooms -->
        <form method="POST" action="">
            <input type="submit" id="assignTimesRooms" value="Assign Times and Rooms">
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