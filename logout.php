<?php
// Start session
session_start();

// Destroy all session variables
session_unset();
session_destroy();

// Redirect to login page
header("Location: /CRS_V1.0/index.php");
exit();
?>
