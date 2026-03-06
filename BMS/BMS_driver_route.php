<?php 
session_start();
include 'BMS_header.php'; 
include 'conn.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'driver') {
    header('Location: index.php');
    exit;
}

date_default_timezone_set('Asia/Kuala_Lumpur');

$driver_id = $_SESSION['user_id'];
$current_time = date('H:i:s');
$current_datetime = date('Y-m-d H:i:s');

// Get assigned bus
$bus_sql = "SELECT bus_id FROM bus WHERE driver_id = '$driver_id'";
$bus_result = mysqli_query($con, $bus_sql);
$bus_row = mysqli_fetch_assoc($bus_result);

$bus_id = $bus_row['bus_id'] ?? null;
$active_schedule = null;
$route_details = null;
$roads = [];
$from_to_text = '';
$active_roads = [];
$total_distance = 0;
$eta = '';
$student_count = 0;
$message = '';

// If bus is assigned, find active schedule
if ($bus_id) {
    // Find the closest schedule within the next 30 minutes
    $schedule_sql = "SELECT * FROM bus_schedule 
                    WHERE bus_id = '$bus_id' 
                    AND date = CURDATE()
                    AND (
                (time <= '$current_time' AND TIME_TO_SEC(TIMEDIFF('$current_time', time)) <= 1800) -- Within last 30 min
                OR 
                (time > '$current_time' AND TIME_TO_SEC(TIMEDIFF(time, '$current_time')) <= 1800) -- Within next 30 min
              )
              ORDER BY ABS(TIME_TO_SEC(TIMEDIFF(time, '$current_time')))
              LIMIT 1";
    
    $schedule_result = mysqli_query($con, $schedule_sql);
    $active_schedule = mysqli_fetch_assoc($schedule_result);
    
    if ($active_schedule) {
        $from = $active_schedule['initial_station'];
        $to = $active_schedule['terminal_station'];
        $from_to_text = "$from → $to";
        $schedule_id = $active_schedule['schedule_id'];
        
        // Find corresponding route
        $route_sql = "SELECT * FROM route 
                     WHERE initial_station = '$from' 
                     AND terminal_station = '$to' 
                     AND status = 'active' 
                     LIMIT 1";
        $route_result = mysqli_query($con, $route_sql);
        $route_details = mysqli_fetch_assoc($route_result);
        
        if ($route_details) {
            $route_id = $route_details['route_id'];
            
            // Get roads for this route
            $roads_sql = "SELECT rr.*, r.road_name, r.road_length 
                         FROM `route-road` rr 
                         JOIN road r ON rr.road_id = r.road_id 
                         WHERE rr.route_id = $route_id 
                         ORDER BY rr.road_order";
            $roads_result = mysqli_query($con, $roads_sql);
            
            while ($road = mysqli_fetch_assoc($roads_result)) {
                $roads[] = $road;
                $total_distance += $road['road_length'];
                $active_roads[] = $road['road_id'];
            }
            
            // Calculate ETA (assuming average speed of 40 km/h)
            if ($total_distance > 0) {
                $avg_speed_kmh = 40; // Average bus speed
                $travel_time_hours = $total_distance / $avg_speed_kmh;
                $travel_time_minutes = $travel_time_hours * 60;
                $arrival_time = strtotime($active_schedule['time']) + ($travel_time_minutes * 60);
                $eta = date('h:i a', (int)$arrival_time);
            }
            
            // Count students on board for this schedule
            $student_count_sql = "SELECT COUNT(*) as count FROM `seat-student` 
                                 WHERE schedule_id = $schedule_id 
                                 AND status = 'Attend'";
            $student_count_result = mysqli_query($con, $student_count_sql);
            $student_count_row = mysqli_fetch_assoc($student_count_result);
            $student_count = $student_count_row['count'];
        }
    }
}

// Handle student ID submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['student_id']) && $bus_id && $active_schedule) {
    $student_id = mysqli_real_escape_string($con, $_POST['student_id']);
    $schedule_id = $active_schedule['schedule_id'];
    
    // Check if student exists
    $student_check = "SELECT * FROM student WHERE student_id = '$student_id'";
    $student_result = mysqli_query($con, $student_check);
    
    if (mysqli_num_rows($student_result) == 0) {
        $message = "Student ID not found!";
    } else {
        // Check if student already onboard
        $onboard_check = "SELECT * FROM `seat-student` 
                         WHERE student_id = '$student_id' 
                         AND schedule_id = $schedule_id 
                         AND status = 'Attend'";
        $onboard_result = mysqli_query($con, $onboard_check);
        
        if (mysqli_num_rows($onboard_result) > 0) {
            $message = "Student already onboard!";
        } else {
            // First check for reserved seats
            $reserved_check = "SELECT * FROM `seat-student` 
                              WHERE student_id = '$student_id' 
                              AND schedule_id = $schedule_id 
                              AND status = 'reserved'";
            $reserved_result = mysqli_query($con, $reserved_check);
            
            if (mysqli_num_rows($reserved_result) > 0) {
                // Update reserved to onboard
                $update_sql = "UPDATE `seat-student` 
                              SET status = 'Attend' 
                              WHERE student_id = '$student_id' 
                              AND schedule_id = $schedule_id";
                mysqli_query($con, $update_sql);
                $message = "Reserved student marked as onboard!";
            } else {
                // Find available seat
                $all_seats = range(1, 44);
                
                // Get occupied seats
                $occupied_sql = "SELECT seat_id FROM `seat-student` 
                                WHERE schedule_id = $schedule_id 
                                AND status IN ('Attend', 'reserved')";
                $occupied_result = mysqli_query($con, $occupied_sql);
                $occupied_seats = [];
                while ($row = mysqli_fetch_assoc($occupied_result)) {
                    $occupied_seats[] = $row['seat_id'];
                }
                
                // Find first available seat
                $available_seat = null;
                foreach ($all_seats as $seat) {
                    if (!in_array($seat, $occupied_seats)) {
                        $available_seat = $seat;
                        break;
                    }
                }
                
                if ($available_seat) {
                    // Add student to available seat
                    $insert_sql = "INSERT INTO `seat-student` (student_id, seat_id, schedule_id, status) 
                                  VALUES ('$student_id', $available_seat, $schedule_id, 'Attend')";
                    mysqli_query($con, $insert_sql);
                    $message = "Student added to seat $available_seat!";
                } else {
                    $message = "No available seats!";
                }
            }
            
            // Refresh student count
            $student_count_sql = "SELECT COUNT(*) as count FROM `seat-student` 
                                 WHERE schedule_id = $schedule_id 
                                 AND status = 'Attend'";
            $student_count_result = mysqli_query($con, $student_count_sql);
            $student_count_row = mysqli_fetch_assoc($student_count_result);
            $student_count = $student_count_row['count'];
            
            // Refresh page to update counts
            echo "<script>setTimeout(function(){ window.location.href = 'BMS_driver_route.php'; }, 1000);</script>";
        }
    }
}

// Get reserved students count
$reserved_count = 0;
if ($bus_id && $active_schedule) {
    $reserved_sql = "SELECT COUNT(*) as count FROM `seat-student` 
                     WHERE schedule_id = {$active_schedule['schedule_id']} 
                     AND status = 'reserved'";
    $reserved_result = mysqli_query($con, $reserved_sql);
    $reserved_row = mysqli_fetch_assoc($reserved_result);
    $reserved_count = $reserved_row['count'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Route Preview</title>
<script>
// Auto-refresh page every 60 seconds to check for new schedules
setTimeout(function() {
    window.location.reload();
}, 60000);
</script>

<style>
*{
    box-sizing:border-box;
    margin:0;
    padding:0;
    font-family:Arial, Helvetica, sans-serif;
}

body{
    background:#08b7f0;
}

/* ===== Layout ===== */
.container{
    display:flex;
    gap:30px;
    padding:30px;
}

/* ===== Main Card ===== */
.main{
    flex:3;
    background:#fff;
    border-radius:20px;
    padding:30px;
    box-shadow:0 10px 30px rgba(0,0,0,.2);
}

/* ===== Return Button ===== */
.back-btn{
    font-weight:bold;
    text-decoration:none;
    color:#333;
    margin-bottom:10px;
    display:inline-block;
    font-size:14px;
}


h2{
    margin-bottom:10px;
}

.route-info {
    color:#666;
    font-size:16px;
    margin-bottom:20px;
    padding:10px;
    background:#f0f8ff;
    border-radius:8px;
    font-weight:bold;
}

/* ===== Form ===== */
.form-row{
    display:flex;
    gap:40px;
    margin-bottom:20px;
    align-items:center;
}

label{
    font-weight:bold;
    margin-right:10px;
}

select{
    padding:6px 10px;
    border-radius:6px;
}

/* ===== Map Area ===== */
.map{
    display:flex;
    border:2px dashed #ccc;
    border-radius:16px;
    padding:20px;
    margin-top:10px;
}

.diagram{
    flex:1;
    display:flex;
    justify-content:center;
    align-items:center;
}

.caption{
    display: flex;
    align-items: center;
    flex-direction: column;
    justify-content: center;
    flex: 1;
    font-size: 100%;
}

.capwords{
    margin:2px;
    font-size:14px;
    font-weight:bold;
}

/* ===== Triangle Diagram ===== */
.triangle-container{
    position:relative;
    width:300px;
    height:260px;
}

/* Destinations */
.destination{
    position:absolute;
    font-weight:bold;
    background:#fff;
    padding:5px 10px;
    border-radius:5px;
}

.top-destination{
    top:0;
    left:50%;
    transform:translateX(-50%);
}

.left-destination{
    bottom:0;
    left:0;
    transform:translateX(-50%);
}

.right-destination{
    bottom:0;
    right:0;
    transform:translateX(50%);
}

/* ===== Road Buttons ===== */
.road{
    width: 100px;
    height: 5px;
    margin: 0;
    border-radius: 3px;
    border: none;
    background: #d9d9d9;
    font-weight:bold;
    cursor:pointer;
    position: absolute;
    transform-origin: 0 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #333;
}

.road.active {
    background: #00c8ff !important;
    box-shadow: 0 0 8px rgba(0, 200, 255, 0.5);
}

.road.g {top: 127px;left: 97px;transform: rotate(-60deg);}
.road.h {top: 130px;left: 100px;transform: rotate(0deg);}
.road.i {top: 41px;left: 155px;transform: rotate(60deg);}
.road.c {top: 224px;left: 42px;transform: rotate(-60deg);}
.road.d {top: 135px;left: 100px;transform: rotate(60deg);}
.road.e {top: 221px;left: 150px;transform: rotate(-60deg);}
.road.f {top: 135px;left: 208px;transform: rotate(60deg);}
.road.a {top: 225px;left: 46px;transform: rotate(0deg);}
.road.b {top: 225px;left: 155px;transform: rotate(0deg);}

/* ===== Info Card ===== */
.info-card{
    background:#fff;
    border-radius:20px;
    padding:20px;
    margin-top:20px;
    text-align:center;
    box-shadow:0 10px 30px rgba(0,0,0,.2);
}

.info-card h3{
    margin-bottom:10px;
}

/* Student Entry Form */
.student-entry {
    background:#fff;
    border-radius:20px;
    padding:20px;
    margin-top:20px;
    box-shadow:0 10px 30px rgba(0,0,0,.2);
}

.student-entry h3 {
    margin-bottom:15px;
    color:#333;
}

.student-form {
    display:flex;
    gap:10px;
}

.student-form input {
    flex:1;
    padding:10px;
    border-radius:8px;
    border:1px solid #ccc;
    font-size:14px;
}

.student-form button {
    background:#39ff14;
    border:none;
    padding:10px 20px;
    border-radius:8px;
    font-weight:bold;
    cursor:pointer;
}

.message {
    margin-top:10px;
    padding:10px;
    border-radius:6px;
    text-align:center;
    font-size:14px;
}

.message.success {
    background:#d4edda;
    color:#155724;
}

.message.error {
    background:#f8d7da;
    color:#721c24;
}

.message.info {
    background:#d1ecf1;
    color:#0c5460;
}

/* Status indicators */
.status-indicator {
    display:inline-block;
    padding:4px 8px;
    border-radius:12px;
    font-size:12px;
    font-weight:bold;
    margin-left:10px;
}

.status-active {
    background:#d4edda;
    color:#155724;
}

.status-inactive {
    background:#f8d9da;
    color:#721c24;
}

.status-pending {
    background:#fff3cd;
    color:#856404;
}

/* =========================
   DRIVER MOBILE VIEW FIX
   ========================= */

@media screen and (max-width: 1024px) {
    /* Map becomes vertical */
    .map{
        flex-direction:column;
        gap:20px;
    }
}

@media screen and (max-width: 768px){
    /* Force single-column layout */
    .container{
        flex-direction:column !important;
        padding:15px !important;
    }

    .main{
        width:100% !important;
        padding:20px !important;
    }

    /* Center and scale triangle */
    .diagram{
        justify-content:center;
    }

    .triangle-container{
        transform:scale(0.85);
    }
    
    .student-form {
        flex-direction:column;
    }
    
    .side-section {
        display:none;
        width:100% !important;
    }
}

/* Sidebar section */
.side-section {
    width:300px;
    display:flex;
    flex-direction:column;
    gap:20px;
}

</style>
</head>

<body>

<div class="container">

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main">
        <a href="BMS_driver_home.php" class="back-btn">← Return to Main Menu</a>
        <h2>Route Preview</h2>
        
        <?php if ($bus_id && $active_schedule): ?>
            <div class="route-info">
                Current Route: <?php echo $from_to_text; ?>
                <span class="status-indicator status-active">ACTIVE</span>
            </div>
        <?php elseif ($bus_id): ?>
            <div class="route-info">
                No active schedule found within 30 minutes
                <span class="status-indicator status-pending">WAITING</span>
            </div>
        <?php else: ?>
            <div class="route-info">
                No bus assigned
                <span class="status-indicator status-inactive">INACTIVE</span>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : (strpos($message, 'not found') !== false ? 'error' : 'success'); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- ===== MAP ===== -->
        <div class="map">
            <div class="diagram">
                <div class="triangle-container">

                    <div class="destination top-destination">Fortune</div>
                    <div class="destination left-destination">APU</div>
                    <div class="destination right-destination">LRT</div>

                    <?php
                    // Map road IDs to CSS classes
                    $road_mapping = [
                        1 => 'a', 2 => 'b', 3 => 'c', 4 => 'd',
                        5 => 'e', 6 => 'f', 7 => 'g', 8 => 'h', 9 => 'i'
                    ];
                    
                    // Generate road buttons
                    foreach ($road_mapping as $road_id => $road_class) {
                        $is_active = in_array($road_id, $active_roads) ? 'active' : '';
                        echo "<button class='road $road_class $is_active'>" . strtoupper($road_class) . "</button>";
                    }
                    ?>
                </div>
            </div>

            <div class="caption">
                <p class="capwords">A: Jalan Wangi</p>
                <p class="capwords">B: Jalan Melati</p>
                <p class="capwords">C: Jalan Mawar</p>
                <p class="capwords">D: Jalan Kenanga</p>
                <p class="capwords">E: Jalan Dahlia</p>
                <p class="capwords">F: Jalan Teratai</p>
                <p class="capwords">G: Jalan Anggerik</p>
                <p class="capwords">H: Jalan Cempaka</p>
                <p class="capwords">I: Jalan Seroja</p>
            </div>
        </div>
    </div>

    <!-- ===== SIDEBAR ===== -->
    <div class="side-section">
        <?php include 'BMS_driver_sidebar.php'; ?>

        <?php if ($bus_id && $active_schedule): ?>
        <!-- Student Entry Form -->
        <div class="student-entry">
            <h3>Student Boarding</h3>
            <form method="POST" class="student-form">
                <input type="text" name="student_id" placeholder="Enter Student ID" required maxlength="5">
                <button type="submit">Add</button>
            </form>
            <?php if ($reserved_count > 0): ?>
                <p style="font-size:12px; color:#666; margin-top:10px;">
                    <?php echo $reserved_count; ?> reserved student(s) pending
                </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="info-card">
            <?php if ($bus_id && $active_schedule && $route_details): ?>
                <h3>ETA: <?php echo $eta ?: 'Calculating...'; ?></h3>
                <p><?php echo round($travel_time_minutes ?? 0); ?> min &nbsp;&nbsp; <?php echo round($total_distance, 1); ?> km</p>
                <br>
                <h2><?php echo $student_count; ?> / 44</h2>
                <p>Students On Board</p>
                <?php if ($reserved_count > 0): ?>
                    <p style="font-size:12px; color:#666; margin-top:5px;">
                        +<?php echo $reserved_count; ?> reserved
                    </p>
                <?php endif; ?>
            <?php elseif ($bus_id): ?>
                <h3>No Active Route</h3>
                <p>Next schedule within 30 min will appear here</p>
                <br>
                <h2>0 / 44</h2>
                <p>Students On Board</p>
            <?php else: ?>
                <h3>No Bus Assigned</h3>
                <p>Please contact administrator</p>
                <br>
                <h2>0 / 44</h2>
                <p>Students On Board</p>
            <?php endif; ?>
        </div>
    </div>

</div>

</body>
</html>
<?php 
if (isset($con)) {
    mysqli_close($con);
}
?>