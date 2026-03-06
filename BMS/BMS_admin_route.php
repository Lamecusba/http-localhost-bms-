<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Handle status update
if (isset($_POST['toggle_status']) && isset($_POST['route_id'])) {
    $routeId = intval($_POST['route_id']);
    
    // Get the route details first
    $routeQuery = "SELECT initial_station, terminal_station FROM route WHERE route_id = $routeId";
    $routeResult = mysqli_query($con, $routeQuery);
    $routeData = mysqli_fetch_assoc($routeResult);
    
    if ($routeData) {
        $initialStation = $routeData['initial_station'];
        $terminalStation = $routeData['terminal_station'];
        
        // Start transaction
        mysqli_begin_transaction($con);
        
        try {
            // First, set all routes with the same stations to "Inactive"
            $deactivateSql = "UPDATE route SET status = 'Inactive' 
                              WHERE initial_station = '$initialStation' 
                              AND terminal_station = '$terminalStation'";
            mysqli_query($con, $deactivateSql);
            
            // Then, activate the selected route
            $activateSql = "UPDATE route SET status = 'Active' WHERE route_id = $routeId";
            mysqli_query($con, $activateSql);
            
            // Commit transaction
            mysqli_commit($con);
            
            // Refresh the page to show updated status
            header("Location: BMS_admin_route.php" . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($con);
            $error = "Error updating status: " . $e->getMessage();
        }
    }
}

// Handle delete route
if (isset($_POST['delete_route']) && isset($_POST['route_id'])) {
    $routeId = intval($_POST['route_id']);
    
    // Check if the route is active
    $checkActiveSql = "SELECT status FROM route WHERE route_id = $routeId";
    $checkResult = mysqli_query($con, $checkActiveSql);
    $routeStatus = mysqli_fetch_assoc($checkResult);
    
    if ($routeStatus && $routeStatus['status'] == 'Active') {
        $error = "Cannot delete an active route. Please deactivate it first.";
    } else {
        // Start transaction
        mysqli_begin_transaction($con);
        
        try {
            // Delete route-road mappings first
            $deleteRoadSql = "DELETE FROM `route-road` WHERE route_id = $routeId";
            mysqli_query($con, $deleteRoadSql);
            
            // Delete the route
            $deleteRouteSql = "DELETE FROM route WHERE route_id = $routeId";
            mysqli_query($con, $deleteRouteSql);
            
            // Commit transaction
            mysqli_commit($con);
            
            // Refresh the page
            header("Location: BMS_admin_route.php" . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($con);
            $error = "Error deleting route: " . $e->getMessage();
        }
    }
}

// Handle filtering
$where = [];

if (!empty($_GET['from'])) {
    $from = mysqli_real_escape_string($con, $_GET['from']);
    $where[] = "initial_station = '$from'";
}

if (!empty($_GET['to'])) {
    $to = mysqli_real_escape_string($con, $_GET['to']);
    $where[] = "terminal_station = '$to'";
}

// Updated SQL query to include total distance calculation
$sql = "SELECT 
            r.route_id,
            r.status,
            r.route_name,
            r.initial_station,
            r.terminal_station,
            COALESCE(SUM(rd.road_length), 0) as total_distance
        FROM route r
        LEFT JOIN `route-road` rr ON r.route_id = rr.route_id
        LEFT JOIN road rd ON rr.road_id = rd.road_id";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " GROUP BY r.route_id, r.status, r.route_name, r.initial_station, r.terminal_station";

// Order by status first (Active on top), then by station and route name
$sql .= " ORDER BY 
    CASE WHEN r.status = 'Active' THEN 1 ELSE 2 END,  -- Active routes first
    r.initial_station, 
    r.route_name";

$result = mysqli_query($con, $sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rerouting</title>

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

.mobile-filter-form select {
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

.back-btn{
    font-weight:bold;
    text-decoration:none;
    color:#333;
    margin-bottom:10px;
    display:inline-block;
    font-size:14px;
}

.table-header > div {
    padding:0 5px;
    min-width:0;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

/* ===== Route Row ===== */
.route-row{
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

.route-row > div {
    display:flex;
    align-items:center;
    justify-content:center;
    min-height:40px;
    min-width:0;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.route-row > div:first-child {
    justify-content:flex-start;
    text-align:left;
}

.route-row.active-route {
    border-color:#39ff14;
    border-width:2px;
    box-shadow:0 0 10px rgba(57, 255, 20, 0.2);
    background:linear-gradient(to right, #ffffff, #f9fff9);
}

/* ===== Status Button ===== */
.status-btn {
    display:inline-block;
    padding:8px 12px;
    border-radius:6px;
    font-size:13px;
    min-width:90px;
    text-align:center;
    cursor:pointer;
    border:none;
    font-weight:bold;
    transition:all 0.3s ease;
    text-decoration:none;
    color:#000;
}

.status-btn.active {
    background:#39ff14;
}

.status-btn.inactive {
    background:#d3d3d3;
}

/* ===== Distance Column ===== */
.distance {
    font-weight:bold;
    color:#333;
    padding:6px 10px;
    background:#f5f5f5;
    border-radius:6px;
    text-align:center;
    min-width:100px;
    display:inline-block;
    font-size:14px;
}

/* ===== Action Buttons ===== */
.action-btn {
    text-align:center;
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

.filter-form select {
    width:100%;
    padding:10px 14px;
    border-radius:10px;
    border:1px solid #ccc;
    background-color:#fff;
    font-size:14px;
    margin-bottom:20px;
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
.status-form, .delete-form {
    display:inline;
    margin:0;
    padding:0;
}

@keyframes pulse {
    0% { opacity:1; }
    50% { opacity:0.5; }
    100% { opacity:1; }
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
    
    .route-row {
        display:flex;
        flex-direction:column;
        align-items:stretch;
        gap:12px;
        padding:15px;
        border-radius:10px;
        margin-bottom:15px;
        position:relative;
    }
    
    .route-row > div {
        display:flex;
        justify-content:space-between;
        align-items:center;
        min-height:auto;
        padding:5px 0;
        border-bottom:1px solid #eee;
        text-align:left;
        white-space:normal;
    }
    
    .route-row > div:last-child {
        border-bottom:none;
    }

    .route-row > div:first-child {
        justify-content: space-between !important; /* Force space-between for mobile */
        text-align: left;
    }
    
    .route-row > div:before {
        content:attr(data-label);
        font-weight:bold;
        color:#666;
        min-width:80px;
        margin-right:10px;
        font-size:14px;
    }
    
    .route-row > div:first-child:before {
        content:"Route:";
    }
    
    .route-row > div:nth-child(2):before {
        content:"Name:";
    }
    
    .route-row > div:nth-child(3):before {
        content:"Status:";
    }
    
    .route-row > div:nth-child(4):before {
        content:"Distance:";
    }
    
    .route-row > div:nth-child(5):before {
        content:"Edit:";
    }
    
    .route-row > div:nth-child(6):before {
        content:"Delete:";
    }
    
    .stations {
        display:flex;
        align-items:center;
        gap:5px;
    }
    
    .status-btn {
        min-width:80px;
        padding:6px 10px;
        font-size:12px;
    }
    
    .distance {
        min-width:80px;
        padding:5px 8px;
        font-size:13px;
    }
    
    .action-btn a img,
    .action-btn button img {
        width:20px;
        height:20px;
    }
}

@media screen and (max-width: 480px) {
    .main-header h2 {
        font-size:18px;
    }
    
    .route-row > div:before {
        min-width:70px;
        font-size:13px;
    }
    
    .status-btn {
        min-width:70px;
        padding:5px 8px;
        font-size:11px;
    }
    
    .distance {
        min-width:70px;
        font-size:12px;
    }
}

/* ===== No routes message ===== */
.no-routes {
    text-align:center;
    padding:40px;
    color:#666;
    font-size:16px;
}

@media screen and (max-width: 768px) {
    .no-routes {
        padding:30px 20px;
        font-size:14px;
    }
}
</style>

<script>
function confirmStatusChange(routeId, currentStatus, fromStation, toStation) {
    const newStatus = currentStatus === 'Active' ? 'Inactive' : 'Active';
    
    if (newStatus === 'Active') {
        if (confirm(`Activate this route?\n\nThis will deactivate any other active route from ${fromStation} to ${toStation}.`)) {
            document.getElementById('toggle-form-' + routeId).submit();
        }
    } else {
        if (confirm(`Deactivate this route?\n\nNo route from ${fromStation} to ${toStation} will be active.`)) {
            document.getElementById('toggle-form-' + routeId).submit();
        }
    }
    return false;
}

function confirmDeleteRoute(routeId, routeName, isActive) {
    if (isActive) {
        alert('Cannot delete an active route. Please deactivate it first.');
        return false;
    }
    
    if (confirm(`Are you sure you want to delete ${routeName}?\n\nThis action cannot be undone.`)) {
        document.getElementById('delete-form-' + routeId).submit();
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
        <h3>Filter Routes</h3>
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

            <div class="mobile-filter-actions">
                <a href="BMS_admin_route.php">Clear</a>
                <button type="submit">Apply</button>
            </div>
        </form>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-card">
        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['status_updated'])): ?>
            <div class="success-message">Status updated successfully!</div>
        <?php endif; ?>
        <a href="BMS_admin_home.php" class="back-btn">← Return to Main Menu</a>
        <div class="main-header">
            <h2>Rerouting</h2>
            <button class="create-btn"
                onclick="window.location.href='BMS_admin_reroute.php'">
                + Create New Route
            </button>
        </div>

        <div class="table-header">
            <div>Route</div>
            <div>Route Name</div>
            <div>Status</div>
            <div>Distance (km)</div>
            <div>Edit</div>
            <div>Delete</div>
        </div>

        <?php while($row = mysqli_fetch_assoc($result)): 
            $isActive = $row['status'] == 'Active';
            $statusClass = $isActive ? 'active' : 'inactive';
            $rowClass = $isActive ? 'active-route' : '';
            $routeName = 'Route ' . htmlspecialchars($row['route_name']);
            $distance = number_format($row['total_distance'], 2);
        ?>
        <div class="route-row <?= $rowClass ?>">
            <div class="stations" data-label="Route">
                <?= htmlspecialchars($row['initial_station']) ?>
                →
                <?= htmlspecialchars($row['terminal_station']) ?>
            </div>

            <div class="route-name" data-label="Name"><?= $routeName ?></div>

            <div data-label="Status">
                <form method="POST" class="status-form" id="toggle-form-<?= $row['route_id'] ?>">
                    <input type="hidden" name="route_id" value="<?= $row['route_id'] ?>">
                    <input type="hidden" name="toggle_status" value="1">
                    <button type="button" 
                            class="status-btn <?= $statusClass ?>"
                            onclick="confirmStatusChange(
                                <?= $row['route_id'] ?>, 
                                '<?= $row['status'] ?>',
                                '<?= htmlspecialchars($row['initial_station']) ?>',
                                '<?= htmlspecialchars($row['terminal_station']) ?>'
                            )">
                        <?= htmlspecialchars($row['status']) ?>
                    </button>
                </form>
            </div>

            <div data-label="Distance">
                <span class="distance"><?= $distance ?> km</span>
            </div>

            <div class="action-btn" data-label="Edit">
                <a href="BMS_admin_reroute.php?route_id=<?= $row['route_id'] ?>" title="Edit Route">
                    <img src="edit.png" alt="Edit" style="width:25px;height:25px;">
                </a>
            </div>

            <div class="action-btn" data-label="Delete">
                <form method="POST" class="delete-form" id="delete-form-<?= $row['route_id'] ?>">
                    <input type="hidden" name="route_id" value="<?= $row['route_id'] ?>">
                    <input type="hidden" name="delete_route" value="1">
                    <button type="button" 
                            class="img-btn" 
                            title="<?= $isActive ? 'Cannot delete active route' : 'Delete Route' ?>"
                            onclick="confirmDeleteRoute(
                                <?= $row['route_id'] ?>,
                                '<?= $routeName ?>',
                                <?= $isActive ? 'true' : 'false' ?>
                            )"
                            <?= $isActive ? 'disabled' : '' ?>>
                        <img src="trash.png" alt="Delete" style="width:40px;height:25px;">
                    </button>
                </form>
            </div>
        </div>
        <?php endwhile; ?>

        <?php if (mysqli_num_rows($result) === 0): ?>
        <div class="no-routes">
            No routes found. Create your first route!
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

                <div class="filter-actions">
                    <a href="BMS_admin_route.php">Clear</a>
                    <button type="submit">Apply</button>
                </div>
            </form>
        </div>
    </div>

</div>

</body>
</html>