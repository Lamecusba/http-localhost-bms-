<?php
// register.php
require_once 'config.php';


$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userType = $_POST['userType'] ?? '';
    $userId = sanitize($_POST['userId'] ?? '');
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    
    // Validate inputs
    if (empty($userType) || empty($userId) || empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = 'All fields are required!';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match!';
    } else {
        // Check if user already exists
        $table = '';
        $id_field = '';
        $name_field = '';
        
        switch ($userType) {
            case 'admin':
                $table = 'admin';
                $id_field = 'admin_id';
                $name_field = 'admin_name';
                break;
            case 'driver':
                $table = 'driver';
                $id_field = 'driver_id';
                $name_field = 'driver_name';
                break;
            case 'student':
                $table = 'student';
                $id_field = 'student_id';
                $name_field = 'student_name';
                break;
        }
        
        if ($table) {
            $check_sql = "SELECT * FROM $table WHERE $id_field = '$userId'";
            $check_result = mysqli_query($conn, $check_sql);
            
            if (mysqli_num_rows($check_result) > 0) {
                $error = 'User ID already exists!';
            } else {
                // Insert new user
                $join_date = date('Y-m-d');
                
                $sql = "INSERT INTO $table ($id_field, $name_field, password, join_date, email, phone_num";
                if ($userType == 'driver') {
                    $sql .= ", status) VALUES ('$userId', '$name', '$password', '$join_date', '$email', '$phone', 'active')";
                } else {
                    $sql .= ") VALUES ('$userId', '$name', '$password', '$join_date', '$email', '$phone')";
                }
                
                if (mysqli_query($conn, $sql)) {
                    $success = 'Registration successful! You can now login.';
                } else {
                    $error = 'Registration failed: ' . mysqli_error($conn);
                }
            }
        } else {
            $error = 'Invalid user type!';
        }
    }
}
?>
<!DOCTYPE html>
<html>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BMS Register</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .register-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 80px);
        }
        
        .register-box {
            background: white;
            border-radius: 20px;
            padding: 40px;
            width: 400px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin: 40px;
        }
        
        .register-title {
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            color: #333;
            font-weight: bold;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group select:focus,
        .form-group input:focus {
            border-color: #09b5f3;
            outline: none;
        }
        
        .register-btn {
            background: #09b5f3;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
            margin-top: 10px;
        }
        
        .register-btn:hover {
            background: #0899d4;
        }
        
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            display: <?php echo $error ? 'block' : 'none'; ?>;
        }
        
        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            display: <?php echo $success ? 'block' : 'none'; ?>;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .login-link a {
            color: #09b5f3;
            text-decoration: none;
            font-weight: bold;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include 'BMS_header.php'; ?>
    
    <div class="register-container">
        <div class="register-box">
            <div class="register-title">Register Account</div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="userType">Select User Type</label>
                    <select id="userType" name="userType" required>
                        <option value="">Choose your role</option>
                        <option value="admin">Admin</option>
                        <option value="driver">Driver</option>
                        <option value="student">Student</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="userId">User ID</label>
                    <input type="text" id="userId" name="userId" placeholder="Enter unique ID (e.g., AD123)" required>
                </div>
                
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm password" required>
                </div>
                
                <button type="submit" class="register-btn">Register</button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="index.php">Login here</a>
            </div>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>