<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date = mysqli_real_escape_string($con, $_POST['date']);
    $time = mysqli_real_escape_string($con, $_POST['time']);
    $bus_id = mysqli_real_escape_string($con, $_POST['bus_id']);
    $initial_station = mysqli_real_escape_string($con, $_POST['initial_station']);
    $terminal_station = mysqli_real_escape_string($con, $_POST['terminal_station']);
    
    // Validate date is not in the past
    $currentDateTime = date('Y-m-d H:i:s');
    $scheduleDateTime = $date . ' ' . $time;
    
    if (strtotime($scheduleDateTime) < strtotime($currentDateTime)) {
        $error = "Cannot create a schedule in the past.";
    } else {
        // Check if bus is already scheduled at that time
        $checkSql = "SELECT * FROM bus_schedule 
                     WHERE bus_id = '$bus_id' 
                     AND date = '$date' 
                     AND time = '$time'";
        $checkResult = mysqli_query($con, $checkSql);
        
        if (mysqli_num_rows($checkResult) > 0) {
            $error = "This bus is already scheduled at this date and time.";
        } else {
            $insertSql = "INSERT INTO bus_schedule (date, time, bus_id, initial_station, terminal_station)
                         VALUES ('$date', '$time', '$bus_id', '$initial_station', '$terminal_station')";
            
            if (mysqli_query($con, $insertSql)) {
                $success = "Schedule created successfully!";
                // Clear form
                $_POST = [];
            } else {
                $error = "Error creating schedule: " . mysqli_error($con);
            }
        }
    }
}

// Get available buses from the bus table (assuming you have one)
$busSql = "SELECT bus_id FROM bus ORDER BY bus_id";
$busResult = mysqli_query($con, $busSql);

// Get active routes for dropdown
$routeSql = "SELECT DISTINCT initial_station, terminal_station FROM route WHERE status = 'Active'";
$routeResult = mysqli_query($con, $routeSql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Schedule</title>
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
    padding:15px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    font-weight:bold;
}

/* ===== Layout ===== */
.container{
    display:flex;
    gap:30px;
    padding:30px;
}

/* ===== Main Card ===== */
.main-card{
    background:#fff;
    flex:1;
    border-radius:16px;
    padding:30px;
    box-shadow:0 10px 30px rgba(0,0,0,.2);
}

.back-btn{
    display:inline-block;
    margin-bottom:20px;
    font-weight:bold;
    text-decoration:none;
    color:#333;
}

h2{
    margin-top:0;
}

/* ===== Form Styles ===== */
.form-group{
    margin-bottom:20px;
}

label{
    display:block;
    margin-bottom:8px;
    font-weight:bold;
    font-size:14px;
}

input, select{
    width:100%;
    padding:12px;
    border-radius:8px;
    border:1px solid #ccc;
    font-size:16px;
}

.submit-btn{
    background:#39ff14;
    border:none;
    padding:12px 24px;
    border-radius:8px;
    font-weight:bold;
    cursor:pointer;
    font-size:16px;
    width:100%;
    margin-top:10px;
}

.error{
    background:#ffcccc;
    color:#cc0000;
    padding:10px;
    border-radius:6px;
    margin-bottom:20px;
    text-align:center;
}

.success{
    background:#ccffcc;
    color:#006600;
    padding:10px;
    border-radius:6px;
    margin-bottom:20px;
    text-align:center;
}

/* ===== Sidebar ===== */
.side-section{
    width:300px;
    display:flex;
    flex-direction:column;
    gap:20px;
}


@media screen and (max-width: 768px) {
    header {
        padding:12px 15px;
        font-size:14px;
    }
    
    .container {
        padding:12px;
        flex-direction:column;
        padding:15px;
        gap:20px;
    }
    
    .main-card {
        padding:20px;
        border-radius:12px;
    }
    
    .back-btn {
        font-size:14px;
    }
    
    h2 {
        font-size:20px;
    }
    
    input, select {
        padding:10px;
        font-size:14px;
    }
    
    .submit-btn {
        padding:10px 20px;
        font-size:14px;
    }
}
</style>
</head>
<body>

<?php include 'BMS_header.php'; ?>

<div class="container">
    
    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-card">
        <a href="BMS_admin_schedule.php" class="back-btn">← Return to Schedules</a>
        <h2>Create New Schedule</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Date:</label>
                <input type="date" name="date" value="<?= $_POST['date'] ?? '' ?>" min="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Time:</label>
                <input type="time" name="time" value="<?= $_POST['time'] ?? '' ?>" required>
            </div>
            
            <div class="form-group">
                <label>Bus ID:</label>
                <select name="bus_id" required>
                    <option value="">Select Bus</option>
                    <?php while($bus = mysqli_fetch_assoc($busResult)): ?>
                        <option value="<?= $bus['bus_id'] ?>" <?= ($_POST['bus_id'] ?? '') == $bus['bus_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($bus['bus_id']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>From Station:</label>
                <select name="initial_station" id="initial_station" required>
                    <option value="">Select Station</option>
                    <option value="APU" <?= ($_POST['initial_station'] ?? '') == 'APU' ? 'selected' : '' ?>>APU</option>
                    <option value="LRT" <?= ($_POST['initial_station'] ?? '') == 'LRT' ? 'selected' : '' ?>>LRT</option>
                    <option value="Fortune" <?= ($_POST['initial_station'] ?? '') == 'Fortune' ? 'selected' : '' ?>>Fortune</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>To Station:</label>
                <select name="terminal_station" id="terminal_station" required>
                    <option value="">Select Station</option>
                    <option value="APU" <?= ($_POST['terminal_station'] ?? '') == 'APU' ? 'selected' : '' ?>>APU</option>
                    <option value="LRT" <?= ($_POST['terminal_station'] ?? '') == 'LRT' ? 'selected' : '' ?>>LRT</option>
                    <option value="Fortune" <?= ($_POST['terminal_station'] ?? '') == 'Fortune' ? 'selected' : '' ?>>Fortune</option>
                </select>
            </div>
            
            <button type="submit" class="submit-btn">Create Schedule</button>
        </form>
    </div>

    <!-- ===== SIDEBAR ===== -->
    <div class="side-section">
        <?php include 'BMS_admin_sidebar.php'; ?>
    </div>

</div>

<script>
// Validate that From and To stations are different
document.querySelector('form').addEventListener('submit', function(e) {
    const fromStation = document.getElementById('initial_station').value;
    const toStation = document.getElementById('terminal_station').value;
    
    if (fromStation && toStation && fromStation === toStation) {
        e.preventDefault();
        alert('From and To stations cannot be the same.');
    }
});

// Set minimum time for today's date
const dateInput = document.querySelector('input[type="date"]');
const timeInput = document.querySelector('input[type="time"]');

dateInput.addEventListener('change', function() {
    const today = new Date().toISOString().split('T')[0];
    const selectedDate = this.value;
    
    
    if (selectedDate === today) {
        // If selecting today, set min time to current time
        const now = new Date();
        const currentTime = now.getHours().toString().padStart(2, '0') + ':' + 
                           now.getMinutes().toString().padStart(2, '0');
        timeInput.min = currentTime;
    } else {
        // For future dates, remove time restriction
        timeInput.min = '00:00';
    }
});
</script>
</body>
</html>