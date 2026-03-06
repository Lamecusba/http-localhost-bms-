<?php
session_start();
include 'BMS_header.php';
include "conn.php";

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$existingRouteCount = 0;
$fromStation = '';
$toStation = '';

// Get route details if editing
$isEdit = false;
$routeName = '';
$selectedRoads = [];
$routeId = null;

if (isset($_GET['route_id'])) {
    $isEdit = true;
    $routeId = intval($_GET['route_id']);

    /* Fetch route info */
    $routeSql = "SELECT * FROM route WHERE route_id = $routeId";
    $routeRes = mysqli_query($con, $routeSql);
    $route = mysqli_fetch_assoc($routeRes);

    if ($route) {
        $routeName = 'Route ' . $route['route_name'];
        $fromStation = $route['initial_station'];
        $toStation = $route['terminal_station'];
        
        // Check existing routes for these stations
        $stmt = $con->prepare("
            SELECT COUNT(DISTINCT route_id) AS total 
            FROM route 
            WHERE initial_station = ? AND terminal_station = ?
        ");
        $stmt->bind_param("ss", $fromStation, $toStation);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $existingRouteCount = $result['total'];
    }

    /* Fetch selected roads in order */
    $roadSql = "SELECT road_id FROM `route-road` WHERE route_id = $routeId ORDER BY road_order";
    $roadRes = mysqli_query($con, $roadSql);

    while ($r = mysqli_fetch_assoc($roadRes)) {
        $selectedRoads[] = $r['road_id'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $isEdit ? 'Edit Route' : 'Create New Route' ?></title>

  <style>
    *{box-sizing:border-box;font-family:Arial,Helvetica,sans-serif}

    body{
      margin:0;
      background:#08b7f0;
    }

    /* ===== Sticky Header ===== */
    header{
      background:#fff;
      padding:12px 24px;
      display:flex;
      justify-content:space-between;
      align-items:center;
      font-weight:bold;
      position:sticky;
      top:0;
      z-index:1000;
    }

    /* ===== Layout ===== */
    .container{
      display:flex;
      gap:30px;
      padding:30px;
    }

    .main{
      flex:3;
      background:#fff;
      border-radius:20px;
      padding:30px;
      box-shadow:0 10px 30px rgba(0,0,0,.2);
    }

    .back-btn{
        font-weight:bold;
        text-decoration:none;
        color:#333;
        margin-bottom:10px;
        display:inline-block;
        font-size:14px;
    }


    h2{
      margin-top:0;
    }

    /* ===== Form ===== */
    p, label{
      font-weight:bold;
      margin-right:10px;
    }

    select{
      padding:6px 10px;
      border-radius:6px;
    }
    
    select:disabled {
      background-color: #f5f5f5;
      color: #666;
      cursor: not-allowed;
    }

    .form-row{
      display:flex;
      gap:30px;
      margin-bottom:20px;
      align-items:center;
    }

    /* ===== Triangle Diagram ===== */
    .map{
    display:flex;
    border:2px dashed #ccc;
    border-radius:16px;
    padding:20px;
    margin-top:10px;
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
      margin: 2px;
      font-size:14px;
    }
    .diagram{
    flex:1;
    display:flex;
    justify-content:center;
    align-items:center;
    }

    .triangle-container {
      position: relative;
      width: 300px;
      height: 260px;
    }

    /* Equilateral triangle points */
    .destination{
      position: absolute;
      font-weight:bold;
      background: white;
      padding: 5px 10px;
      border-radius: 5px;
      z-index: 2;
    }

    .top-destination {
      top: 0;
      left: 50%;
      transform: translateX(-50%);
    }

    .left-destination {
      bottom: 0;
      left: 0;
      transform: translateX(-50%);
    }

    .right-destination {
      bottom: 0;
      right: 0;
      transform: translateX(50%);
    }

    .road{
      width: 100px;
      height: 5px;
      margin: 0;
      border-radius: 3px;
      border: none;
      background: #d9d9d9;
      font-weight:bold;
      cursor: not-allowed;
      position: absolute;
      transform-origin: 0 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      color: #333;
    }

    .road.available {
    background: #00c853;
    cursor: pointer;
    }

    .road.selected {
      background: #00c8ff;
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

    .save-btn{
      margin-top:20px;
      padding:10px 20px;
      border:none;
      border-radius:10px;
      background:#d9d9d9;
      font-weight:bold;
      cursor: not-allowed;
      float:right;
    }

    .save-btn.active {
      background: #00c8ff;
      cursor: pointer;
    }

    .reset-btn{
      margin-top:20px;
      margin-right:20px;
      padding:10px 20px;
      border:none;
      border-radius:10px;
      background:#d9d9d9;
      font-weight:bold;
      cursor:pointer;
      float:right;
    }

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


    /* Map becomes vertical */
    .map{
        flex-direction:column;
        gap:20px;
    }

    /* Center and scale triangle */
    .diagram{
        justify-content:center;
    }

    .triangle-container{
        transform:scale(0.85);
    }
}
    
  </style>
</head>

<body>
    <div class="container">
        
      <!-- ===== MAIN CONTENT ===== -->
      <div class="main">
        <a href="BMS_admin_route.php" class="back-btn">← Return to Route Options</a>
        <?php if ($isEdit): ?>
            <h2><?= htmlspecialchars($routeName) ?></h2>
        <?php else: ?>
            <h2>Create New Route</h2>
        <?php endif; ?>
        <br>
        <div class="form-row">
          <div>
              <label>From :</label>
              <select id="fromStation" <?= $isEdit ? 'disabled' : '' ?>>
                <option value="">Please Select</option>
                <option value="APU" <?= $fromStation == 'APU' ? 'selected' : '' ?>>APU</option>
                <option value="LRT" <?= $fromStation == 'LRT' ? 'selected' : '' ?>>LRT</option>
                <option value="Fortune" <?= $fromStation == 'Fortune' ? 'selected' : '' ?>>Fortune</option>
              </select>
          </div>

          <div>
              <label>To :</label>
              <select id="toStation" <?= $isEdit ? 'disabled' : '' ?>>
                <option value="">Please Select</option>
                <option value="LRT" <?= $toStation == 'LRT' ? 'selected' : '' ?>>LRT</option>
                <option value="APU" <?= $toStation == 'APU' ? 'selected' : '' ?>>APU</option>
                <option value="Fortune" <?= $toStation == 'Fortune' ? 'selected' : '' ?>>Fortune</option>
              </select>
          </div>
        </div>

        <!-- ===== PERFECT SYMMETRICAL TRIANGLE DIAGRAM ===== -->
        <div class="map">
          <div class="diagram">
            <div class="triangle-container">
                <!-- Destinations at triangle vertices -->
                <div class="destination top-destination">Fortune</div>
                <div class="destination left-destination">APU</div>
                <div class="destination right-destination">LRT</div>

                <button class="road a" data-road="A" data-id="1">A</button>
                <button class="road b" data-road="B" data-id="2">B</button>
                <button class="road c" data-road="C" data-id="3">C</button>
                <button class="road d" data-road="D" data-id="4">D</button>
                <button class="road e" data-road="E" data-id="5">E</button>
                <button class="road f" data-road="F" data-id="6">F</button>
                <button class="road g" data-road="G" data-id="7">G</button>
                <button class="road h" data-road="H" data-id="8">H</button>
                <button class="road i" data-road="I" data-id="9">I</button>
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
        <button class="save-btn">Save</button>
        <button class="reset-btn">Reset</button>
      </div>

      <?php include 'BMS_admin_sidebar.php'; ?>

    </div>
    
    <!-- Hidden field to store edit mode data -->
    <?php if ($isEdit): ?>
    <input type="hidden" id="editMode" value="1">
    <input type="hidden" id="routeId" value="<?= $routeId ?>">
    <input type="hidden" id="originalRouteName" value="<?= htmlspecialchars($route['route_name']) ?>">
    <input type="hidden" id="originalRouteId" value="<?= $routeId ?>">
    <?php endif; ?>
    
<script>

    
document.addEventListener('DOMContentLoaded', function() {
    // ===== GLOBAL VARIABLES =====
    let routeArray = []; // Stores road IDs in sequence
    let availableRoads = []; // Current available roads
    let fromStation = '';
    let toStation = '';
    let currentDirection = 'forward'; // Tracks direction of traversal
    
    // Check if we're in edit mode
    const isEditMode = document.getElementById('editMode') ? true : false;
    const routeId = isEditMode ? document.getElementById('routeId').value : null;
    const originalRouteName = isEditMode ? document.getElementById('originalRouteName').value : '';
    
    // Road connections data structure
    const roadConnections = {
        'A': {
            'forward': { connected: ['APU', 'C'], nextAvailable: ['D', 'E', 'B'] },
            'backward': { connected: ['D', 'E', 'B'], nextAvailable: ['C', 'APU'] }
        },
        'B': {
            'forward': { connected: ['A', 'D', 'E'], nextAvailable: ['F', 'LRT'] },
            'backward': { connected: ['F', 'LRT'], nextAvailable: ['A', 'D', 'E'] }
        },
        'C': {
            'forward': { connected: ['APU', 'A'], nextAvailable: ['G', 'H', 'D'] },
            'backward': { connected: ['G', 'H', 'D'], nextAvailable: ['A', 'APU'] }
        },
        'D': {
            'forward': { connected: ['A', 'E', 'B'], nextAvailable: ['C', 'G', 'H'] },
            'backward': { connected: ['C', 'G', 'H'], nextAvailable: ['A', 'E', 'B'] }
        },
        'E': {
            'forward': { connected: ['D', 'A', 'B'], nextAvailable: ['H', 'I', 'F'] },
            'backward': { connected: ['H', 'I', 'F'], nextAvailable: ['D', 'A', 'B'] }
        },
        'F': {
            'forward': { connected: ['LRT', 'B'], nextAvailable: ['I', 'H', 'E'] },
            'backward': { connected: ['I', 'H', 'E'], nextAvailable: ['LRT', 'B'] }
        },
        'G': {
            'forward': { connected: ['Fortune', 'I'], nextAvailable: ['C', 'D', 'H'] },
            'backward': { connected: ['C', 'D', 'H'], nextAvailable: ['Fortune', 'I'] }
        },
        'H': {
            'forward': { connected: ['G', 'C', 'D'], nextAvailable: ['I', 'E', 'F'] },
            'backward': { connected: ['I', 'E', 'F'], nextAvailable: ['G', 'C', 'D'] }
        },
        'I': {
            'forward': { connected: ['Fortune', 'G'], nextAvailable: ['H', 'E', 'F'] },
            'backward': { connected: ['H', 'E', 'F'], nextAvailable: ['Fortune', 'G'] }
        }
    };
    
    // Road to destination mapping (for initial state)
    const roadToDestinations = {
        'A': ['APU', 'C'],
        'B': ['A', 'D', 'E', 'F', 'LRT'],
        'C': ['APU', 'A', 'G', 'H', 'D'],
        'D': ['A', 'E', 'B', 'C', 'G', 'H'],
        'E': ['D', 'A', 'B', 'H', 'I', 'F'],
        'F': ['LRT', 'B', 'I', 'H', 'E'],
        'G': ['Fortune', 'I', 'C', 'D', 'H'],
        'H': ['G', 'C', 'D', 'I', 'E', 'F'],
        'I': ['Fortune', 'G', 'H', 'E', 'F']
    };
    
    // ===== DOM ELEMENTS =====
    const fromSelect = document.getElementById('fromStation');
    const toSelect = document.getElementById('toStation');
    const roadButtons = document.querySelectorAll('.road');
    const saveBtn = document.querySelector('.save-btn');
    const resetBtn = document.querySelector('.reset-btn');
    
    // ===== EVENT LISTENERS =====
    fromSelect.addEventListener('change', handleStationChange);
    toSelect.addEventListener('change', handleStationChange);
    
    resetBtn.addEventListener('click', resetRoute);
    saveBtn.addEventListener('click', saveRoute);
    
    // Add click listeners to all road buttons
    roadButtons.forEach(button => {
        button.addEventListener('click', function() {
            handleRoadClick(this);
        });
    });
    
    // ===== INITIALIZATION =====
    // If in edit mode, auto-select stations and roads
    if (isEditMode) {
        initializeEditMode();
    }
    
    // ===== FUNCTIONS =====
    
    function initializeEditMode() {
        // Get values from dropdowns (they're pre-selected by PHP)
        fromStation = fromSelect.value;
        toStation = toSelect.value;
        
        // Disable station dropdowns in edit mode
        fromSelect.disabled = true;
        toSelect.disabled = true;
        
        // Initialize route building with existing route
        <?php if ($isEdit && !empty($selectedRoads)): ?>
        // Convert PHP array to JS array
        const existingRoadIds = <?= json_encode($selectedRoads) ?>;
        
        // For each road ID, trigger the road selection
        existingRoadIds.forEach(roadId => {
            const button = document.querySelector(`.road[data-id="${roadId}"]`);
            if (button) {
                // Manually trigger road selection logic
                const roadLetter = button.getAttribute('data-road');
                
                // Add to route array
                routeArray.push(roadId);
                
                // Update available roads based on selection
                if (routeArray.length === 1) {
                    // First road - determine direction
                    const roadData = roadConnections[roadLetter];
                    
                    if (roadData.forward.connected.includes(fromStation)) {
                        currentDirection = 'forward';
                        availableRoads = roadData.forward.nextAvailable;
                    } else if (roadData.backward.connected.includes(fromStation)) {
                        currentDirection = 'backward';
                        availableRoads = roadData.backward.nextAvailable;
                    }
                } else {
                    // Subsequent roads
                    updateAvailableRoadsAfterSelection(roadLetter);
                }
            }
        });
        
        // Update UI to show selected roads
        updateRoadButtons();
        
        // Enable save button
        checkDestinationReached();
        <?php endif; ?>
    }
    
    function handleStationChange() {
        fromStation = fromSelect.value;
        toStation = toSelect.value;
        
        // Validate that APU is selected
        if (fromStation && toStation) {
            if (fromStation !== 'APU' && toStation !== 'APU') {
                alert('Error: One of the destinations must be APU');
                resetStations();
                return;
            }
            
            if (fromStation === toStation) {
                alert('Error: From and To stations cannot be the same');
                resetStations();
                return;
            }
            
            // Initialize route building
            resetRoute();
            updateInitialAvailableRoads();
        }
    }
    
    function resetStations() {
        // Only reset if not in edit mode
        if (!isEditMode) {
            fromSelect.value = '';
            toSelect.value = '';
            fromStation = '';
            toStation = '';
            resetRoadButtons();
        }
    }
    
    function resetRoadButtons() {
        roadButtons.forEach(button => {
            button.classList.remove('available', 'selected');
            button.disabled = true;
            button.style.cursor = 'not-allowed';
        });
    }
    
    function updateInitialAvailableRoads() {
        // Find roads that connect to the starting station
        availableRoads = [];
        
        Object.keys(roadToDestinations).forEach(road => {
            if (roadToDestinations[road].includes(fromStation)) {
                availableRoads.push(road);
            }
        });
        
        // Update UI
        updateRoadButtons();
    }
    
    function updateRoadButtons() {
        // Reset all buttons first
        roadButtons.forEach(button => {
            button.classList.remove('available', 'selected');
            button.disabled = true;
            button.style.cursor = 'not-allowed';
        });
        
        // Mark selected roads
        routeArray.forEach(roadId => {
            const button = document.querySelector(`.road[data-id="${roadId}"]`);
            if (button) {
                button.classList.add('selected');
                button.disabled = true;
            }
        });
        
        // Mark available roads
        availableRoads.forEach(roadLetter => {
            const button = document.querySelector(`.road[data-road="${roadLetter}"]`);
            if (button && !button.classList.contains('selected')) {
                button.classList.add('available');
                button.disabled = false;
                button.style.cursor = 'pointer';
            }
        });
        
        // Check if destination reached
        checkDestinationReached();
    }
    
    function handleRoadClick(button) {
        const roadLetter = button.getAttribute('data-road');
        const roadId = button.getAttribute('data-id');
        
        // Add to route array
        routeArray.push(roadId);
        
        // Determine next available roads based on current state
        updateAvailableRoadsAfterSelection(roadLetter);
        
        // Update UI
        updateRoadButtons();
    }
    
    function updateAvailableRoadsAfterSelection(selectedRoad) {
        if (routeArray.length === 1) {
            // First road selection - determine direction based on starting station
            const roadData = roadConnections[selectedRoad];
            
            // Check which connection matches our starting point
            if (roadData.forward.connected.includes(fromStation)) {
                currentDirection = 'forward';
                availableRoads = roadData.forward.nextAvailable;
            } else if (roadData.backward.connected.includes(fromStation)) {
                currentDirection = 'backward';
                availableRoads = roadData.backward.nextAvailable;
            }
        } else {
            // Subsequent selections - use the selected road's connections
            const roadData = roadConnections[selectedRoad];
            
            // Get the LAST selected road to check connections
            const lastRoadId = routeArray[routeArray.length - 2]; // Get second-to-last (previous road)
            const lastButton = document.querySelector(`.road[data-id="${lastRoadId}"]`);
            const lastRoadLetter = lastButton ? lastButton.getAttribute('data-road') : '';
            
            // Check connection between previous road and current road
            if (roadData.forward.connected.includes(lastRoadLetter)) {
                // Current road is connected via forward direction
                availableRoads = roadData.forward.nextAvailable;
            } else if (roadData.backward.connected.includes(lastRoadLetter)) {
                // Current road is connected via backward direction
                availableRoads = roadData.backward.nextAvailable;
            } else {
                // Fallback: use current direction
                availableRoads = roadData[currentDirection].nextAvailable;
            }
            
            // Filter out roads already in the route
            const selectedRoadLetters = routeArray.map(id => {
                const btn = document.querySelector(`.road[data-id="${id}"]`);
                return btn ? btn.getAttribute('data-road') : null;
            }).filter(Boolean);
            
            availableRoads = availableRoads.filter(road => !selectedRoadLetters.includes(road));
        }
    }
    
    function checkDestinationReached() {
        // Get the selected road letters
        const selectedRoadLetters = routeArray.map(id => {
            const btn = document.querySelector(`.road[data-id="${id}"]`);
            return btn ? btn.getAttribute('data-road') : null;
        }).filter(Boolean);

        // Get the last selected road
        const lastRoad = selectedRoadLetters.length > 0 ? selectedRoadLetters[selectedRoadLetters.length - 1] : null;

        // Roads that are near/connected to destination stations
        const destinationRoads = {
            'APU': ['A', 'C'], // Roads directly connected to APU
            'LRT': ['B', 'F'], // Roads directly connected to LRT
            'Fortune': ['G', 'I'] // Roads directly connected to Fortune
        };

        // Check if we have at least one road selected AND the last road connects to the destination
        const isDestinationReached = routeArray.length > 0 && 
            lastRoad && 
            destinationRoads[toStation] && 
            destinationRoads[toStation].includes(lastRoad);

        if (isDestinationReached) {
            saveBtn.classList.add('active');
            saveBtn.disabled = false;
            saveBtn.style.cursor = 'pointer';
        } else {
            saveBtn.classList.remove('active');
            saveBtn.disabled = true;
            saveBtn.style.cursor = 'not-allowed';
        }
    }
    
    function resetRoute() {
        routeArray = [];
        availableRoads = [];
        currentDirection = 'forward';
        
        // Reset UI
        updateRoadButtons();
        
        // Re-initialize if stations are selected
        if (fromStation && toStation) {
            updateInitialAvailableRoads();
        }
    }
    
    async function saveRoute() {
        if (!fromStation || !toStation) {
            alert('Please select both From and To stations');
            return;
        }
        
        if (routeArray.length === 0) {
            alert('Please select at least one road');
            return;
        }
        
        // Check if APU is included
        if (fromStation !== 'APU' && toStation !== 'APU') {
            alert('Error: One destination must be APU');
            return;
        }
        
        // Extract road letters (A, B, C, etc.) in the correct order
        const roadLetters = [];
        routeArray.forEach(id => {
            const btn = document.querySelector(`.road[data-id="${id}"]`);
            if (btn) {
                const roadLetter = btn.getAttribute('data-road');
                roadLetters.push(roadLetter);
            }
        });
        
        try {
            const formData = new FormData();
            formData.append('initial_station', fromStation);
            formData.append('terminal_station', toStation);
            
            // Add each road letter as a separate element in the array
            roadLetters.forEach((letter, index) => {
                formData.append(`route_array[${index}]`, letter);
            });
            
            // If in edit mode, include route_id to delete old route
            if (isEditMode && routeId) {
                formData.append('route_id', routeId);
            }
            
            const response = await fetch('save_route.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            console.log('Server response:', result);
            
            if (result.success) {
                if (isEditMode) {
                    alert(`Route updated successfully! Route name: ${result.route_name}`);
                } else {
                    alert(`Route saved successfully! Route name: ${result.route_name}`);
                }
                
                // Redirect back to route management page
                window.location.href = 'BMS_admin_route.php';
            } else {
                alert('Error saving route: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error saving route. Please try again.');
        }
    }
    
    // ===== INITIAL SETUP =====
    // Disable save button initially
    saveBtn.classList.remove('active');
    saveBtn.disabled = true;
    saveBtn.style.cursor = 'not-allowed';

    // Clear ALL visual states from road buttons
    document.querySelectorAll('.road').forEach(btn => {
        btn.classList.remove('available');
    });
    
    // Disable all road buttons initially
    if (!isEditMode) {
        resetRoadButtons();
    }
});
</script>
</body>
</html>