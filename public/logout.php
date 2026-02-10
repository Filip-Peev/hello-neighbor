<?php
// public/logout.php
session_start();

// 1. Clear all session variables
$_SESSION = [];

// 2. Destroy the session cookie and data
session_destroy();

// 3. Send the user back to the home page
header("Location: index.php?page=home");
exit;