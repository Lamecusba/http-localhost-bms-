
<?php
// logout.php
session_start();

// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'bms';

$conn = mysqli_connect($host, $user, $password, $database);

// Update driver status to 'inactive' if user is a driver
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'driver' && isset($_SESSION['user_id'])) {
    $driver_id = $_SESSION['user_id'];
    $update_sql = "UPDATE driver SET status = 'inactive' WHERE driver_id = '$driver_id'";
    mysqli_query($conn, $update_sql);
}

session_destroy();
header('Location: index.php');
exit;
?>
