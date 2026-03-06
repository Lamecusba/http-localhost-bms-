<?php
// config.php
session_start();

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'bms';

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Default avatar paths
$default_avatars = [
    'admin' => 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png',
    'driver' => 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png',
    'student' => 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png'
];

// Helper function for sanitizing input
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}
?>