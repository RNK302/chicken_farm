<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

// Function to determine the month's season and corresponding color
function get_season($month) {

    switch ($month) {

        case 3:
        case 4:
        case 5:
            return [
                'icon' => '☀️',
                'color' => '#ffc107',
                'text-color' => '#000'
            ];

        case 6:
        case 7:
        case 8:
            return [
                'icon' => '🌧️',
                'color' => '#17a2b8',
                'text-color' => '#fff'
            ];

        case 9:
        case 10:
        case 11:
            return [
                'icon' => '🍂',
                'color' => '#fd7e14',
                'text-color' => '#fff'
            ];

        case 12:
        case 1:
        case 2:
            return [
                'icon' => '❄️',
                'color' => '#6c757d',
                'text-color' => '#fff'
            ];

        default:
            return [
                'icon' => '☀️',
                'color' => '#ffc107',
                'text-color' => '#000'
            ];
    }
}

// Fetch incomplete batches
$incomplete_sql = "SELECT * FROM chicken_batches 
                   WHERE status = 'incomplete' 
                   ORDER BY year ASC, month ASC, created_at ASC";

$incomplete_result = mysqli_query($conn, $incomplete_sql);

if (!$incomplete_result) {
    die("Incomplete Query Error: " . mysqli_error($conn));
}

// Fetch completed batches
$complete_sql = "SELECT * FROM chicken_batches 
                 WHERE status = 'complete' 
                 ORDER BY year DESC, month DESC, created_at DESC";

$complete_result = mysqli_query($conn, $complete_sql);

if (!$complete_result) {
    die("Complete Query Error: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Chicken Farm Data Management</title>

    <link rel="stylesheet"
        href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

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
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e9ecef;
            font-weight: bold;
            text-align: center;
        }

        .batch-box {
            margin-bottom: 1rem;
        }

        .batch-box a {
            text-decoration: none;
            padding: 15px 20px;
            border-radius: .25rem;
            display: block;
            position: relative;
            transition: all .2s ease;
        }

        .batch-box a:hover {
            transform: translateY(-2px);
        }

        .batch-box a small {
            display: block;
            margin-top: 5px;
        }

        .delete-btn {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            font-size: 1.2em;
            cursor: pointer;
        }

    </style>

</head>

<body>

<div class="container">

    <h1 class="text-center my-4">
        Chicken Farm Data Management
    </h1>

    <div class="row text-center mb-4">

        <div class="col-md-6 mb-2">

            <a href="data_entry.php"
               class="btn btn-success btn-lg btn-block">

                <i class="fas fa-plus-circle"></i>
                New Data Entry

            </a>

        </div>

        <div class="col-md-6 mb-2">

            <a href="data_display.php"
               class="btn btn-primary btn-lg btn-block">

                <i class="fas fa-chart-bar"></i>
                View Stored Data

            </a>

        </div>

    </div>

    <!-- Incomplete Batches -->

    <div class="card">

        <div class="card-header">
            Incomplete Batches
        </div>

        <div class="card-body">

        <?php

        if (mysqli_num_rows($incomplete_result) > 0) {

            while ($row = mysqli_fetch_assoc($incomplete_result)) {

                $season = get_season($row['month']);

                $monthName = date(
                    'F',
                    mktime(0,0,0,$row['month'],10)
                );

                $summary_query = "
                    SELECT 
                        SUM(death_in_day) AS total_deaths,
                        SUM(feed_taken) AS total_feed
                    FROM chicken_data
                    WHERE batch_no = ?
                    AND year = ?
                    AND month = ?
                ";

                $total_deaths = 0;
                $total_feed = 0;

                if ($stmt_summary = mysqli_prepare($conn, $summary_query)) {

                    mysqli_stmt_bind_param(
                        $stmt_summary,
                        "sii",
                        $row['batch_no'],
                        $row['year'],
                        $row['month']
                    );

                    mysqli_stmt_execute($stmt_summary);

                    mysqli_stmt_bind_result(
                        $stmt_summary,
                        $total_deaths,
                        $total_feed
                    );

                    mysqli_stmt_fetch($stmt_summary);

                    mysqli_stmt_close($stmt_summary);
                }

                $total_deaths = $total_deaths ?? 0;
                $total_feed = $total_feed ?? 0;

                $initial_chickens = $row['initial_chickens'] ?? 0;

                $mortality_rate = ($initial_chickens > 0)
                    ? ($total_deaths / $initial_chickens) * 100
                    : 0;

                ?>

                <div class="batch-box">

                    <a href="data_entry.php?batch_no=<?php echo urlencode($row['batch_no']); ?>&year=<?php echo $row['year']; ?>&month=<?php echo $row['month']; ?>"
                       style="background-color:<?php echo $season['color']; ?>; color:<?php echo $season['text-color']; ?>;">

                        <span style="font-size:1.1em; font-weight:bold;">

                            <?php
                            echo htmlspecialchars(
                                $season['icon'] .
                                ' Batch ' .
                                $row['batch_no'] .
                                ' - ' .
                                $monthName .
                                ' ' .
                                $row['year']
                            );
                            ?>

                        </span>

                        <small>
                            Mortality:
                            <?php echo number_format($mortality_rate, 2); ?>%
                        </small>

                        <small>
                            Deaths:
                            <?php echo number_format($total_deaths, 0); ?>

                            |

                            Feed:
                            <?php echo number_format($total_feed, 2); ?> kg
                        </small>

                        <span class="delete-btn"
                              onclick="deleteBatch(event,'<?php echo $row['batch_no']; ?>','<?php echo $row['year']; ?>','<?php echo $row['month']; ?>')">

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

</div>

<script>

function deleteBatch(e,batch,year,month){

    e.preventDefault();
    e.stopPropagation();

    if(confirm("Are you sure you want to delete this batch?")){

        window.location.href =
            "delete_batch.php?batch_no=" +
            batch +
            "&year=" +
            year +
            "&month=" +
            month;
    }
}

</script>

</body>
</html>
