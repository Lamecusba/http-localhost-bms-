<?php
include 'conn.php';

if(isset($_POST['bus_id'])) {
    $bus_id = $_POST['bus_id'];
    // Set driver id to NULL to remove the bus from the driver
    $sql = "UPDATE bus SET driver_id = NULL WHERE bus_id = '$bus_id'";
    if(mysqli_query($con, $sql)) {
        header("Location: BMS_admin_busmanagement.php"); 
        exit();
    }
}
?>