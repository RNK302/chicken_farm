<?php
// index.php

include 'db.php'; // Include the database connection file

// Function to determine the month's season and corresponding color
function get_season($month) {
    switch ($month) {
        case 3:
        case 4:
        case 5:
            return ['icon' => '☀️', 'color' => '#ffc107', 'text-color' => '#000']; // Summer (Yellow/Black)
        case 6:
        case 7:
        case 8:
            return ['icon' => '🌧️', 'color' => '#17a2b8', 'text-color' => '#fff']; // Monsoon (Cyan/White)
        case 9:
        case 10:
        case 11:
            return ['icon' => '🍂', 'color' => '#fd7e14', 'text-color' => '#fff']; // Autumn (Orange/White)
        case 12:
        case 1:
        case 2:
            return ['icon' => '❄️', 'color' => '#6c757d', 'text-color' => '#fff']; // Winter (Gray/White)
        default:
            return ['icon' => '☀️', 'color' => '#ffc107', 'text-color' => '#000'];
    }
}

// Fetch all incomplete chicken batches, ordered by date (oldest first)
$incomplete_sql = "SELECT * FROM chicken_batches WHERE status = 'incomplete' ORDER BY year ASC, month ASC, created_at ASC";
$incomplete_result = mysqli_query($conn, $incomplete_sql);

// Fetch all complete chicken batches
$complete_sql = "SELECT * FROM chicken_batches WHERE status = 'complete' ORDER BY year DESC, month DESC, created_at DESC";
$complete_result = mysqli_query($conn, $complete_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chicken Farm Data Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            background-color: #f4f7f6;
        }
        .container {
            margin-top: 50px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e9ecef;
            font-weight: bold;
            color: #343a40;
            text-align: center;
        }
        .batch-box {
            margin-bottom: 1rem;
        }
        /* Styles to make the link look like a button/card */
        .batch-box a {
            text-decoration: none;
            font-family: Arial, sans-serif;
            color: #fff;
            padding: 15px 20px;
            border-radius: .25rem;
            display: block;
            text-align: left;
            box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
            transition: all .2s ease-in-out;
            position: relative; 
        }
        .batch-box a:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);
        }
        /* Styles for small summary text */
        .batch-box a small {
            display: block;
            font-weight: normal;
            margin-top: 5px;
            color: rgba(255, 255, 255, 0.75);
            line-height: 1.4; /* Better spacing for two lines of small text */
        }
        /* Style override for sun icon text to maintain contrast */
        .batch-box a[style*="#ffc107"] small {
             color: rgba(0, 0, 0, 0.7);
        }
        /* Delete Button styling */
        .delete-btn {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.2em;
            transition: color 0.2s ease;
        }
        /* Delete button on yellow background */
        .batch-box a[style*="#ffc107"] .delete-btn {
            color: rgba(0, 0, 0, 0.5); 
        }
        .delete-btn:hover {
            color: #fff;
        }
    </style>
</head>
<body>

<div class="container">
    <h1 class="text-center my-4">Chicken Farm Data Management</h1>
    <div class="row text-center mb-4">
        <div class="col-md-6 mb-2">
            <a href="data_entry.php" class="btn btn-success btn-lg btn-block"><i class="fas fa-plus-circle"></i> New Data Entry</a>
        </div>
        <div class="col-md-6 mb-2">
            <a href="data_display.php" class="btn btn-primary btn-lg btn-block"><i class="fas fa-chart-bar"></i> View Stored Data</a>
        </div>
    </div>
    

    <div class="card">
        <div class="card-header">Incomplete Batches (Click to Continue)</div>
        <div class="card-body">
            <?php
            if (mysqli_num_rows($incomplete_result) > 0) {
                while ($row = mysqli_fetch_assoc($incomplete_result)) {
                    $season = get_season($row['month']);
                    $monthName = date('F', mktime(0, 0, 0, $row['month'], 10));
                    
                    // --- FETCH TOTAL DEATHS AND FEED CONSUMED ---
                    $summary_query = "SELECT SUM(death_in_day) AS total_deaths, SUM(feed_taken) AS total_feed FROM chicken_data WHERE batch_no = ? AND year = ? AND month = ?";
                    
                    if ($stmt_summary = mysqli_prepare($conn, $summary_query)) {
                        mysqli_stmt_bind_param($stmt_summary, "sii", $row['batch_no'], $row['year'], $row['month']);
                        mysqli_stmt_execute($stmt_summary);
                        $summary_result = mysqli_stmt_get_result($stmt_summary);
                        $summary_row = mysqli_fetch_assoc($summary_result);
                        
                        $total_deaths = $summary_row['total_deaths'] ?? 0;
                        $total_feed = $summary_row['total_feed'] ?? 0.00; 
                        
                        mysqli_stmt_close($stmt_summary);
                    } else {
                        $total_deaths = 0;
                        $total_feed = 0.00;
                    }
                    // --- END FETCH ---

                    $initial_chickens = $row['initial_chickens'];
                    $mortality_rate = ($initial_chickens > 0) ? ($total_deaths / $initial_chickens) * 100 : 0;
                    
                    ?>
                    <div class="batch-box">
                        <a href="data_entry.php?batch_no=<?= urlencode($row['batch_no']) ?>&year=<?= $row['year'] ?>&month=<?= $row['month'] ?>"
                           style="background-color:<?= $season['color'] ?>; color:<?= $season['text-color'] ?>;">
                            
                            <span style="font-size:1.1em; font-weight:bold;"><?= htmlspecialchars($season['icon'] . ' Batch ' . $row['batch_no'] . ' - ' . $monthName . ' ' . $row['year']) ?></span>
                            
                            <small>Mortality: <?= number_format($mortality_rate, 2) ?>%</small>

                            <small>Deaths: <?= number_format($total_deaths, 0) ?> | Feed: <?= number_format($total_feed, 2) ?> kg</small>
                            
                            <span class="delete-btn" onclick="deleteBatch(event,'<?= $row['batch_no'] ?>','<?= $row['year'] ?>','<?= $row['month'] ?>')">
    <i class="fas fa-trash-alt"></i>
</span>
                        </a>
                    </div>
                    <?php
                }
            } else {
                echo '<p class="text-center">No incomplete batches found.</p>';
            }
            ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Completed Batches</div>
        <div class="card-body">
            <?php
            if (mysqli_num_rows($complete_result) > 0) {
                while ($row = mysqli_fetch_assoc($complete_result)) {
                    $season = get_season($row['month']);
                    $monthName = date('F', mktime(0, 0, 0, $row['month'], 10));

                    // --- FETCH TOTAL DEATHS AND FEED CONSUMED ---
                    $summary_query = "SELECT SUM(death_in_day) AS total_deaths, SUM(feed_taken) AS total_feed FROM chicken_data WHERE batch_no = ? AND year = ? AND month = ?";
                    
                    if ($stmt_summary = mysqli_prepare($conn, $summary_query)) {
                        mysqli_stmt_bind_param($stmt_summary, "sii", $row['batch_no'], $row['year'], $row['month']);
                        mysqli_stmt_execute($stmt_summary);
                        $summary_result = mysqli_stmt_get_result($stmt_summary);
                        $summary_row = mysqli_fetch_assoc($summary_result);
                        
                        $total_deaths = $summary_row['total_deaths'] ?? 0;
                        $total_feed = $summary_row['total_feed'] ?? 0.00; 
                        
                        mysqli_stmt_close($stmt_summary);
                    } else {
                        $total_deaths = 0;
                        $total_feed = 0.00;
                    }
                    // --- END FETCH ---

                    $initial_chickens = $row['initial_chickens'];
                    $mortality_rate = ($initial_chickens > 0) ? ($total_deaths / $initial_chickens) * 100 : 0;
                    
                    ?>
                    <div class="batch-box">
                        <a href="data_entry.php?batch_no=<?= urlencode($row['batch_no']) ?>&year=<?= $row['year'] ?>&month=<?= $row['month'] ?>"
                           style="background-color:<?= $season['color'] ?>; color:<?= $season['text-color'] ?>;">
                            
                            <span style="font-size:1.1em; font-weight:bold;"><?= htmlspecialchars($season['icon'] . ' Batch ' . $row['batch_no'] . ' - ' . $monthName . ' ' . $row['year']) ?></span>
                            
                            <small>Mortality: <?= number_format($mortality_rate, 2) ?>%</small>

                            <small>Deaths: <?= number_format($total_deaths, 0) ?> | Feed: <?= number_format($total_feed, 2) ?> kg</small>
                            
                            <span class="delete-btn" onclick="event.preventDefault(); if(confirm('Are you sure you want to delete this batch? This action cannot be undone.')) { window.location.href = 'delete_batch.php?batch_no=<?= urlencode($row['batch_no']) ?>&year=<?= $row['year'] ?>&month=<?= $row['month'] ?>'; }">
                                <i class="fas fa-trash-alt"></i>
                            </span>
                        </a>
                    </div>
                    <?php
                }
            } else {
                echo '<p class="text-center">No completed batches found.</p>';
            }
            ?>
        </div>
    </div>
</div>
    <script>
function deleteBatch(e,batch,year,month){
    e.stopPropagation();
    e.preventDefault();

    if(confirm("Are you sure you want to delete this batch?")){
        window.location.href = "delete_batch.php?batch_no="+batch+"&year="+year+"&month="+month;
    }
}
</script>

</body>
</html>
