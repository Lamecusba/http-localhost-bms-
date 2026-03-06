<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user type if exists
$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';
?>

<style>
*{
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: Arial, Helvetica, sans-serif;
}

body{
    background: #08b7f0;
}

/* Sticky Header */
header{
    position: sticky;
    top: 0;
    z-index: 1000;

    width: 100%;
    background: #fff;

    padding: 12px 24px;

    display: flex;
    justify-content: space-between;
    align-items: center;

    font-weight: bold;
    border-bottom: 1px solid #ddd;
}

/* Logo styling */
.logo-container {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom:0;
}

.logo-img {
    height: 32px; /* Adjust this value to control logo height */
    width: auto; /* Maintain aspect ratio */
    object-fit: contain;
}

.system-name {
    font-size: 1.2rem;
    color: black;
}

.no-role {
    font-size: 1.2rem;
    color: black;
}

/* Role text styling */
.role {
    font-size: 1.2rem;
    color: black;
}

/* Mobile styles */
@media (max-width: 768px) {
    .logo-container {
        flex: 1; /* Take available space */
    }
    
    .system-name {
        display: none; /* Hide "Bus Management System" on mobile */
    }
    
    .role-desktop {
        display: none; /* Hide the role on the right side on mobile */
    }
    
    .role-mobile {
        display: block; /* Show role next to logo on mobile */
        font-size: 1rem;
        margin-left: 8px;
        color: black;
    }
}

/* Desktop styles */
@media (min-width: 769px) {
    .role-mobile {
        display: none; /* Hide mobile role on desktop */
    }
    
    .role-desktop {
        display: block; /* Show role on the right side on desktop */
    }
}
</style>

<header>
    <div class="logo-container">
        <?php if(!empty($userType)): ?>
            <a href="BMS_<?php echo $userType; ?>_home.php" class="logo-link" style="display: flex; align-items: center; gap: 12px; text-decoration: none;">
                <img src="BMS.png" alt="BMS Logo" class="logo-img">
                <span class="system-name">Bus Management System</span>
                <?php if(!empty($userType)): ?>
                    <span class="role-mobile"><?php echo ucfirst($userType); ?> Dashboard</span>
                <?php endif; ?>
            </a>
        <?php else: ?>
            <div style="display: flex; align-items: center; gap: 12px;">
                <img src="BMS.png" alt="BMS Logo" class="logo-img">
                <span class="no-role">Bus Management System</span>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if(!empty($userType)): ?>
        <div class="role role-desktop"><?php echo ucfirst($userType); ?> Dashboard</div>
    <?php endif; ?>
</header>