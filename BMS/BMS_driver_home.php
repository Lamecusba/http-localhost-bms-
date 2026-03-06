<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'driver') {
    header('Location: index.php');
    exit;
}

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'bms';

$conn = mysqli_connect($host, $user, $password, $database);
date_default_timezone_set('Asia/Kuala_Lumpur');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get driver data
$driver_id = $_SESSION['user_id'];
$sql = "SELECT * FROM driver WHERE driver_id = '$driver_id'";
$result = mysqli_query($conn, $sql);
$driver = mysqli_fetch_assoc($result);

// Get assigned bus (but we won't display it in a card)
$bus_sql = "SELECT * FROM bus WHERE driver_id = '$driver_id'";
$bus_result = mysqli_query($conn, $bus_sql);
$bus = mysqli_fetch_assoc($bus_result);

// Get current time
$current_time = date('H:i:s');

// Get today's schedule - only future schedules
if ($bus) {
    $today_schedule_sql = "SELECT bs.* FROM bus_schedule bs
                          WHERE bs.bus_id = '{$bus['bus_id']}' 
                          AND bs.date = CURDATE()
                          AND (
                        (time <= '$current_time' AND TIME_TO_SEC(TIMEDIFF('$current_time', time)) <= 1800) -- Within last 30 min
                        OR 
                        (time > '$current_time' AND TIME_TO_SEC(TIMEDIFF(time, '$current_time')) <= 1800) -- Within next 30 min
                        )
                        ORDER BY ABS(TIME_TO_SEC(TIMEDIFF(time, '$current_time')))
                        LIMIT 1";
    $today_schedule_result = mysqli_query($conn, $today_schedule_sql);
} else {
    $today_schedule_result = false;
}

// Calculate experience
$join_date = new DateTime($driver['join_date']);
$now = new DateTime();
$interval = $join_date->diff($now);
$experience = $interval->y . ' years & ' . $interval->m . ' months';

// Handle form submissions
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ... (keep your existing POST handling code)
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Driver specific styles */
        .driver-status {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }
        
        .status-active { background: #4CAF50; }
        .status-on-leave { background: #FF9800; }
        .status-inactive { background: #f44336; }
        
        .schedule-timeline {
            margin: 25px 0;
        }
        
        .timeline-item {
            display: flex;
            margin-bottom: 20px;
            position: relative;
        }
        
        .timeline-item:before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: -20px;
            width: 2px;
            background: #e9ecef;
        }
        
        .timeline-item:last-child:before {
            display: none;
        }
        
        .timeline-time {
            width: 60px;
            padding-right: 15px;
            text-align: right;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .timeline-content {
            flex: 1;
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-left: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
        }
        
        .timeline-location {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 5px 0;
            justify-content: center;
        }
        
        .schedule-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow);
            margin: 25px 0;
        }
        
        .schedule-card h3 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            color: var(--dark-color);
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-color);
        }
        
        .bus-info-mini {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            font-size: 0.9rem;
        }
        
        .bus-info-mini i {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        .bus-info-content {
            flex: 1;
        }
        
        .bus-info-content strong {
            display: block;
            margin-bottom: 5px;
            color: var(--light-color);
        }
        
        /* Current time indicator */
        .current-time-message {
            background: #e7f3ff;
            color: #0066cc;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.9rem;
        }
        
        /* Mobile specific */
        @media (max-width: 768px) {
            .timeline-item {
                flex-direction: column;
            }
            
            .timeline-item:before {
                left: 15px;
            }
            
            .timeline-content {
                margin-left: 40px;
            }
            
            .driver-status {
                flex-wrap: wrap;
            }
            
            .bus-info-mini {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .timeline-content {
                padding: 12px;
                margin-left: 30px;
            }
        }
        
        /* Dark mode */
        @media (prefers-color-scheme: dark) {
            .timeline-content, .schedule-card {
                background: #2d3748;
                color: #e2e8f0;
            }
            
            .bus-info-mini {
                background: #4a5568;
                color: #e2e8f0;
            }
            
            .current-time-message {
                background: #2c5282;
                color: #bee3f8;
            }
        }
    </style>
</head>
<body>
    <?php if ($message): ?>
        <div class="notification show <?php echo strpos($message, 'Error') !== false ? 'error' : ''; ?>" id="notification">
            <?php echo strpos($message, 'Error') !== false ? '❌' : '✅'; ?>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
        <script>
            setTimeout(() => {
                const notification = document.getElementById('notification');
                if (notification) {
                    notification.classList.remove('show');
                }
            }, 3000);
        </script>
    <?php endif; ?>
    
    <?php include 'BMS_header.php'; ?>

    <div class="container">
        <!-- Profile Section -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="avatar">
                    <?php
                    $avatar_path = isset($driver['avatar_path']) ? $driver['avatar_path'] : '';
                    if ($avatar_path && file_exists($avatar_path)) {
                        echo '<img src="' . htmlspecialchars($avatar_path) . '" alt="Driver Avatar">';
                    } else {
                        echo '<img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Default Avatar">';
                    }
                    ?>
                    <div class="avatar-upload" onclick="document.getElementById('avatarInput').click()">
                        <i class="fas fa-camera"></i>
                    </div>
                    <input type="file" id="avatarInput" style="display: none;" accept="image/*" onchange="uploadAvatar(this)">
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($driver['driver_name']); ?></h2>
                    <p><strong>Driver ID:</strong> <?php echo htmlspecialchars($driver_id); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($driver['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($driver['phone_num']); ?></p>
                    
                    <div class="driver-status">
                        <span class="status-indicator status-<?php echo $driver['status']; ?>"></span>
                        <span><strong>Status:</strong> <?php echo ucfirst($driver['status']); ?></span>
                        <span><strong>Experience:</strong> <?php echo htmlspecialchars($experience); ?></span>
                    </div>
                    
                    <!-- Minimal bus info inline in profile -->
                    <?php if($bus): ?>
                    <div class="bus-info-mini">
                        <i class="fas fa-bus"></i>
                        <div class="bus-info-content">
                            <strong>Assigned Bus: <?php echo htmlspecialchars($bus['bus_id']); ?></strong>
                            <span>Status: <span style="color: <?php echo $bus['status'] == 'active' ? '#4CAF50' : ($bus['status'] == 'maintenance' ? '#FF9800' : '#f44336'); ?>">
                                <?php echo ucfirst($bus['status']); ?>
                            </span></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Today's Schedule Card -->
        <div class="schedule-card">
            <h2>Today's Schedule</h2>
            
            <?php if($bus && mysqli_num_rows($today_schedule_result) > 0): ?>
            <div class="schedule-timeline">
                <?php 
                $hasSchedules = false;
                while($schedule = mysqli_fetch_assoc($today_schedule_result)): 
                    $hasSchedules = true;
                ?>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <div style="font-weight: 600; margin-bottom: 10px;display: flex; align-items: center; justify-content: center;">
                            Route #<?php echo $schedule['schedule_id']; ?>
                            <span style="font-size: 0.9rem; color: var(--gray-color); font-weight: normal; margin-left: 10px;">
                                (<?php echo date('h:i A', strtotime($schedule['time'])); ?>)
                            </span>
                        </div>
                        <div class="timeline-location">
                            <i class="fas fa-map-marker-alt" style="color: var(--success-color);"></i>
                            <span><strong>From:</strong> <?php echo $schedule['initial_station']; ?></span>
                        </div>
                        <div style="text-align: center; margin: 5px 0;display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-arrow-down" style="color: var(--primary-color);"></i>
                        </div>
                        <div class="timeline-location">
                            <i class="fas fa-flag-checkered" style="color: var(--danger-color);"></i>
                            <span><strong>To:</strong> <?php echo $schedule['terminal_station']; ?></span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
                
                <?php if (!$hasSchedules): ?>
                <div class="text-center p-4">
                    <div style="font-size: 2rem; margin-bottom: 10px; opacity: 0.3;">🕒</div>
                    <p style="color: var(--gray-color);">No more schedules for today</p>
                    <p style="color: var(--gray-color); font-size: 0.9rem;">All schedules for today have been completed</p>
                </div>
                <?php endif; ?>
            </div>
            <?php elseif($bus): ?>
            <div class="text-center p-4">
                <div style="font-size: 2rem; margin-bottom: 10px; opacity: 0.3;">📅</div>
                <p style="color: var(--gray-color);">No more schedules for today</p>
                <p style="color: var(--gray-color); font-size: 0.9rem;">All schedules for today have been completed</p>
            </div>
            <?php else: ?>
            <div class="text-center p-4">
                <div style="font-size: 2rem; margin-bottom: 10px; opacity: 0.3;">🚌</div>
                <p style="color: var(--gray-color);">No bus assigned yet</p>
                <p style="color: var(--gray-color); font-size: 0.9rem;">Contact administrator for bus assignment</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Action Grid -->
        <div class="actions-grid">
            <a class="action-btn" href="BMS_driver_route.php" style="text-decoration: none;">
                <span><i class="fas fa-route"></i> Route Guidance</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            
            <a class="action-btn" href="BMS_driver_schedule.php" style="text-decoration: none;">
                <span><i class="fas fa-calendar-alt"></i> Schedule</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            
            <a class="action-btn" href="BMS_driver_maintenance.php" style="text-decoration: none;">
                <span><i class="fas fa-tools"></i> Maintenance</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            
            <a class="action-btn" href="BMS_driver_leave.php" style="text-decoration: none;">
                <span><i class="fas fa-umbrella-beach"></i> Leave Application</span>
                <i class="fas fa-chevron-right"></i>
            </a>
            
            <a href="logout.php" class="action-btn full" style="text-decoration: none;">
                <span><i class="fas fa-sign-out-alt"></i> Log Out</span>
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>