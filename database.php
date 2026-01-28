<?php
$host = "localhost";
$user = "root";
$pass = ""; // default XAMPP
$db   = "monitoring_app"; // pastikan nama db sama di phpMyAdmin

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
