<?php
// Detect current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
.sidebar {
    width: 300px; /* Standard width for all sidebars */
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    height: fit-content;
    display: flex;
    flex-direction: column;
    align-items: stretch;
}

.sidebar ul {
    list-style: none;
    padding-left: 0;
    margin: 0;
}

.sidebar li {
    margin-bottom: 14px;
}

.sidebar a {
    display: block;
    padding: 10px 14px;
    border-radius: 10px;
    text-decoration: none;
    color: #000;
    font-weight: bold;
    transition: background-color 0.3s ease;
}

.sidebar a:hover,
.sidebar .active a {
    background: #9fc2ff;
}

@media screen and (max-width: 768px) {
    .sidebar {
        display:none;
    }
}
</style>

<aside class="sidebar">
    <ul>
        <li class="<?= $currentPage == 'BMS_admin_route.php' || $currentPage == 'BMS_admin_reroute.php' ? 'active' : '' ?>">
            <a href="BMS_admin_route.php">📍 Rerouting</a>
        </li>

        <li class="<?= $currentPage == 'BMS_admin_schedule.php' || $currentPage == 'BMS_admin_schedule_edit.php' || $currentPage == 'BMS_admin_schedule_create.php' ? 'active' : '' ?>">
            <a href="BMS_admin_schedule.php">📅 Manage Bus Schedule</a>
        </li>

        <li class="<?= $currentPage == 'BMS_admin_analytic.php' ? 'active' : '' ?>">
            <a href="BMS_admin_analytic.php">📊 Analytic Dashboard</a>
        </li>

        <li class="<?= $currentPage == 'BMS_admin_busmanagement.php' ? 'active' : '' ?>">
            <a href="BMS_admin_busmanagement.php">🚌 Bus & Driver Management</a>
        </li>

        <li>
            <a href="logout.php">🚪 Log Out</a>
        </li>
    </ul>
</aside>
