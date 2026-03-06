
<?php
session_start();

// Database configuration
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'bms';

// Create connection
$conn = mysqli_connect($host, $user, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userType = $_POST['userType'] ?? '';
    $userId = mysqli_real_escape_string($conn, $_POST['userId'] ?? '');
    $userPassword = $_POST['password'] ?? '';
    
    if (empty($userType) || empty($userId) || empty($userPassword)) {
        $error = 'All fields are required!';
    } else {
        // Determine which table to query based on user type
        $table = '';
        $idField = '';
        $nameField = '';
        
        switch($userType) {
            case 'admin':
                $table = 'admin';
                $idField = 'admin_id';
                $nameField = 'admin_name';
                break;
            case 'driver':
                $table = 'driver';
                $idField = 'driver_id';
                $nameField = 'driver_name';
                break;
            case 'student':
                $table = 'student';
                $idField = 'student_id';
                $nameField = 'student_name';
                break;
        }
        
        if ($table) {
            $sql = "SELECT * FROM $table WHERE $idField = '$userId'";
            $result = mysqli_query($conn, $sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);
                
                // In real application, use password_verify() with hashed passwords
                if ($userPassword === $user['password']) {
                    // Store user info in session
                    $_SESSION['user_type'] = $userType;
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_name'] = $user[$nameField];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_phone'] = $user['phone_num'];
                    
                    // Update driver status to 'active' if user is a driver
                    if ($userType == 'driver') {
                        $update_sql = "UPDATE driver SET status = 'active' WHERE driver_id = '$userId'";
                        mysqli_query($conn, $update_sql);
                    }
                    
                    // Redirect to appropriate dashboard
                    header("Location: BMS_{$userType}_home.php");
                    exit;
                } else {
                    $error = 'Invalid password!';
                }
            } else {
                $error = 'User not found!';
            }
        } else {
            $error = 'Invalid user type!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> BMS Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .logo {
            font-size: 2.5rem;
        }
        
        .logo-text {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            width: 100%;
            max-width: 400px;
            box-shadow: var(--box-shadow);
        }
        
        .login-title {
            text-align: center;
            margin-bottom: 30px;
            color: var(--dark-color);
            font-size: 1.8rem;
        }
                
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .form-select,
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-select:focus,
        .form-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(9, 181, 243, 0.1);
        }
        
        .login-btn {
            width: 100%;
            padding: 15px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
        }
        
        .login-btn:hover {
            background: var(--secondary-color);
        }
        
        .error-message {
            background: #ffebee;
            color: var(--danger-color);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid var(--danger-color);
        }
        
        .demo-info {
            margin-top: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .demo-info h4 {
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .demo-info p {
            margin: 8px 0;
            color: var(--gray-color);
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: var(--gray-color);
        }
        
        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 20px;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
            
            .logo-container {
                flex-direction: column;
                gap: 10px;
            }
            
            .logo-text {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-page">
        <?php include 'BMS_header.php'; ?>
        
        <div class="login-container">
            <div class="login-card">
                <h1 class="login-title">Welcome to BMS</h1>
                
                <?php if ($error): ?>
                    <div class="error-message">⚠️ <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="userType">Select User Type</label>
                        <select class="form-select" id="userType" name="userType" required>
                            <option value="">Choose your role</option>
                            <option value="admin">Admin</option>
                            <option value="driver">Driver</option>
                            <option value="student">Student</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="userId">User ID</label>
                        <input class="form-input" type="text" id="userId" name="userId" placeholder="Enter your ID" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-input" type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                    
                    <button type="submit" class="login-btn">Login</button>
                </form>
                
                <div class="demo-info">
                    <h4>Demo Accounts:</h4>
                    <p><strong>Admin:</strong> ID: AD001 | p/w: 123</p>
                    <p><strong>Driver:</strong> ID: DR001 | p/w: 123</p>
                    <p><strong>Student:</strong> ID: ST001 | p/w: 123</p>
                </div>
                
                <div class="register-link">
                    Don't have an account? <a href="register.php">Register here</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Add input focus styles
        document.querySelectorAll('.form-select, .form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
        
        // Auto-focus first input
        document.getElementById('userType').focus();
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
