<?php
session_start();

// Destroy all sessions and redirect to login page
session_unset();
session_destroy();

header("Location: login.php");
exit();
?>
