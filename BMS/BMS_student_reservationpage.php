<?php
session_start();
include("conn.php");
include("BMS_header.php");

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header('Location: index.php');
    exit;
}

$student_id = $_SESSION['user_id'];

/* =======================
   CHECK FOR EXISTING RESERVATION
   ======================= */
$existingReservationQuery = mysqli_query($con, 
    "SELECT * FROM `seat-student` 
     WHERE student_id = '$student_id' 
     AND (status = 'reserved')"
);

if (mysqli_num_rows($existingReservationQuery) > 0) {
    // Student already has a reservation, redirect to warning page
    header('Location: BMS_student_reservationwarning.php');
    exit;
}

/* =======================
   Handle Reservation
   ======================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $schedule_id = $_POST['schedule_id'] ?? '';
    $seat_id = $_POST['seat_id'] ?? '';
    
    if ($schedule_id && $seat_id) {
        // Check if seat is already taken
        $checkQuery = mysqli_query($con, 
            "SELECT * FROM `seat-student` 
             WHERE schedule_id = '$schedule_id' 
             AND seat_id = '$seat_id'"
        );
        
        if (mysqli_num_rows($checkQuery) === 0) {
            // Insert reservation
            $insertQuery = mysqli_query($con,
                "INSERT INTO `seat-student` (schedule_id, student_id, seat_id, status) 
                 VALUES ('$schedule_id', '$student_id', '$seat_id', 'reserved')"
            );
            
            if ($insertQuery) {
                echo "<script>alert('Reservation successful!');window.location.href='BMS_student_home.php';</script>";
            } else {
                echo "<script>alert('Reservation failed!');</script>";
            }
        } else {
            echo "<script>alert('Seat already taken! Please choose another.');</script>";
        }
    } else {
        echo "<script>alert('Please select all fields and a seat!');</script>";
    }
}

/* =======================
   Load schedules and get unique dates
   ======================= */
$scheduleData = [];
$uniqueDates = [];

// Get all future schedules
$scheduleQuery = mysqli_query($con, "SELECT * FROM bus_schedule WHERE date >= CURDATE() ORDER BY date");

while ($row = mysqli_fetch_assoc($scheduleQuery)) {
    $scheduleData[] = $row;
    
    // Store unique dates
    if (!in_array($row['date'], $uniqueDates)) {
        $uniqueDates[] = $row['date'];
    }
}

/* =======================
   Load seat reservations
   ======================= */
$seatData = [];
$seatQuery = mysqli_query($con, "SELECT schedule_id, seat_id FROM `seat-student`");

while ($row = mysqli_fetch_assoc($seatQuery)) {
    $seatData[] = $row;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Seat Reservation</title>

<style>
*{
    box-sizing:border-box;
    font-family:Arial, Helvetica, sans-serif;
}

body{
    margin:0;
    background:#07b6ef;
}

.back-btn{
    font-weight:bold;
    text-decoration:none;
    color:#333;
    margin-bottom:10px;
    display:inline-block;
    font-size:14px;
}

/* ===== Header ===== */
header{
    width: 100%;
    flex-shrink: 0;
}

/* ===== Card ===== */
.card{
    width:650px;
    background:#fff;
    margin:50px auto;
    padding:35px;
    border-radius:18px;
}

/* ===== Title ===== */
.card h2{
    margin-top:0;
    margin-bottom:25px;
}

/* ===== Form Rows ===== */
.form-row{
    margin-bottom:18px;
}

.form-row label{
    display:inline-block;
    width:90px;
    font-weight:bold;
}

.form-row input,
.form-row select{
    padding:6px 8px;
    width:180px;
}

/* ===== Seat Section ===== */
.seat-section{
    margin-top:25px;
}

.seat-grid{
    margin-left:95px;
    margin-right:95px;
    margin-top:10px;
    display:grid;
    grid-template-columns: repeat(11, 28px);
    gap:8px;
    max-width: 100%;
}

/* ===== Seat Buttons ===== */
.seat-btn{
    width:28px;
    height:28px;
    border:none;
    border-radius:4px;
    background:#ccc;
    cursor:pointer;
    padding:0;
}

/* Seat States */
.seat-btn.reserved{ background:#ff0000; cursor:not-allowed; }
.seat-btn.selected{ background:#32ff00; }

/* ===== Seat Info ===== */
.seat-info{
    margin-top:15px;
    font-size:15px;
}

/* ===== Legend ===== */
.legend{
    margin-top:18px;
    display:flex;
    gap:22px;
    font-size:14px;
}

.legend-item{
    display:flex;
    align-items:center;
    gap:6px;
}

.legend-dot{
    width:14px;
    height:14px;
    border-radius:50%;
}

.legend-reserved{ background:red; }
.legend-selected{ background:#32ff00; }
.legend-available{ background:#ccc; }

/* ===== Buttons ===== */
.button-row{
    margin-top:25px;
    text-align:right;
}

button{
    font-weight:bold;
}

.btn-confirm{
    padding:8px 20px;
    background:#32ff00;
    border:none;
    border-radius:8px;
    cursor:pointer;
}

.btn-reset{
    padding:8px 20px;
    background:#f0f0f0;
    border:none;
    border-radius:8px;
    margin-left:10px;
    cursor:pointer;
}

/* ===============================
   MOBILE RESPONSIVE (NO REDESIGN)
   =============================== */
@media screen and (max-width: 768px) {

    /* ===== Card ===== */
    .card{
        width: 95%;
        margin: 20px auto;
        padding: 20px;
        border-radius: 16px;
    }

    /* ===== Title ===== */
    .card h2{
        font-size: 22px;
        text-align: center;
    }

    /* ===== Form Rows ===== */
    .form-row{
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 14px;
    }

    .form-row label{
        width: auto;
        font-size: 14px;
    }

    .form-row input,
    .form-row select{
        width: 100%;
        padding: 10px;
        font-size: 15px;
    }

    /* Fix From / To inline layout */
    .form-row label[style]{
        width: auto !important;
    }

    /* ===== Seat Section ===== */
    .seat-section{
        margin-top: 20px;
    }

    /* Seat grid scaling */
    .seat-grid{
        justify-content: center;
        grid-template-columns: repeat(11, 22px);
        gap: 6px;
    }

    .seat-btn{
        width:20px;
        height:20px;
        aspect-ratio: 1 / 1;
    }

    /* ===== Seat Info ===== */
    .seat-info{
        margin-top: 10px;
        font-size: 14px;
        text-align: center;
    }

    /* ===== Legend ===== */
    .legend{
        flex-wrap: wrap;
        gap: 14px;
        font-size: 13px;
        justify-content: center;
    }

    /* ===== Buttons ===== */
    .button-row{
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 20px;
    }

    .btn-confirm,
    .btn-reset{
        width: 100%;
        padding: 12px;
        font-size: 15px;
    }
}


</style>
</head>

<body>


<!-- ===== Booking Card ===== -->
<div class="card">
<a href="BMS_student_home.php" class="back-btn">← Return to Main Menu</a>
<h2>Bus Booking</h2>

<form method="post" id="bookingForm">
    <!-- Hidden inputs for backend -->
    <input type="hidden" name="schedule_id" id="schedule_id">
    <input type="hidden" name="seat_id" id="seat_id">
    
    <!-- Date Dropdown -->
    <div class="form-row">
        <label>Date :</label>
        <select id="date" name="date" required>
            <option value="">Select Date</option>
            <?php
            // Generate dropdown options from unique dates
            foreach ($uniqueDates as $date) {
                $formattedDate = date("F j, Y", strtotime($date));
                echo "<option value='$date'>$formattedDate</option>";
            }
            
            // If no dates available, show a message
            if (empty($uniqueDates)) {
                echo "<option value='' disabled>No schedules available</option>";
            }
            ?>
        </select>
    </div>

    <!-- From / To -->
    <div class="form-row">
        <label>From :</label>
        <select id="from" disabled>
            <option value="">Pick Location</option>
        </select>

        <label style="width:40px;">To :</label>
        <select id="to" disabled>
            <option value="">Pick Location</option>
        </select>
    </div>

    <!-- Time -->
    <div class="form-row">
        <label>Time :</label>
        <select id="time" disabled>
            <option value="">Pick Time</option>
        </select>
    </div>

    <!-- Seat Selection -->
    <div class="seat-section">
        <strong>Seat Selection:</strong>

        <div class="seat-grid">
            <?php
            /* 44 individual seat buttons (since loop goes from 1 to 44 inclusive) */
            for($i = 1; $i <= 44; $i++){
                echo "<button type='button' class='seat-btn' data-seat='$i'></button>";
            }
            ?>
        </div>

        <div class="seat-info">
            Seat Selected: <span id="seatText">-</span></p>
        </div>

        <!-- Legend -->
        <div class="legend">
            <div class="legend-item">
                <div class="legend-dot legend-reserved"></div> Reserved
            </div>
            <div class="legend-item">
                <div class="legend-dot legend-selected"></div> Currently Picked
            </div>
            <div class="legend-item">
                <div class="legend-dot legend-available"></div> Available
            </div>
        </div>
    </div>

    <!-- Buttons -->
    <div class="button-row">
        <button type="submit" class="btn-confirm">Confirm</button>
        <button type="reset" class="btn-reset">Clear</button>
    </div>

</form>

</div>
<script>
/* ===== Data from PHP ===== */
const schedules = <?php echo json_encode($scheduleData); ?>;
const reservedSeats = <?php echo json_encode($seatData); ?>;
const dateSelect = document.getElementById("date");
const fromSelect = document.getElementById("from");
const toSelect = document.getElementById("to");
const timeSelect = document.getElementById("time");
const seatButtons = document.querySelectorAll(".seat-btn");
const seatText = document.getElementById("seatText");

let activeScheduleId = null;
let selectedSeat = null;

/* ===== SEAT SELECTION ===== */
seatButtons.forEach(btn => {
    btn.addEventListener("click", () => {
        // Check if a schedule is selected
        if (!activeScheduleId) {
            alert("Please select date, from, to, and time first!");
            return;
        }
        
        // Check if seat is already reserved
        if (btn.classList.contains("reserved")) {
            alert("This seat is already reserved!");
            return;
        }
        
        // Check if seat is already selected
        if (btn.classList.contains("selected")) {
            // Deselect seat
            btn.classList.remove("selected");
            selectedSeat = null;
            seatText.textContent = "-";
        } else {
            // Deselect any previously selected seat
            seatButtons.forEach(b => b.classList.remove("selected"));
            
            // Select new seat
            btn.classList.add("selected");
            selectedSeat = btn.getAttribute("data-seat");
            seatText.textContent = selectedSeat;
        }
    });
});

/* ===== DATE PICKED ===== */
dateSelect.addEventListener("change", () => {
    resetSelections();

    const pickedDate = dateSelect.value;

    // Filter schedules for the selected date
    const matched = schedules.filter(s => s.date === pickedDate);

    if (matched.length === 0) {
        alert("No bus scheduled on this date yet");
        return;
    }

    fromSelect.disabled = false;
    toSelect.disabled = false;

    populateLocations(matched);
});

function populateLocations(data) {
    fromSelect.innerHTML = '<option value="">Pick Location</option>';
    toSelect.innerHTML   = '<option value="">Pick Location</option>';

    const fromSet = new Set();
    const toSet   = new Set();

    data.forEach(s => {
        fromSet.add(s.initial_station);
        toSet.add(s.terminal_station);
    });

    fromSet.forEach(v => fromSelect.innerHTML += `<option>${v}</option>`);
    toSet.forEach(v => toSelect.innerHTML += `<option>${v}</option>`);
}

[fromSelect, toSelect].forEach(el => {
    el.addEventListener("change", () => {
        if (!fromSelect.value || !toSelect.value) return;

        timeSelect.disabled = false;
        timeSelect.innerHTML = '<option value="">Pick Time</option>';

        schedules
            .filter(s =>
                s.date === dateSelect.value &&
                s.initial_station === fromSelect.value &&
                s.terminal_station === toSelect.value
            )
            .forEach(s => {
                timeSelect.innerHTML +=
                    `<option value="${s.schedule_id}">${s.time}</option>`;
            });
    });
});

timeSelect.addEventListener("change", () => {
    activeScheduleId = timeSelect.value;
    selectedSeat = null;
    seatText.textContent = "-";
    
    // Reset all seats first
    seatButtons.forEach(btn => {
        btn.classList.remove("reserved", "selected");
        btn.disabled = false;
    });
    
    // Mark reserved seats
    reservedSeats
        .filter(r => r.schedule_id == activeScheduleId)
        .forEach(r => {
            const seatBtn = document.querySelector(
                `.seat-btn[data-seat='${r.seat_id}']`
            );
            if (seatBtn) {
                seatBtn.classList.add("reserved");
                seatBtn.disabled = true;
            }
        });
});

function resetSelections() {
    fromSelect.disabled = true;
    toSelect.disabled = true;
    timeSelect.disabled = true;
    
    fromSelect.innerHTML = '<option value="">Pick Location</option>';
    toSelect.innerHTML   = '<option value="">Pick Location</option>';
    timeSelect.innerHTML = '<option value="">Pick Time</option>';
    
    seatButtons.forEach(btn => {
        btn.disabled = false;
        btn.classList.remove("reserved", "selected");
    });
    
    activeScheduleId = null;
    selectedSeat = null;
    seatText.textContent = "-";
}

/* ===== FORM SUBMISSION ===== */
document.getElementById("bookingForm").addEventListener("submit", function(e) {
    e.preventDefault();
    
    // Validate all selections
    if (!dateSelect.value) {
        alert("Please select a date!");
        return;
    }
    
    if (!fromSelect.value || !toSelect.value) {
        alert("Please select from and to locations!");
        return;
    }
    
    if (!timeSelect.value) {
        alert("Please select a time!");
        return;
    }
    
    if (!selectedSeat) {
        alert("Please select a seat!");
        return;
    }
    
    // Set hidden inputs
    document.getElementById("schedule_id").value = activeScheduleId;
    document.getElementById("seat_id").value = selectedSeat;
    
    // Get selected date text for confirmation message
    const selectedDateText = dateSelect.options[dateSelect.selectedIndex].text;
    
    // Confirm with user
    if (confirm(`Confirm reservation:\nSeat: ${selectedSeat}\nDate: ${selectedDateText}\nFrom: ${fromSelect.value}\nTo: ${toSelect.value}\nTime: ${timeSelect.options[timeSelect.selectedIndex].text}`)) {
        this.submit();
    }
});

// Add event listener to reset button
document.querySelector(".btn-reset").addEventListener("click", function(e) {
    e.preventDefault();
    resetSelections();
    dateSelect.selectedIndex = 0; // Reset to "Select Date" option
    document.getElementById("bookingForm").reset();
});

// Add event listener for form reset
document.getElementById("bookingForm").addEventListener("reset", function() {
    setTimeout(() => {
        resetSelections();
        dateSelect.selectedIndex = 0;
    }, 0);
});
</script>
</body>
</html>