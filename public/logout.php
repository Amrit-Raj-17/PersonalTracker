<?php
session_start();
require_once '../includes/logVisit.php';

logVisit('logoutPage');
session_destroy();

header("Location: login.php");
exit;
?>