<?php
session_start();
include 'BMS_header.php';
include("conn.php");  // Added to connect to database

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header('Location: index.php');
    exit;
}

$student_id = $_SESSION['user_id'];

// Check if user has a reservation
$checkReservationQuery = mysqli_query($con,
    "SELECT * FROM `seat-student` 
     WHERE student_id = '$student_id' 
     AND (status = 'reserved')"
);

// If NO reservation exists, redirect directly to reservation page
if (mysqli_num_rows($checkReservationQuery) === 0) {
    header('Location: BMS_student_reservationpage.php');
    exit;
}

// Handle cancel button click
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservation'])) {
    // Delete the reservation
    $cancelQuery = mysqli_query($con,
        "DELETE FROM `seat-student` 
         WHERE student_id = '$student_id' 
         AND (status = 'reserved')"
    );
    
    if ($cancelQuery) {
        // Successfully cancelled, redirect to reservation page
        header('Location: BMS_student_reservationpage.php');
        exit;
    } else {
        $error_message = "Failed to cancel reservation. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reservation Warning</title>

  <style>
    *{box-sizing:border-box;font-family:Arial,Helvetica,sans-serif}
    body{margin:0;background:#08b7f0}

    .card{
      background:#fff;
      border-radius:20px;
      padding:32px 36px;
      width:480px;
      margin:120px auto;
      box-shadow:0 10px 30px rgba(0,0,0,.2);
      text-align:center
    }

    h2{margin-bottom:16px}
    
    .error {
        color: red;
        margin-bottom: 15px;
    }

    .actions{
      margin-top:20px;
      display:flex;
      gap:12px;
      justify-content:center
    }

    .btn{
      padding:10px 22px;
      border:none;
      border-radius:10px;
      cursor:pointer;
      font-weight:bold;
      text-decoration: none;
      display: inline-block;
      text-align: center;
    }
    
    .btn-return {
        background:#f0f0f0;
        color: #333;
    }

    .btn-red{
        background:#ff2b2b;
        color:#fff;
    }
    
    .btn-red:hover {
        background:#e02626;
    }
    
    form {
        display: inline;
    }

    @media screen and (max-width: 768px){

      /* ===== Card ===== */
      .card{
          width: 92%;
          margin: 60px auto;
          padding: 24px 20px;
          border-radius: 16px;
      }

      /* ===== Title ===== */
      h2{
          font-size: 22px;
      }

      /* ===== Text ===== */
      .card p{
          font-size: 15px;
          line-height: 1.5;
      }

      /* ===== Buttons ===== */
      .actions{
          flex-direction: column;
          gap: 10px;
          margin-top: 18px;
      }

      .btn{
          width: 100%;
          padding: 12px;
          font-size: 15px;
      }
  }
  </style>
</head>

<body>

<div class="card">
  <h2>Bus Booking</h2>
  
  <?php if (isset($error_message)): ?>
    <div class="error"><?php echo $error_message; ?></div>
  <?php endif; ?>
  
  <p>
    You can only book one bus at a time.<br>
    Cancel the previous reservation to make a new one.
  </p>

  <div class="actions">
    <a href="BMS_student_home.php" class="btn btn-return">Return</a>
    
    <!-- Form for cancelling reservation -->
    <form method="post" onsubmit="return confirm('Are you sure you want to cancel your existing reservation?');">
        <button type="submit" name="cancel_reservation" class="btn btn-red">Cancel Reservation</button>
    </form>
  </div>
</div>

</body>
</html>