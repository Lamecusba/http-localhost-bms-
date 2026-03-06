<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header('Location: index.php');
    exit;
}

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'bms';

include 'conn.php';

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get student data
$student_id = $_SESSION['user_id'];
$sql = "SELECT * FROM student WHERE student_id = '$student_id'";
$result = mysqli_query($con, $sql);
$student = mysqli_fetch_assoc($result);

// Get student's active booking
$booking_sql = "SELECT 
                ss.*, 
                bs.date, 
                bs.time, 
                bs.bus_id, 
                bs.initial_station, 
                bs.terminal_station,
                b.driver_id,
                d.driver_name
               FROM `seat-student` ss 
               JOIN bus_schedule bs ON ss.schedule_id = bs.schedule_id 
               LEFT JOIN bus b ON bs.bus_id = b.bus_id
               LEFT JOIN driver d ON b.driver_id = d.driver_id
               WHERE ss.student_id = '$student_id' 
               AND ss.status = 'reserved' 
               AND bs.date >= CURDATE()
               ORDER BY bs.date, bs.time 
               LIMIT 1";
$booking_result = mysqli_query($con, $booking_sql);
$booking = mysqli_fetch_assoc($booking_result);

// Calculate estimated arrival time based on schedule time
if ($booking) {
    // Get schedule time
    $schedule_time = strtotime($booking['date'] . ' ' . $booking['time']);
    $current_time = time();
    
    // Calculate difference in minutes
    $time_diff_minutes = floor(($schedule_time - $current_time) / 60);
    
    if ($time_diff_minutes > 0) {
        $estimated_arrival = $time_diff_minutes . ' minutes';
    } else if ($time_diff_minutes == 0) {
        $estimated_arrival = 'Now';
    } else {
        $estimated_arrival = 'Departed ' . abs($time_diff_minutes) . ' minutes ago';
    }
} else {
    $estimated_arrival = null;
}

// Get available schedules
$schedules_sql = "SELECT bs.*, 
                  (SELECT COUNT(*) FROM `seat-student` ss WHERE ss.schedule_id = bs.schedule_id AND ss.status = 'booked') as booked_seats,
                  b.driver_id,
                  d.driver_name
                  FROM bus_schedule bs 
                  LEFT JOIN bus b ON bs.bus_id = b.bus_id
                  LEFT JOIN driver d ON b.driver_id = d.driver_id
                  WHERE bs.date >= CURDATE() 
                  AND bs.time >= CURTIME()
                  ORDER BY bs.date, bs.time";
$schedules_result = mysqli_query($con, $schedules_sql);

// Handle actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['cancel_reservation'])) {

        $cancel_sql = "DELETE FROM `seat-student` 
                       WHERE student_id = '$student_id' 
                       AND status = 'reserved'";
        
        if (mysqli_query($con, $cancel_sql)) {
            $message = 'Reservation cancelled successfully!';
            $booking = null;
            $estimated_arrival = null;
        } else {
            $message = 'Error: ' . mysqli_error($con);
        }
    }
    
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        
        .back-btn {
            display: none;
        }
        
        /* Keep existing responsive styles */
        @media (max-width: 768px) {
            
            .dashboard-cards {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .profile-header {
                gap: 15px;
            }
            
            .avatar {
                width: 100px;
                height: 100px;
            }
            
            .profile-info h2 {
                font-size: 1.3rem;
            }
            
            .detail-row {
                flex-direction: column;
                gap: 5px;
                padding: 10px 0;
            }
            
            .arrival-time {
                font-size: 2.5rem;
            }
            
            .action-btn {
                padding: 15px;
                min-height: 70px;
            }
            
            .action-btn img {
                width: 28px;
                height: 28px;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }
            
            .profile-card, .bus-info-card {
                padding: 15px;
            }
            
            .arrival-widget {
                padding: 20px;
            }
            
            .arrival-time {
                font-size: 2rem;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <?php if ($message): ?>
        <div class="notification <?php echo strpos($message, 'Error') !== false ? 'error' : ''; ?>" id="notification">
            <?php echo strpos($message, 'Error') !== false ? '❌' : '✅'; ?>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
        <script>
            setTimeout(() => {
                const notification = document.getElementById('notification');
                if (notification) {
                    notification.classList.add('show');
                    setTimeout(() => {
                        notification.classList.remove('show');
                        if(window.history.replaceState) {
                            window.history.replaceState(null, null, window.location.pathname);
                        }
                    }, 3000);
                }
            }, 100);
        </script>
    <?php endif; ?>
    <?php include 'BMS_header.php'; ?>

    <div class="container">
        <div class="dashboard-cards">
            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-header">
                    <div class="avatar">
                        <?php
                        $avatar_path = isset($student['avatar_path']) ? $student['avatar_path'] : '';
                        if ($avatar_path && file_exists($avatar_path)) {
                            echo '<img src="' . htmlspecialchars($avatar_path) . '" alt="Student Avatar">';
                        } else {
                            echo '<img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Default Avatar">';
                        }
                        ?>
                        <div class="avatar-upload" onclick="document.getElementById('avatarInput').click()">
                            Change
                        </div>
                        <input type="file" id="avatarInput" style="display: none;" accept="image/*" onchange="uploadAvatar(this)">
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($student['student_name']); ?></h2>
                        <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student_id); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['phone_num']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Bus Info Card -->
            <div class="bus-info-card">
                <div class="bus-header">
                    <h3>📍 Booked Bus</h3>
                    <?php if($booking): ?>
                        <span class="bus-status active">Active</span>
                    <?php else: ?>
                        <span class="bus-status maintenance">No Booking</span>
                    <?php endif; ?>
                </div>
                
                <?php if($booking): ?>
                    <div class="bus-details">
                        <div class="detail-row">
                            <span class="detail-label">Bus ID:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($booking['bus_id']); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Driver:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($booking['driver_name'] ?? 'Not assigned'); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Estimated Arrival:</span>
                            <span class="detail-value" style="color: <?php echo (strpos($estimated_arrival, 'Departed') === false && $estimated_arrival != 'Now') ? '#4CAF50' : '#f44336'; ?>; font-weight: bold;">
                                <?php echo $estimated_arrival; ?>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Schedule Time:</span>
                            <span class="detail-value">
                                <?php echo date('h:i A', strtotime($booking['time'])); ?>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Route:</span>
                            <span class="detail-value">
                                <?php echo htmlspecialchars($booking['initial_station']); ?> → 
                                <?php echo htmlspecialchars($booking['terminal_station']); ?>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Seat Number:</span>
                            <span class="detail-value">#<?php echo $booking['seat_id']; ?></span>
                        </div>
                    </div>
                    
                    <form method="POST" action="" class="mt-4">
                        <button type="submit" name="cancel_reservation" class="btn btn-danger btn-block" 
                                onclick="return confirm('Cancel this reservation?')">
                            Cancel Reservation
                        </button>
                    </form>
                <?php else: ?>
                    <div class="text-center p-4">
                        <div style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;">🚌</div>
                        <h4>No Active Booking</h4>
                        <p class="mt-2" style="color: var(--gray-color);">Book a bus to see details here</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Actions -->
        <div class="actions-grid">
            <a class="action-btn" href="BMS_student_reservationpage.php" style="text-decoration: none;">
                <span>Bus Booking</span>
                <img src="https://cdn-icons-png.flaticon.com/512/61/61088.png" alt="Booking">
            </a>
            
            <a class="action-btn" href="logout.php" style="text-decoration: none;">
                <span>Log Out</span>
                <img src="https://cdn-icons-png.flaticon.com/512/1828/1828479.png" alt="Logout">
            </a>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($con); ?>