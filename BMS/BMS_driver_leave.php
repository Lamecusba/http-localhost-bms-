<?php 
session_start();
include 'BMS_header.php'; 

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'driver') {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Application</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #00b7f0;
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            display:flex;
            gap:30px;
            padding:30px;
        }
        
        .content {
            width: 100%;
        }
        
        .card {
            background:#fff;
            border-radius:20px;
            padding:30px;
            box-shadow:0 10px 30px rgba(0,0,0,.2);
        }
        
        h2 {
            color: black;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eaeaea;
            font-size: 28px;
        }
        
        .form-group {
            margin-bottom: 25px;
            display: flex;
            flex-direction: column;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
            font-size: 16px;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 14px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 16px;
            font-family: 'Segoe UI', Arial, sans-serif;
            transition: all 0.3s ease;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #00b7f0;
            box-shadow: 0 0 0 3px rgba(0, 183, 240, 0.2);
        }
        
        textarea {
            height: 150px;
            resize: vertical;
        }
        
        .btn-group {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: right;
        }
        
        .submit {
            background: #5cff00;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        .clear {
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .submit:hover {
            background: #52e600;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(92, 255, 0, 0.3);
        }
        
        .clear:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
                
        .info-box h3 {
            color: #0056b3;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .info-box p {
            color: #666;
            line-height: 1.5;
        }

        .back-btn{
            font-weight:bold;
            text-decoration:none;
            color:#333;
            margin-bottom:10px;
            display:inline-block;
            font-size:14px;
        }

        .status-box {
            margin-top: 40px;
            background:#fff;
            border-radius:20px;
            padding:30px;
            box-shadow:0 10px 30px rgba(0,0,0,.2);
        }

        .status-table {
            width: 100%;
            border-collapse: collapse;
        }

        .status-table th,
        .status-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eaeaea;
            font-size: 15px;
        }

        .status-table th {
            background: #f4f8ff;
            font-weight: bold;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            display: inline-block;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .side-section {
            width: 300px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        
        @media screen and (max-width: 768px){

            .status-box{
                padding: 20px;
            }

            .side-section {
                display: none;
            }

            .status-table{
                display: block;
                width: 100%;
                overflow-x: auto;
                white-space: nowrap; /* prevent column collapse */
            }

            /* Improve readability */
            .status-table th,
            .status-table td{
                font-size: 14px;
                padding: 10px;
            }

            /* Limit description width */
            .status-table td:nth-child(3){
                max-width: 200px;
                white-space: normal;   /* allow text wrap */
                word-break: break-word;
            }

            /* Status badge stays neat */
            .status-badge{
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <div class="card">
                <a href="BMS_driver_home.php" class="back-btn">← Return to Main Menu</a>
                <h2>Leave Application</h2>                    
                <form method="POST" action="#">
                    <div class="form-group">
                        <label for="leave_date">Leave Date</label>
                        <input type="date" id="leave_date" name="leave_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="leave_type">Leave Type</label>
                        <select id="leave_type" name="leave_type" required>
                            <option value="" disabled selected>Select leave type</option>
                            <option value="personal">Personal</option>
                            <option value="sick">Sick</option>
                            <option value="vacation">Vacation</option>
                            <option value="emergency">Emergency</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <!-- Description -->
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Please provide a brief explanation of your leave..." required></textarea>
                    </div>
                    
                    <!-- Action buttons -->
                    <div class="btn-group">
                        <button type="submit" class="submit" name="submitBtn">Submit</button>
                        <button type="reset" class="clear">Clear</button>
                    </div>
                </form>
                <?php
                // Handle form submission
                if (isset($_POST['submitBtn'])){
                    include('conn.php');
                    $sql = "INSERT INTO leave_apply (`apply_date`, `driver_id`, `leave_type`, `description`, `status`) VALUES (
                    '$_POST[leave_date]',
                    '$_SESSION[user_id]',
                    '$_POST[leave_type]',
                    '$_POST[description]',
                    'Pending'
                    )";


                    if(!mysqli_query($con,$sql)){
                        die('Error:'.mysqli_error($con));
                    } else {
                        echo "<script>alert('Submitted successfully');window.location.href='BMS_driver_leave.php';</script>";
                    }

                    mysqli_close($con);
                }
                ?>
                
            </div>
            <div class="status-box">
                <h2>My Leave Applications</h2>

                <?php
                include('conn.php');

                $driverId = $_SESSION['user_id'];
                $result = mysqli_query($con,
                    "SELECT apply_date, leave_type, description, status
                    FROM leave_apply
                    WHERE driver_id = '$driverId'
                    ORDER BY apply_id DESC"
                );

                if (mysqli_num_rows($result) > 0) {
                    echo "<table class='status-table'>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Status</th>
                            </tr>";

                    while ($row = mysqli_fetch_assoc($result)) {

                        $statusClass = match ($row['status']) {
                            'Approved' => 'status-approved',
                            'Rejected' => 'status-rejected',
                            default => 'status-pending'
                        };

                        echo "<tr>
                                <td>{$row['apply_date']}</td>
                                <td>".ucfirst($row['leave_type'])."</td>
                                <td>{$row['description']}</td>
                                <td><span class='status-badge {$statusClass}'>{$row['status']}</span></td>
                            </tr>";
                    }

                    echo "</table>";
                } else {
                    echo "<p>No leave applications submitted yet.</p>";
                }

                mysqli_close($con);
                ?>
            </div>
        </div>
        <div class="side-section">
            <?php include 'BMS_driver_sidebar.php'; ?>
        </div>
    </div>
    </body>
</html>