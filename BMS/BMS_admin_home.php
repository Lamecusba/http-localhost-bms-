<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: index.php');
    exit;
}

include 'conn.php';

// Get admin data
$admin_id = $_SESSION['user_id'];
$sql = "SELECT * FROM admin WHERE admin_id = '$admin_id'";
$result = mysqli_query($con, $sql);
$admin = mysqli_fetch_assoc($result);

// Calculate experience
$join_date = new DateTime($admin['join_date']);
$now = new DateTime();
$interval = $join_date->diff($now);
$experience = $interval->y . ' years & ' . $interval->m . ' months';

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Your existing POST handling
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Clean admin styles - only profile and buttons */
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
                
        /* Simple profile card */
        .profile-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            display: flex;
            gap: 25px;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin-bottom: 30px;
        }
        
        @media (max-width: 768px) {
            .profile-card {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }
        }
        
        /* Action buttons grid - exactly like original */
        .actions {
            margin-top: 30px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .actions {
                grid-template-columns: 1fr;
            }
        }
        
        /* Action button style - match original */
        .action-btn {
            background: white;
            border-radius: 14px;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            border: none;
            min-height: 80px;
        }
        
        .action-btn img {
            width: 32px;
            height: 32px;
        }
        
        .full {
            grid-column: span 2;
        }
        
        @media (max-width: 768px) {
            .full {
                grid-column: span 1;
            }
            
            .action-btn {
                padding: 15px;
                font-size: 16px;
                min-height: 70px;
            }
        }
        
        /* Logout button */
        .logout {
            margin-top: 25px;
        }
        
            
        .container {
            padding-bottom: 70px;
        }
    }
    </style>
</head>
<body>
    <?php if ($message): ?>
        <div class="notification <?php echo strpos($message, 'Error') !== false ? 'error' : ''; ?>" id="notification">
            <?php echo strpos($message, 'Error') !== false ? '❌' : '✅'; ?>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
        <script>
            setTimeout(() => {
                const notification = document.getElementById('notification');
                if (notification) {
                    notification.classList.add('show');
                    setTimeout(() => notification.classList.remove('show'), 3000);
                }
            }, 100);
        </script>
    <?php endif; ?>
    <?php include 'BMS_header.php'; ?>

    <div class="container">
        <!-- Profile Card -->
        <div class="profile-card">
            <div class="avatar">
                <?php
                $avatar_path = isset($admin['avatar_path']) ? $admin['avatar_path'] : '';
                if ($avatar_path && file_exists($avatar_path)) {
                    echo '<img src="' . htmlspecialchars($avatar_path) . '" alt="Admin Avatar" style="width: 120px;">';
                } else {
                    echo '<img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Default Avatar" style="width: 120px;">';
                }
                ?>
                <div class="avatar-upload" onclick="document.getElementById('avatarInput').click()">
                    Change Photo
                </div>
                <input type="file" id="avatarInput" style="display: none;" accept="image/*" onchange="uploadAvatar(this)">
            </div>

            <div class="profile-info">
                <h2><?php echo htmlspecialchars($admin['admin_name']); ?></h2>
                <p><strong>Admin ID:</strong> <?php echo htmlspecialchars($admin_id); ?></p>
                <p>Email: <?php echo htmlspecialchars($admin['email']); ?></p>
                <p>Phone Number: <?php echo htmlspecialchars($admin['phone_num']); ?></p>
                <p>Experience: <?php echo htmlspecialchars($experience); ?></p>
                <p>Join Date: <?php echo date('F j, Y', strtotime($admin['join_date'])); ?></p>
            </div>
        </div>

        <!-- Action Buttons - Exactly like original design -->
        <div class="actions">
            <a class="action-btn" href="BMS_admin_route.php" style="text-decoration: none;">
                <span>Rerouting</span>
                <img src="https://cdn-icons-png.flaticon.com/512/684/684908.png">
            </a>
            <a class="action-btn" href="BMS_admin_schedule.php" style="text-decoration: none;">
                <span>Manage Bus Schedule</span>
                <img src="https://cdn-icons-png.flaticon.com/512/747/747310.png">
            </a>
            <a class="action-btn" href="BMS_admin_analytic.php" style="text-decoration: none;">
                <span>Analytic Dashboard</span>
                <img src="https://cdn-icons-png.flaticon.com/512/1828/1828919.png">
            </a>
            <a class="action-btn" href="BMS_admin_busmanagement.php" style="text-decoration: none;">
                <span>Bus & Driver Management</span>
                <img src="https://cdn-icons-png.flaticon.com/512/1077/1077114.png">
            </a>
        </div>

        <!-- Logout Button -->
        <div class="logout">
            <a href="logout.php" style="text-decoration: none;">
                <div class="action-btn full">
                    Log Out <img src="https://cdn-icons-png.flaticon.com/512/1828/1828479.png">
                </div>
            </a>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($con); ?>