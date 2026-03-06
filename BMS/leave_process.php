<?php
include 'conn.php';

if(isset($_POST['apply_id'])) {
    $id = $_POST['apply_id'];
    $new_status = $_POST['action']; // Matche the name in the HTML button

    // Update the leaveapply database
    $sql = "UPDATE leave_apply SET status = '$new_status' WHERE apply_id = '$id'";
    
    if(mysqli_query($con, $sql)) {
        header("Location: BMS_admin_busmanagement.php"); 
        exit();
    } else {
        echo "Error: " . mysqli_error($con);
    }
}
?>