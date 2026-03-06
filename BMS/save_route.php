<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include "conn.php";

header('Content-Type: application/json');

/* ===== Security check ===== */
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access"
    ]);
    exit;
}

/* ===== Input validation ===== */
if (
    !isset($_POST['initial_station']) ||
    !isset($_POST['terminal_station']) ||
    !isset($_POST['route_array']) ||
    !is_array($_POST['route_array'])
) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid input data"
    ]);
    exit;
}

$initial_station  = trim($_POST['initial_station']);
$terminal_station = trim($_POST['terminal_station']);
$routeArray       = $_POST['route_array'];

if (count($routeArray) === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Route cannot be empty"
    ]);
    exit;
}

/* =========================
   EDIT MODE: DELETE OLD ROUTE
   ========================= */
if (!empty($_POST['route_id'])) {
    $oldRouteId = intval($_POST['route_id']);
    
    // First, check if the route exists and get its stations
    $checkSql = "SELECT initial_station, terminal_station FROM route WHERE route_id = ?";
    $checkStmt = mysqli_prepare($con, $checkSql);
    mysqli_stmt_bind_param($checkStmt, "i", $oldRouteId);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $oldRoute = mysqli_fetch_assoc($checkResult);
    
    if ($oldRoute) {
        // Verify we're editing the same stations (should be locked in UI)
        if ($oldRoute['initial_station'] !== $initial_station || 
            $oldRoute['terminal_station'] !== $terminal_station) {
            echo json_encode([
                "success" => false,
                "message" => "Cannot change stations in edit mode"
            ]);
            exit;
        }
        
        // Delete old route-road mappings
        $deleteRoadSql = "DELETE FROM `route-road` WHERE route_id = ?";
        $deleteRoadStmt = mysqli_prepare($con, $deleteRoadSql);
        mysqli_stmt_bind_param($deleteRoadStmt, "i", $oldRouteId);
        mysqli_stmt_execute($deleteRoadStmt);
        
        // Delete the old route
        $deleteRouteSql = "DELETE FROM route WHERE route_id = ?";
        $deleteRouteStmt = mysqli_prepare($con, $deleteRouteSql);
        mysqli_stmt_bind_param($deleteRouteStmt, "i", $oldRouteId);
        mysqli_stmt_execute($deleteRouteStmt);
    }
}

/* ===== Start transaction ===== */
mysqli_begin_transaction($con);

try {

    /* ===== 1️⃣ Count existing routes with same destinations ===== */
    $countSql = "
        SELECT COUNT(*) AS total 
        FROM route
        WHERE initial_station = ?
          AND terminal_station = ?
    ";

    $stmt = mysqli_prepare($con, $countSql);
    mysqli_stmt_bind_param($stmt, "ss", $initial_station, $terminal_station);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    $routeCount = (int)$row['total'];

    /* ===== 2️⃣ Generate route_name alphabetically ===== */
    // 0 -> A, 1 -> B, 2 -> C ...
    $route_name = chr(65 + $routeCount);

    /* ===== 3️⃣ Determine status based on route_name ===== */
    // If this is the first route (A) between these stations, set it as Active
    // Otherwise, set as Inactive
    $status = ($route_name == 'A') ? 'Active' : 'Inactive';
    
    // If this is the first route (A), we need to ensure no other active routes
    // exist between these stations (for safety, though there shouldn't be any)
    if ($route_name == 'A') {
        // Deactivate any other routes between these stations (shouldn't exist, but just in case)
        $deactivateSql = "UPDATE route SET status = 'Inactive' 
                          WHERE initial_station = ? 
                          AND terminal_station = ?";
        $deactivateStmt = mysqli_prepare($con, $deactivateSql);
        mysqli_stmt_bind_param($deactivateStmt, "ss", $initial_station, $terminal_station);
        mysqli_stmt_execute($deactivateStmt);
    }

    /* ===== 4️⃣ Insert into route table ===== */
    $insertRouteSql = "
        INSERT INTO `route` (`status`, `route_name`, `initial_station`, `terminal_station`)
        VALUES (?, ?, ?, ?)
    ";

    $stmt = mysqli_prepare($con, $insertRouteSql);
    mysqli_stmt_bind_param($stmt, "ssss", $status, $route_name, $initial_station, $terminal_station);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) === 0) {
        throw new Exception("Failed to insert route");
    }

    /* ===== 5️⃣ Get newly created route_id ===== */
    $route_id = mysqli_insert_id($con);

    /* ===== 6️⃣ Insert route-road records ===== */
    $insertRoadSql = "
        INSERT INTO `route-road` (`road_id`, `road_order`, `route_id`)
        VALUES (?, ?, ?)
    ";

    $stmt = mysqli_prepare($con, $insertRoadSql);

    foreach ($routeArray as $index => $roadLetter) {

        // Convert A=1, B=2, C=3 ...
        $road_id   = ord(strtoupper($roadLetter)) - 64;
        $roadOrder = $index + 1;

        mysqli_stmt_bind_param(
            $stmt,
            "iii",
            $road_id,
            $roadOrder,
            $route_id
        );

        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_errno($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }

    }

    /* ===== Commit transaction ===== */
    mysqli_commit($con);

    echo json_encode([
        "success" => true,
        "message" => "Route saved successfully",
        "route_id" => $route_id,
        "route_name" => $route_name,
        "status" => $status
    ]);

} catch (Exception $e) {

    mysqli_rollback($con);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>