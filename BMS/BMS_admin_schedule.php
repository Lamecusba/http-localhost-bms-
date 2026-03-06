<?php
session_start();
include 'conn.php';

// Handle delete schedule
if (isset($_POST['delete_schedule']) && isset($_POST['schedule_id'])) {
    $scheduleId = intval($_POST['schedule_id']);
    
    // Check if schedule is in the past
    $checkSql = "SELECT date, time FROM bus_schedule WHERE schedule_id = $scheduleId";
    $checkResult = mysqli_query($con, $checkSql);
    $schedule = mysqli_fetch_assoc($checkResult);
    
    if ($schedule) {
        $scheduleDateTime = $schedule['date'] . ' ' . $schedule['time'];
        $currentDateTime = date('Y-m-d H:i:s');
        
        if (strtotime($scheduleDateTime) < strtotime($currentDateTime)) {
            $error = "Cannot delete a schedule that has already passed.";
        } else {
            // Start transaction
            mysqli_begin_transaction($con);
            
            try {
                // Delete the schedule
                $deleteSql = "DELETE FROM bus_schedule WHERE schedule_id = $scheduleId";
                mysqli_query($con, $deleteSql);
                
                // Commit transaction
                mysqli_commit($con);
                
                // Refresh the page
                header("Location: BMS_admin_schedule.php" . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
                exit();
                
            } catch (Exception $e) {
                mysqli_rollback($con);
                $error = "Error deleting schedule: " . $e->getMessage();
            }
        }
    }
}

// Handle filtering
$where = [];
$currentDate = date('Y-m-d');
$currentTime = date('H:i:s');

// Default filter: show only future schedules
$where[] = "(date > '$currentDate' OR (date = '$currentDate' AND time >= '$currentTime'))";

if (!empty($_GET['from'])) {
    $from = mysqli_real_escape_string($con, $_GET['from']);
    $where[] = "initial_station = '$from'";
}

if (!empty($_GET['to'])) {
    $to = mysqli_real_escape_string($con, $_GET['to']);
    $where[] = "terminal_station = '$to'";
}

if (!empty($_GET['date'])) {
    $date = mysqli_real_escape_string($con, $_GET['date']);
    $where[] = "date = '$date'";
}

if (!empty($_GET['bus'])) {
    $bus = mysqli_real_escape_string($con, $_GET['bus']);
    $where[] = "bus_id = '$bus'";
}

// Get available buses for filter dropdown
$busSql = "SELECT DISTINCT bus_id FROM bus_schedule ORDER BY bus_id";
$busResult = mysqli_query($con, $busSql);
$buses = [];
while ($busRow = mysqli_fetch_assoc($busResult)) {
    $buses[] = $busRow['bus_id'];
}

// Get unique dates for filter dropdown
$dateSql = "SELECT DISTINCT date FROM bus_schedule WHERE date >= CURDATE() ORDER BY date";
$dateResult = mysqli_query($con, $dateSql);
$dates = [];
while ($dateRow = mysqli_fetch_assoc($dateResult)) {
    $dates[] = $dateRow['date'];
}

// Get schedule data
$sql = "SELECT 
            bs.schedule_id,
            bs.date,
            bs.time,
            bs.bus_id,
            bs.initial_station,
            bs.terminal_station
        FROM bus_schedule bs";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY bs.date ASC, bs.time ASC, bs.initial_station";

$result = mysqli_query($con, $sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Schedule Management</title>

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

.create-btn{
    background:#39ff14;
    border:none;
    padding:10px 18px;
    border-radius:8px;
    font-weight:bold;
    cursor:pointer;
    width:100%;
    max-width:200px;
}

/* ===== Filter on Mobile ===== */
.mobile-filter {
    display:none;
    background:#fff;
    border-radius:16px;
    padding:20px;
    margin-bottom:20px;
}

.mobile-filter h3 {
    margin-top:0;
    margin-bottom:15px;
    font-size:18px;
}

.mobile-filter-form label {
    display:block;
    margin-bottom:8px;
    font-weight:bold;
    font-size:14px;
}

.mobile-filter-form select,
.mobile-filter-form input {
    width:100%;
    padding:10px 14px;
    border-radius:10px;
    border:1px solid #ccc;
    background-color:#fff;
    font-size:14px;
    margin-bottom:15px;
}

.mobile-filter-actions {
    display:flex;
    justify-content:space-between;
    margin-top:10px;
}

.mobile-filter-actions a {
    display:inline-block;
    padding:10px 18px;
    border-radius:10px;
    text-decoration:none;
    color:#000;
    font-weight:bold;
    background:#f0f0f0;
}

.mobile-filter-actions button {
    background:#39ff14;
    border:none;
    padding:10px 18px;
    border-radius:10px;
    font-weight:bold;
    cursor:pointer;
}

/* ===== Table Header ===== */
.table-header{
    display:grid;
    grid-template-columns:1.5fr 1fr 1fr 1fr 0.8fr 0.8fr;
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
    grid-template-columns:1.5fr 1fr 1fr 1fr 0.8fr 0.8fr;
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


/* ===== Action Buttons ===== */
.action-btn {
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

.action-btn a, .action-btn button {
    text-decoration:none;
    font-size:18px;
    color:#000;
    background:none;
    border:none;
    cursor:pointer;
    padding:5px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
}

.action-btn button.img-btn:disabled {
    cursor:not-allowed;
    opacity:0.5;
}

/* ===== Sidebar ===== */
.side-section{
    width:300px;
    display:flex;
    flex-direction:column;
    gap:20px;
}

.filter-box {
    width:100%;
    background:#fff;
    border-radius:16px;
    padding:20px;
    height:fit-content;
    display:flex;
    flex-direction:column;
    align-items:stretch;
}

.filter-box h3 {
    margin-top:0;
    margin-bottom:20px;
    font-size:18px;
}

/* ===== Filter Form ===== */
.filter-form label {
    display:block;
    margin-bottom:8px;
    font-weight:bold;
    font-size:14px;
}

.filter-form select,
.filter-form input {
    width:100%;
    padding:10px 14px;
    border-radius:10px;
    border:1px solid #ccc;
    background-color:#fff;
    font-size:14px;
    margin-bottom:15px;
    cursor:pointer;
}

.filter-actions {
    display:flex;
    justify-content:space-between;
    margin-top:10px;
}

.filter-actions a {
    display:inline-block;
    padding:10px 18px;
    border-radius:10px;
    text-decoration:none;
    color:#000;
    font-weight:bold;
    background:#f0f0f0;
}

.filter-actions button {
    background:#39ff14;
    border:none;
    padding:10px 18px;
    border-radius:10px;
    font-weight:bold;
    cursor:pointer;
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

.success-message {
    background:#ccffcc;
    color:#006600;
    padding:10px;
    border-radius:6px;
    margin-bottom:20px;
    text-align:center;
}

/* ===== Form styling ===== */
.delete-form {
    display:inline;
    margin:0;
    padding:0;
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
    
    .side-section {
        display:none;
    }
    
    .mobile-filter {
        display:block;
    }
    
    .main-header {
        flex-direction:row;
        align-items:center;
        justify-content:space-between;
    }
    
    .create-btn {
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
    
    .create-btn {
        width:100%;
        max-width:none;
    }
    
    .mobile-filter {
        padding:15px;
        border-radius:12px;
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
        display:flex;
        justify-content:space-between;
        align-items:center;
        min-height:auto;
        padding:5px 0;
        border-bottom:1px solid #eee;
        text-align:left;
        white-space:normal;
    }

    .schedule-row > div:first-child {
        justify-content: space-between !important; /* Force space-between for mobile */
        text-align: left;
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
        content:"Bus:";
    }
    
    .schedule-row > div:nth-child(5):before {
        content:"Edit:";
    }
    
    .schedule-row > div:nth-child(6):before {
        content:"Delete:";
    }
    
    .datetime {
        flex-direction:row;
        justify-content:space-between;
        width:100%;
    }
    
    .date-display, .time-display {
        font-size:14px;
    }
    
    .action-btn a img,
    .action-btn button img {
        width:20px;
        height:20px;
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
</style>

<script>
function confirmDeleteSchedule(scheduleId, scheduleInfo, isPast) {
    if (isPast) {
        alert('Cannot delete a schedule that has already passed.');
        return false;
    }
    
    if (confirm(`Are you sure you want to delete this schedule?\n\n${scheduleInfo}\n\nThis action cannot be undone.`)) {
        document.getElementById('delete-form-' + scheduleId).submit();
    }
    return false;
}
</script>

</head>

<body>

<?php include 'BMS_header.php'; ?>

<div class="container">
    
    <!-- ===== MOBILE FILTER (Hidden on Desktop) ===== -->
    <div class="mobile-filter">
        <h3>Filter Schedules</h3>
        <form method="GET" class="mobile-filter-form">
            <label>From :</label>
            <select name="from">
                <option value="">Pick Location</option>
                <option value="APU" <?= isset($_GET['from']) && $_GET['from'] == 'APU' ? 'selected' : '' ?>>APU</option>
                <option value="LRT" <?= isset($_GET['from']) && $_GET['from'] == 'LRT' ? 'selected' : '' ?>>LRT</option>
                <option value="Fortune" <?= isset($_GET['from']) && $_GET['from'] == 'Fortune' ? 'selected' : '' ?>>Fortune</option>
            </select>

            <label>To :</label>
            <select name="to">
                <option value="">Pick Location</option>
                <option value="APU" <?= isset($_GET['to']) && $_GET['to'] == 'APU' ? 'selected' : '' ?>>APU</option>
                <option value="LRT" <?= isset($_GET['to']) && $_GET['to'] == 'LRT' ? 'selected' : '' ?>>LRT</option>
                <option value="Fortune" <?= isset($_GET['to']) && $_GET['to'] == 'Fortune' ? 'selected' : '' ?>>Fortune</option>
            </select>

            <label>Date :</label>
            <select name="date">
                <option value="">Any Date</option>
                <?php foreach ($dates as $date): ?>
                    <option value="<?= $date ?>" <?= isset($_GET['date']) && $_GET['date'] == $date ? 'selected' : '' ?>>
                        <?= date('d M Y', strtotime($date)) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Bus :</label>
            <select name="bus">
                <option value="">Any Bus</option>
                <?php foreach ($buses as $bus): ?>
                    <option value="<?= $bus ?>" <?= isset($_GET['bus']) && $_GET['bus'] == $bus ? 'selected' : '' ?>>
                        <?= htmlspecialchars($bus) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="mobile-filter-actions">
                <a href="BMS_admin_schedule.php">Clear</a>
                <button type="submit">Apply</button>
            </div>
        </form>
    </div>
                    
    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-card">
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="success-message">Schedule deleted successfully!</div>
        <?php endif; ?>
            <a href="BMS_admin_home.php" class="back-btn">← Return to Main Menu</a>
        <div class="main-header">
            <h2>Schedule Management</h2>
            <button class="create-btn"
                onclick="window.location.href='BMS_admin_schedule_create.php'">
                + Create New Schedule
            </button>
        </div>

        <div class="table-header">
            <div>Route</div>
            <div>Time</div>
            <div>Date</div>
            <div>Bus</div>
            <div>Edit</div>
            <div>Delete</div>
        </div>

        <?php while($row = mysqli_fetch_assoc($result)): 
            $scheduleDateTime = $row['date'] . ' ' . $row['time'];
            $currentDateTime = date('Y-m-d H:i:s');
            $isUpcoming = strtotime($scheduleDateTime) > strtotime($currentDateTime);
            $isPast = strtotime($scheduleDateTime) < strtotime($currentDateTime);
            $rowClass = $isUpcoming ? 'upcoming' : '';
            $formattedDate = date('d M Y', strtotime($row['date']));
            $formattedTime = date('h:i A', strtotime($row['time']));
            $scheduleInfo = $row['initial_station'] . ' → ' . $row['terminal_station'] . 
                           ' | ' . $formattedDate . ' at ' . $formattedTime . 
                           ' | Bus: ' . $row['bus_id'];
        ?>
        <div class="schedule-row <?= $rowClass ?>">
            <div class="stations" data-label="Route">
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

            <div data-label="Bus">
                <span class="bus-id"><?= htmlspecialchars($row['bus_id']) ?></span>
            </div>

            <div class="action-btn" data-label="Edit">
                <?php if ($isUpcoming): ?>
                    <a href="BMS_admin_schedule_edit.php?schedule_id=<?= $row['schedule_id'] ?>" title="Edit Schedule">
                        <img src="edit.png" alt="Edit" style="width:25px;height:25px;">
                    </a>
                <?php else: ?>
                    <button class="img-btn" disabled title="Cannot edit past schedule">
                        <img src="edit.png" alt="Edit" style="width:25px;height:25px;opacity:0.5;">
                    </button>
                <?php endif; ?>
            </div>

            <div class="action-btn" data-label="Delete">
                <form method="POST" class="delete-form" id="delete-form-<?= $row['schedule_id'] ?>">
                    <input type="hidden" name="schedule_id" value="<?= $row['schedule_id'] ?>">
                    <input type="hidden" name="delete_schedule" value="1">
                    <button type="button" 
                            class="img-btn" 
                            title="<?= $isPast ? 'Cannot delete past schedule' : 'Delete Schedule' ?>"
                            onclick="confirmDeleteSchedule(
                                <?= $row['schedule_id'] ?>,
                                '<?= htmlspecialchars($scheduleInfo) ?>',
                                <?= $isPast ? 'true' : 'false' ?>
                            )"
                            <?= $isPast ? 'disabled' : '' ?>>
                        <img src="trash.png" alt="Delete" style="width:40px;height:25px;">
                    </button>
                </form>
            </div>
        </div>
        <?php endwhile; ?>

        <?php if (mysqli_num_rows($result) === 0): ?>
        <div class="no-schedules">
            No upcoming schedules found. Create your first schedule!
        </div>
        <?php endif; ?>

    </div>

    <!-- ===== DESKTOP SIDEBAR (Hidden on Mobile) ===== -->
    <div class="side-section">
        <?php include 'BMS_admin_sidebar.php'; ?>
        <div class="filter-box">
            <h3>Filter</h3>
            <form method="GET" class="filter-form">
                <label>From :</label>
                <select name="from">
                    <option value="">Pick Location</option>
                    <option value="APU" <?= isset($_GET['from']) && $_GET['from'] == 'APU' ? 'selected' : '' ?>>APU</option>
                    <option value="LRT" <?= isset($_GET['from']) && $_GET['from'] == 'LRT' ? 'selected' : '' ?>>LRT</option>
                    <option value="Fortune" <?= isset($_GET['from']) && $_GET['from'] == 'Fortune' ? 'selected' : '' ?>>Fortune</option>
                </select>

                <label>To :</label>
                <select name="to">
                    <option value="">Pick Location</option>
                    <option value="APU" <?= isset($_GET['to']) && $_GET['to'] == 'APU' ? 'selected' : '' ?>>APU</option>
                    <option value="LRT" <?= isset($_GET['to']) && $_GET['to'] == 'LRT' ? 'selected' : '' ?>>LRT</option>
                    <option value="Fortune" <?= isset($_GET['to']) && $_GET['to'] == 'Fortune' ? 'selected' : '' ?>>Fortune</option>
                </select>

                <label>Date :</label>
                <select name="date">
                    <option value="">Any Date</option>
                    <?php foreach ($dates as $date): ?>
                        <option value="<?= $date ?>" <?= isset($_GET['date']) && $_GET['date'] == $date ? 'selected' : '' ?>>
                            <?= date('d M Y', strtotime($date)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Bus :</label>
                <select name="bus">
                    <option value="">Any Bus</option>
                    <?php foreach ($buses as $bus): ?>
                        <option value="<?= $bus ?>" <?= isset($_GET['bus']) && $_GET['bus'] == $bus ? 'selected' : '' ?>>
                            <?= htmlspecialchars($bus) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="filter-actions">
                    <a href="BMS_admin_schedule.php">Clear</a>
                    <button type="submit">Apply</button>
                </div>
            </form>
        </div>
    </div>

</div>

</body>
</html>