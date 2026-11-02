<?php 
include 'config.php'; 

// Set default order and filter values
$filterDepartment = isset($_GET['department']) ? $_GET['department'] : '';
$filterLevel = isset($_GET['level']) ? $_GET['level'] : '';
$orderBy = isset($_GET['orderBy']) ? $_GET['orderBy'] : 'Course_ID';
$orderDir = isset($_GET['orderDir']) && $_GET['orderDir'] == 'desc' ? 'desc' : 'asc';

// 
$sql = "SELECT c1.Course_ID, c1.Course_Name, c1.Course_CreditHours, c1.Level_name, 
               c2.Course_Name AS Prerequisite_Name
        FROM course c1
        LEFT JOIN course c2 ON c1.Prerequisite_Course_ID = c2.Course_ID
        WHERE (c1.Department_name_forCourse LIKE '%$filterDepartment%' OR '$filterDepartment' = '') 
        AND (c1.Level_name LIKE '%$filterLevel%' OR '$filterLevel' = '')
        GROUP BY c1.Course_ID, c1.Course_Name, c1.Course_CreditHours, c1.Level_name, Prerequisite_Name
        ORDER BY $orderBy $orderDir";

$result = $conn->query($sql);
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
</head>
<body>

<?php include 'sidebar.php'; ?>
<script src="sidebar.js"></script>

<div class="main-content">
    <h1>Course List</h1>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>No.</th>
                <th>Course ID</th>
                <th>Course Name</th>
                <th>Credit Hours</th>
                <th>Level</th>
                <th>Prerequisite Course</th>
            </tr>

            <?php 
            $counter = 0;
            $totalCreditHours = 0;
            while ($row = $result->fetch_assoc()): 
                $counter++;
                $totalCreditHours += $row['Course_CreditHours'];
            ?>
                <tr>
                    <td><?= $counter ?></td>
                    <td><?= $row['Course_ID'] ?></td>
                    <td><?= $row['Course_Name'] ?></td>
                    <td><?= $row['Course_CreditHours'] ?></td>
                    <td><?= $row['Level_name'] ?></td>
                    <td><?= $row['Prerequisite_Name'] ?></td>
                </tr>
            <?php endwhile; ?>

            <tr>
                <td colspan="2"><strong>Total</strong></td>
                <td><strong><?= $counter ?></strong></td>
                <td><strong><?= $totalCreditHours ?></strong></td>
                <td colspan="2"></td>
            </tr>
        </table>
    <?php else: ?>
        <p>No courses found.</p>
    <?php endif; ?>

    <?php $conn->close(); ?>
</div>

</body>
</html>