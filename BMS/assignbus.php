<?php
include 'conn.php';

if(isset($_POST['bus_id']) && isset($_POST['driver_id'])) {
    $bus_id = $_POST['bus_id'];
    $driver_id = $_POST['driver_id'];
    // Update the bus table to link this bus to the driver
    $sql = "UPDATE bus SET driver_id = '$driver_id' WHERE bus_id = '$bus_id'";
    if(mysqli_query($con, $sql)) {
        header("Location: BMS_admin_busmanagement.php");
        exit();
    }
}
?>