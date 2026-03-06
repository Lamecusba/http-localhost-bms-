<?php
    session_start();

    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        header('Location: index.php');
        exit;
    }
    include "BMS_header.php";
    include "conn.php"; // your DB connection file

    $currentYear  = date('Y');
    $currentMonth = date('m');

    /* ===== MONTHLY DATA (Carbon Emission) ===== */
    $monthlyData = array_fill(1, 12, 0);

    $sqlMonth = "
    SELECT 
        MONTH(bs.date) AS month,
        COUNT(ss.student_id) AS total_students
    FROM `seat-student` ss
    JOIN bus_schedule bs 
        ON ss.schedule_id = bs.schedule_id
    WHERE ss.status = 'Attend'
    AND YEAR(bs.date) = YEAR(CURDATE())
    GROUP BY MONTH(bs.date)
    ORDER BY MONTH(bs.date);";

    $resultMonth = mysqli_query($con, $sqlMonth);
    while ($row = mysqli_fetch_assoc($resultMonth)) {
        $monthlyData[$row['month']] = $row['total_students'];
    }

    /* Carbon emission calculation */
    $carbonData = [];
    foreach ($monthlyData as $count) {
        $carbonData[] = $count * 0.01; // emission factor
    }

    /* ===== DAILY DATA (Students Fetched) ===== */
    $daysInMonth = date('t');
    $dailyData = array_fill(1, $daysInMonth, 0);

    $sqlDay = "
    SELECT 
        DAY(bs.date) AS day,
        COUNT(ss.student_id) AS total_students
    FROM `seat-student` ss
    JOIN bus_schedule bs 
        ON ss.schedule_id = bs.schedule_id
    WHERE ss.status = 'Attend'
    AND YEAR(bs.date) = YEAR(CURDATE())
    AND MONTH(bs.date) = MONTH(CURDATE())
    GROUP BY DAY(bs.date)
    ORDER BY DAY(bs.date);
    ";

    $resultDay = mysqli_query($con, $sqlDay);
    while ($row = mysqli_fetch_assoc($resultDay)) {
        $dailyData[$row['day']] = $row['total_students'];
    }
    date_default_timezone_set('Asia/Kuala_Lumpur');
    $today = date('j'); 
    $totalToday = $dailyData[$today];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytic Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>

        /* ===== Mobile Back Button ===== */
        .back-btn{
            font-weight:bold;
            text-decoration:none;
            color:#333;
            display:inline-block;
            font-size:14px;
        }


        .admin-layout{
            display: flex;
            padding: 24px;
            gap: 24px;
        }

        /* Main Content */
        .content{
            flex: 1;
            background: #fff;
            border-radius: 20px;
            padding: 32px;
        }

        
        body{
        background:#08b7f0;
        font-family:Arial, sans-serif;
        }

        .dashboard{
        display:flex;
        gap:24px;
        padding:24px;
        }

        html, body{
            width: 100%;
            max-width: 100vw;
        }


        .main{
        flex:3;
        }

        h2{margin-bottom:10px}

        .stat-block{
            display: flex;
            align-items: baseline;
            gap: 8px;
        }

        .stat-label{
            font-weight: normal;
        }

        .stat-value{
            font-size: 28px;
            font-weight: bold;
        }


        /* ===== Scrollable Charts ===== */
        .chart-scroll{
            width: 100%;
            max-width: 100%;
            height: 300px;
        }

        .chart-scroll canvas{
            width: 100%;
            max-width: 100%;
            height: 300px;
        }



    small{color:#666}

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


        @media screen and (max-width: 768px){


            /* Layout */
            .admin-layout{
                flex-direction: column;
                padding: 16px;
                align-items: center;
            }

            /* Main card */
            .content{
                width: 100%;
                padding: 20px;
                border-radius: 16px;
            }

            .main{
                width: 100%;
            }


            .stat-block{
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }

            .stat-label{
                font-size: 14px;
                color: #555;
            }

            .stat-value{
                font-size: 26px;
            }

            /* ===== Scrollable Charts ===== */
            .chart-scroll{
                width: 100%;
                overflow-x: auto;
                overflow-y: hidden;
            }

            .chart-wrapper{
                min-width: 700px;   /* enables horizontal swipe */
                height: 320px;      /* LOCK height */
                position: relative;
            }

            .chart-wrapper canvas{
                width: 100% !important;
                height: 100% !important;
            }


            h2{
                font-size: 20px;
            }
        }


    </style>
</head>

<body>

<div class="admin-layout">

    <!-- MAIN CONTENT -->
    <main class="content">
        <a href="BMS_admin_home.php" class="back-btn">← Return to Main Menu</a>
        <div class="dashboard">
            
            <div class="main">
                

                <h2>Analytic Dashboard</h2>

                <div class="stat-block">
                    <div class="stat-label">Total Carbon Emissions This Month:</div>
                    <div class="stat-value">
                        <?php echo array_sum($carbonData); ?> kgCO₂e
                    </div>
                </div>


                <div class="chart-scroll">
                    <div class="chart-wrapper">
                        <canvas id="carbonChart"></canvas>
                    </div>
                </div>


                <div class="stat-block" style="margin-top:40px;">
                    <div class="stat-label">Total Students Fetched Today:</div>
                    <div class="stat-value">
                        <?php echo $totalToday; ?> person(s)
                    </div>
                </div>


                <div class="chart-scroll">
                    <div class="chart-wrapper">
                        <canvas id="studentChart"></canvas>
                    </div>
                </div>

            </div>

        </div>
    </main>
    <?php include 'BMS_admin_sidebar.php'; ?>
</div>
<script>
/* ===== Carbon Chart ===== */
new Chart(document.getElementById('carbonChart'), {
  type: 'bar',
  data: {
    labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
    datasets: [{
      label: 'Carbon Emissions (kgCO₂e)',
      data: <?php echo json_encode($carbonData); ?>,
      backgroundColor: '#00c853'
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false
  }
});


/* ===== Student Chart ===== */
new Chart(document.getElementById('studentChart'), {
  type: 'bar',
  data: {
    labels: <?php echo json_encode(array_keys($dailyData)); ?>,
    datasets: [{
      label: 'Students Fetched',
      data: <?php echo json_encode(array_values($dailyData)); ?>,
      backgroundColor: '#ff9f43'
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false
  }
});

</script>

</body>
</html>
