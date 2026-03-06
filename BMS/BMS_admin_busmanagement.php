<?php
    session_start();

    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        header('Location: index.php');
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus & Driver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #34c3ff; 
            margin: 0;
            display: block;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .back-btn{
            font-weight:bold;
            text-decoration:none;
            color:#333;
            margin-bottom:10px;
            display:inline-block;
            font-size:14px;
        }

        .admin-layout{
            display: flex;
            padding: 24px;
            gap: 24px;
            align-items: flex-start;
        }       

        header{
            width: 100%;
            flex-shrink: 0;
        }

        .desktop-header {
            width: 100%;             
            background-color: white;
            padding: 15px 40px;
            display: flex; 
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            box-sizing: border-box;
            margin-bottom: 20px;   
        }

        .mobile-header {
            display: none;
        }

        /* for heading */
        h1 { 
            font-size: 1.1rem;
             margin: 0; color: #333; 
            }
        h2 { 
            font-size: 1.8rem; 
            margin: 0 0 25px 0; 
            color: #000; 
            font-weight: bold; 
        }
        h3 { 
            font-size: 1.2rem; 
            margin: 0; 
            color: #000;
         }

        .main-content {
            flex: 1;
            background: white;
            border-radius: 20px;
            padding: 32px;
            min-height: 500px;
            width: 100%;
        }

       
        .driver-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #fff;
        }

       
        .driver-info { order: 1; }
        .bus-details-box { order: 2; }
        .driver-avatar { order: 3; }

        .header-row { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            margin-bottom: 8px; 
        }

        .status-badge {
            padding: 3px 10px;
            border-radius: 5px;
            font-size: 11px;
            color: white;
            font-weight: bold;
        }

        /*driver status colour*/
        .absent { background: #ff0000; }
        .on-duty { background: #00e600; }
        .off-duty { background: #888; }

        .id-text { 
            color: #888; 
            font-size: 0.85rem;
            margin-bottom: 10px;
         }

        .app-pill {
            display: flex;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 20px;
            padding: 2px 10px;
            width: fit-content;
        }
        .app-pill select {
            border: none;
            outline: none;
            background: transparent;
            font-size: 0.8rem;
            padding: 5px;
        }

        .bus-details-box {
            border: 1px solid #999;
            border-radius: 10px;
            padding: 12px;
            width: 240px;
            margin: 0 20px;
        }

        .bus-header { 
            display: flex; 
            justify-content: space-between; 
            font-size: 0.85rem; 
            color: #555;
            margin-bottom: 8px;
        }

        .remove-btn {
            background: #ff0000;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 11px;
            font-weight: bold;
            width: 100%;
            margin-top: 5px;
        }

        .driver-avatar img {
            width: 85px;
            height: 85px;
            border-radius: 50%;
            object-fit: cover;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 400px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .modal-footer { 
            display: flex; justify-content:
            flex-end; gap: 10px;
             margin-top: 20px; 
            }
        .btn-approve {
             background: #43ff40ff; 
             color: white; 
             border: none; 
             padding: 10px 15px; 
             border-radius: 5px; 
             cursor: pointer; 
            }
        .btn-reject { 
            background: red; 
            color: white; 
            border: none; 
            padding: 10px 15px; 
            border-radius: 5px; 
            cursor: pointer; 
        }
        .btn-return { 
            background: none; 
            border: none; 
            cursor: pointer; 
            color: #555; 
            text-decoration: underline; 
        }

        .icon-btn { 
            background: none;
             border: none; 
             cursor: pointer; 
             color: #555; 
             ont-size: 1.1rem; 
             padding: 0 5px; }

    @media (max-width: 1024px) {
        
        .driver-card {
            display: flex ;
            flex-direction: row ;
            flex-wrap: wrap; 
            justify-content: space-between;
            padding: 15px ;
            text-align: left;
        }

        .driver-info {
            width: 65%;
            order: 1;
        }

        .main-content {
            width: 100%;
            padding: 20px;
            border-radius: 16px;
        }

        .driver-avatar {
            width: 30%;
            order: 2;
            display: flex;
            justify-content: flex-end;
        }

        .driver-avatar img {
            width: 65px;
            height: 65px;
        }

        
        .bus-details-box {
            width: 100% !important;
            margin: 15px 0 0 0 !important;
            order: 3;
        }
    }
    </style>


</head>
<body>
    
    <?php include 'BMS_header.php'; ?>

    <div class="admin-layout">
        <main class="main-content">
            <a href="BMS_admin_home.php" class="back-btn">← Return to Main Menu</a>
            <h2>Bus & Driver Management</h2>
            <?php
            include 'conn.php'; 
            $available_buses_query = "SELECT bus_id FROM bus WHERE driver_id IS NULL OR driver_id = ''";
            $available_buses_result = mysqli_query($con, $available_buses_query);


            $query = "SELECT d.driver_name, d.driver_id, d.status AS d_status, 
                            b.bus_id, b.status AS b_status,
                            l.leave_type, l.apply_date, l.description, l.apply_id
                    FROM driver d
                    LEFT JOIN bus b ON d.driver_id = b.driver_id
                    LEFT JOIN leave_apply l ON d.driver_id = l.driver_id AND l.status = 'Pending'";
            
            $result = mysqli_query($con, $query);

            while($row = mysqli_fetch_assoc($result)) {
                $d_name = $row['driver_name'];
                $d_id   = $row['driver_id'];
                $d_stat = $row['d_status'];
                $b_id   = $row['bus_id'] ? $row['bus_id'] : "None";
                $b_stat = $row['b_status'] ? $row['b_status'] : "N/A";
                
                $status_color_class = ($d_stat == "On Duty") ? "on-duty" : (($d_stat == "Off Duty") ? "absent" : "off-duty");
                $has_pending = !empty($row['leave_type']);
            ?>
                <div class="driver-card">
                    <div class="driver-info">
                        <div class="header-row">
                            <h3><?php echo $d_name; ?></h3> 
                            <span class="status-badge <?php echo $status_color_class; ?>"><?php echo $d_stat; ?></span>
                        </div>
                        <div class="id-text">Driver ID: <?php echo $d_id; ?></div>
                        
                        <div class="app-pill" style="position: relative;">
                            <select>
                                <option>Applications</option>
                                <?php if($has_pending): ?>
                                    <option selected>Leave Application</option>
                                <?php endif; ?>
                            </select>
                            <button class="icon-btn" onclick="showLeaveDetails('<?php echo $d_name; ?>', '<?php echo $d_id; ?>', '<?php echo $b_id; ?>', '<?php echo $row['leave_type'] ?? 'N/A'; ?>', '<?php echo $row['apply_date'] ?? 'N/A'; ?>', '<?php echo addslashes($row['description'] ?? ''); ?>', '<?php echo $row['apply_id'] ?? ''; ?>')">
                                <i class="fa-solid fa-file-signature"></i>
                                <?php if($has_pending): ?>
                                    <span style="height: 10px; width: 10px; background: red; border-radius: 50%; position: absolute; top: -5px; right: -5px; border: 2px solid white;"></span>
                                <?php endif; ?>
                            </button>
                        </div>
                    </div>

                    <div class="bus-details-box">
                        <?php if ($b_id !== "None"): ?>
                            <div class="bus-header">
                                <span>Bus: <?php echo $b_id; ?></span>
                                <i class="fa-solid fa-print"></i>
                            </div>
                            <p style="font-size: 0.85rem; margin: 8px 0;">
                                Condition: <span class="status-badge on-duty"><?php echo $b_stat; ?></span>
                            </p>
                            <form action="removebus.php" method="POST">
                                <input type="hidden" name="bus_id" value="<?php echo $b_id; ?>">
                                <button type="submit" class="remove-btn">Remove <?php echo $b_id; ?> from driver</button>
                            </form>
                        <?php else: ?>
                            <div style="text-align: center; padding: 20px 0;">
                                <p style="font-size: 0.85rem; color: #888;">No Bus Assigned</p>
                                <button class="btn-approve" style="width: 100%;" onclick="openAssignModal('<?php echo $d_id; ?>')">Assign Bus</button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="driver-avatar">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($d_name); ?>&background=random" alt="Avatar">
                    </div>
                </div>
            <?php } ?>
        </main>
        <?php include 'BMS_admin_sidebar.php'; ?>
    </div>

    <div id="leaveModal" class="modal-overlay">
        <div class="modal-content">
            <h3>Leave Application</h3>
            <hr>
            <div class="modal-body">
                <p><strong>Name:</strong> <span id="modalName"></span></p>
                <p><strong>Driver ID:</strong> <span id="modalID"></span></p>
                <p><strong>Bus:</strong> <span id="modalBus"></span></p>
                <p><strong>Type:</strong> <span id="modalType"></span></p>
                <p><strong>Date:</strong> <span id="modalDate"></span></p>
                <p><strong>Reason:</strong> <span id="modalReason"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal()" class="btn-return">Return</button>
                <form action="leave_process.php" method="POST" style="display: inline;">
                    <input type="hidden" name="apply_id" id="modalApplyID">
                    <button type="submit" name="action" value="Approved" class="btn-approve">Approve</button>
                    <button type="submit" name="action" value="Rejected" class="btn-reject">Reject</button>
                </form>
            </div>
        </div>
    </div>

    <div id="assignModal" class="modal-overlay"> 
        <div class="modal-content">
            <h3>Assign Bus to Driver</h3>
            <hr>
            <form action="assignbus.php" method="POST">
                <div class="modal-body">
                    <p><strong>Driver ID:</strong> 
                        <input type="text" name="driver_id" id="assignDriverID" readonly style="border:none; outline:none; font-weight:bold; background:transparent;">
                    </p>
                    <p><strong>Select Available Bus:</strong><br>
                        <select name="bus_id" id="busSelect" required style="width:100%; padding:10px; margin-top:5px; border: 1px solid #ccc; border-radius: 5px; cursor: pointer;">
                            <option value="">-- Search/Choose a Bus --</option>
                            <?php 
                            if(mysqli_num_rows($available_buses_result) > 0) {
                                while($bus = mysqli_fetch_assoc($available_buses_result)) {
                                    echo "<option value='".$bus['bus_id']."'>".$bus['bus_id']."</option>";
                                }
                            } else {
                                echo "<option value='' disabled>No unassigned buses available</option>";
                            }
                            ?>
                        </select>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeAssignModal()" class="btn-return">Cancel</button>
                    <button type="submit" class="btn-approve">Assign Now</button>
                </div>
            </form>
        </div>
    </div>

<script>
    function closeModal() { document.getElementById('leaveModal').style.display = 'none'; }
    function openAssignModal(driverId) {
        document.getElementById('assignDriverID').value = driverId;
        document.getElementById('assignModal').style.display = 'flex';
    }
    function closeAssignModal() { document.getElementById('assignModal').style.display = 'none'; }

    function showLeaveDetails(name, id, bus, type, date, reason, applyId) {
        document.getElementById('modalName').innerText = name;
        document.getElementById('modalID').innerText = id;
        document.getElementById('modalBus').innerText = bus;
        document.getElementById('modalType').innerText = type;
        document.getElementById('modalDate').innerText = date;
        document.getElementById('modalReason').innerText = reason;
        document.getElementById('modalApplyID').value = applyId; 
        document.getElementById('leaveModal').style.display = 'flex';
    }

    $(document).ready(function() {
    $('#busSelect').select2({// initialize the search box inside the dropdown
        dropdownParent: $('#assignModal'),   //make sure the search menu stay inside the modal
        placeholder: "Search for available bus ID...",
        allowClear: true
    });
});
</script>
</body>
</html>