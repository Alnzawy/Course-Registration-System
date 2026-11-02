<?php

session_start();
include('config.php'); // Database connection

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userID = $_POST['username'];
    $userPass = $_POST['password'];

    // Query to check user credentials and retrieve user type
    $sql = "SELECT User_ID, User_Type FROM user WHERE (User_ID = ? OR Username = ?) AND Password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $userID, $userID, $userPass);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($loggedInUserID, $userType);
        $stmt->fetch();

        // Store User ID and Type in the session
        $_SESSION['user_id'] = $loggedInUserID;
        $_SESSION['user_type'] = $userType;

        // Redirect based on user type
        if ($userType === 'Admin') {
            header("Location: /CRS_V1.0/Admin/index.php");
        } elseif ($userType === 'Student') {
            header("Location: /CRS_V1.0/Student/index.php");
        } elseif ($userType === 'Doctor') {
            header("Location: /CRS_V1.0/Doctor/");
        } else {
            $message = "Invalid user type.";
        }
        exit();
    } else {
        $message = "Invalid ID/Username or Password";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Course Registration System</title>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link href="Login_Style.css" rel="stylesheet">


</head>

<body>


    <div class="background">
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <!-- Login form container -->
    <form method="post" action="">

        <h3>Login Here</h3>

        <!-- Username input field with label -->
        <label for="username">ID or Username</label>
        <input type="text" placeholder="Enter ID or Username" name="username" id="username" required>
        <!-- Required field for user ID or username -->

        <!-- Password input field with label -->
        <label for="password">Password</label>
        <input type="password" placeholder="Password" name="password" id="password" required>
        <!-- Required field for password -->

        <!-- Checkbox to toggle password visibility -->
        <div class="checkbox-container">
            <input type="checkbox" onclick="myFunction()">
            <!-- Checkbox triggers JavaScript function to toggle visibility -->
            <label>Show Password</label>
        </div>

        <!-- Submit button to log in -->
        <button type="submit">Log In</button>

        <!-- Display message box for error or success messages -->
        <?php if (!empty($message)): ?>
            <div class="message-box"><?php echo $message; ?></div> <!-- Displays message if $message is not empty -->
        <?php endif; ?>
    </form>

    <!-- JavaScript function to toggle password visibility -->
    <script>
        function myFunction() {
            var x = document.getElementById("password");
            x.type = (x.type === "password") ? "text" : "password"; // Toggles between 'text' and 'password' types
        }
    </script>
</body>

</html>