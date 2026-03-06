<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'driver') {
    header('Location: index.php');
    exit;
}

date_default_timezone_set('Asia/Kuala_Lumpur');
$driver_id = $_SESSION['user_id'];

// Get the bus assigned to this driver
$busSql = "SELECT bus_id FROM bus WHERE driver_id = '$driver_id'";
$busResult = mysqli_query($con, $busSql);
$busRow = mysqli_fetch_assoc($busResult);

if (!$busRow) {
    $error = "No bus assigned to you. Please contact administrator.";
    $bus_id = null;
} else {
    $bus_id = $busRow['bus_id'];
}

// Get current date and time for filtering
$currentDate = date('Y-m-d');
$currentTime = date('H:i:s');

// Get today's and upcoming schedules for this driver's bus
$where = ["bus_id = '$bus_id'"];
$where[] = "(date > '$currentDate' OR (date = '$currentDate' AND time >= '$currentTime'))";

// Build the query
$sql = "SELECT 
            bs.schedule_id,
            bs.date,
            bs.time,
            bs.bus_id,
            bs.initial_station,
            bs.terminal_station
        FROM bus_schedule bs
        WHERE bs.bus_id = '$bus_id'
        AND date >= '$currentDate'
        ORDER BY bs.date ASC, bs.time ASC";

$result = mysqli_query($con, $sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Schedule</title>

<style>
*{
    box-sizing:border-box;
    font-family:Arial, Helvetica, sans-serif;
}

body{
    margin:0;
    background:#08b7f0;
}

/* ===== Header ===== */
header{
    background:#fff;
    padding:15px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    font-weight:bold;
}

/* ===== Layout ===== */
.container{
    display:flex;
    gap:30px;
    padding:20px;
}

/* ===== Main Card ===== */
.main-card{
    background:#fff;
    flex:1;
    border-radius:16px;
    padding:20px;
    width:100%;
}

.main-header{
    display:flex;
    gap:15px;
    margin-bottom:20px;
    flex-direction:row;
    align-items:center;
    justify-content:space-between;
}

.main-header h2 {
    margin:0;
    font-size:24px;
}

.driver-bus-info {
    background:#39ff14;
    border:none;
    padding:10px 18px;
    border-radius:8px;
    font-weight:bold;
    text-align:center;
    width:100%;
    max-width:300px;
}

/* ===== Table Header ===== */
.table-header{
    display:grid;
    grid-template-columns:1.5fr 1fr 1fr 1fr;
    font-weight:bold;
    padding:12px 15px;
    border:1px solid #aaa;
    border-radius:10px;
    margin-bottom:15px;
    gap:15px;
    align-items:center;
    text-align:center;
    overflow-x:auto;
}

.table-header > div {
    padding:0 5px;
    min-width:0;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

/* ===== Schedule Row ===== */
.schedule-row{
    display:grid;
    grid-template-columns:1.5fr 1fr 1fr 1fr;
    align-items:center;
    padding:15px;
    border:1px solid #aaa;
    border-radius:10px;
    margin-bottom:15px;
    transition:all 0.3s ease;
    gap:15px;
    text-align:center;
    overflow-x:auto;
}

.schedule-row > div {
    display:flex;
    align-items:center;
    justify-content:center;
    min-height:40px;
    min-width:0;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.schedule-row > div:first-child {
    justify-content:flex-start;
    text-align:left;
}

/* ===== Status Indicator ===== */
.status-indicator {
    display:inline-flex;
    align-items:center;
    gap:5px;
    font-size:12px;
    padding:4px 8px;
    border-radius:12px;
    margin-left:10px;
}

.today {
    background:#fff3cd;
    color:#856404;
    border:1px solid #ffeaa7;
}

.today::before {
    content:"📅";
    font-size:12px;
}

.upcoming {
    background:#d4edda;
    color:#155724;
    border:1px solid #c3e6cb;
}

.upcoming::before {
    content:"🕒";
    font-size:12px;
}


.driver-details {
    background:#f0f8ff;
    padding:15px;
    border-radius:10px;
    margin-bottom:15px;
}

.driver-details p {
    margin:8px 0;
}

.driver-details strong {
    display:inline-block;
    width:80px;
}

/* ===== Messages ===== */
.error-message {
    background:#ffcccc;
    color:#cc0000;
    padding:10px;
    border-radius:6px;
    margin-bottom:20px;
    text-align:center;
}

.back-btn{
    font-weight:bold;
    text-decoration:none;
    color:#333;
    margin-bottom:10px;
    display:inline-block;
    font-size:14px;
}

.info-message {
    background:#e7f3ff;
    color:#0066cc;
    padding:10px;
    border-radius:6px;
    margin-bottom:20px;
    text-align:center;
}

/* ===== DateTime styling ===== */
.datetime {
    font-weight:bold;
    color:#333;
    display:flex;
    flex-direction:column;
    gap:5px;
}

.date-display {
    font-size:14px;
    color:#666;
}

.time-display {
    font-size:16px;
    color:#333;
    font-weight:bold;
}

/* ===== Bus ID styling ===== */
.bus-id {
    font-weight:bold;
    color:#333;
    padding:6px 12px;
    background:#f0f8ff;
    border-radius:6px;
    display:inline-block;
}

/* ===== Mobile Responsive ===== */
@media screen and (max-width: 1024px) {
    .container {
        flex-direction:column;
        padding:15px;
        gap:20px;
    }
    
    .sidebar {
        display:none !important;
    }
    
    .mobile-view {
        display:block;
    }
    
    .main-header {
        flex-direction:row;
        align-items:center;
        justify-content:space-between;
    }
    
    .driver-bus-info {
        width:auto;
    }
}

@media screen and (max-width: 768px) {
    header {
        padding:12px 15px;
        font-size:14px;
    }
    
    .container {
        padding:12px;
    }
    
    .main-card {
        padding:15px;
        border-radius:12px;
    }
    
    .main-header {
        flex-direction:column;
        align-items:flex-start;
        gap:10px;
    }
    
    .main-header h2 {
        font-size:20px;
    }
    
    .driver-bus-info {
        width:100%;
        max-width:none;
    }
    
    .table-header {
        display:none;
    }
    
    .schedule-row {
        display:flex;
        flex-direction:column;
        align-items:stretch;
        gap:12px;
        padding:15px;
        border-radius:10px;
        margin-bottom:15px;
        position:relative;
    }
    
    .schedule-row > div {
        display: flex;
        justify-content: space-between;
        align-items: center;
        min-height: auto;
        padding: 5px 0;
        border-bottom: 1px solid #eee;
        text-align: left;
        white-space: normal;
    }
    
    .schedule-row > div:last-child {
        border-bottom:none;
    }
    
    .schedule-row > div:before {
        content:attr(data-label);
        font-weight:bold;
        color:#666;
        min-width:80px;
        margin-right:10px;
        font-size:14px;
    }

    .schedule-row > div:first-child {
        justify-content: space-between !important; /* Force space-between for mobile */
        text-align: left;
    }
    
    .schedule-row > div:first-child:before {
        content:"Route:";
    }
    
    .schedule-row > div:nth-child(2):before {
        content:"Time:";
    }
    
    .schedule-row > div:nth-child(3):before {
        content:"Date:";
    }
    
    .schedule-row > div:nth-child(4):before {
        content:"Status:";
    }
    
    .datetime {
        flex-direction:row;
        justify-content:space-between;
    }
    
    .date-display, .time-display {
        font-size:14px;
    }
}

/* ===== No schedules message ===== */
.no-schedules {
    text-align:center;
    padding:40px;
    color:#666;
    font-size:16px;
}

@media screen and (max-width: 768px) {
    .no-schedules {
        padding:30px 20px;
        font-size:14px;
    }
}

/* ===== Next Schedule Highlight ===== */
.next-schedule {
    border:2px solid #39ff14;
    background-color:#f0fff0;
}

/* ===== Route Info ===== */
.route-info {
    font-weight:bold;
    font-size:16px;
    
    }
</style>

</head>

<body>

<?php include 'BMS_header.php'; ?>

<div class="container">
    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-card">
        <a href="BMS_driver_home.php" class="back-btn">← Return to Main Menu</a>
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!$bus_id): ?>
            <div class="info-message">
                You are not assigned to any bus. Please contact the administrator.
            </div>
        <?php endif; ?>
        <div class="main-header">
            <h2>My Schedule</h2>
        </div>
        
        <div class="table-header">
            <div>Route</div>
            <div>Time</div>
            <div>Date</div>
            <div>Status</div>
        </div>

        <?php 
        $hasNextSchedule = false;
        while($row = mysqli_fetch_assoc($result)): 
            $scheduleDate = $row['date'];
            $formattedDate = date('d M Y', strtotime($row['date']));
            $formattedTime = date('h:i A', strtotime($row['time']));
            
            // Determine status - only Today or Upcoming
            if ($scheduleDate == $currentDate) {
                $status = 'today';
                $statusText = 'Today';
                $rowClass = 'next-schedule'; // Highlight today's schedules
            } else {
                $status = 'upcoming';
                $statusText = 'Upcoming';
                $rowClass = '';
            }
        ?>
        <div class="schedule-row <?= $rowClass ?>" data-label="schedule-<?= $row['schedule_id'] ?>">
            <div class="route-info" data-label="Route">
                <?= htmlspecialchars($row['initial_station']) ?>
                →
                <?= htmlspecialchars($row['terminal_station']) ?>
            </div>

            <div class="datetime" data-label="Time">
                <span class="time-display"><?= $formattedTime ?></span>
            </div>

            <div class="datetime" data-label="Date">
                <span class="date-display"><?= $formattedDate ?></span>
            </div>

            <div data-label="Status">
                <span class="status-indicator <?= $status ?>">
                    <?= $statusText ?>
                </span>
            </div>
        </div>
        <?php endwhile; ?>

        <?php if (mysqli_num_rows($result) === 0 && $bus_id): ?>
        <div class="no-schedules">
            No upcoming schedules found for your bus.
        </div>
        <?php endif; ?>
    </div>
    <?php include 'BMS_driver_sidebar.php'; ?>

</div>      

</body>
</html>