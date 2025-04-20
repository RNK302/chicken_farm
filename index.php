<?php
include 'db.php';
session_start();


// Fetch unique incomplete batches
$incomplete = $conn->query("
    SELECT DISTINCT batch_no, year, month 
    FROM chicken_batches 
    WHERE status = 'incomplete'
    ORDER BY year DESC, month DESC
");

// Fetch unique completed batches
$completed = $conn->query("
    SELECT DISTINCT batch_no, year, month 
    FROM chicken_batches 
    WHERE status = 'complete'
    ORDER BY year DESC, month DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Chicken Farm Dashboard</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            padding: 40px;
            background-color: #f9f9f9;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        .button-container {
            text-align: center;
            margin-bottom: 40px;
        }

        .button {
            padding: 12px 24px;
            margin: 0 10px;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            color: white;
            transition: background-color 0.3s ease;
        }

        .new-entry {
            background-color: #28a745;
        }

        .new-entry:hover {
            background-color: #218838;
        }

        .view-data {
            background-color: #007bff;
        }

        .view-data:hover {
            background-color: #0056b3;
        }

        .batch-section {
            max-width: 600px;
            margin: 0 auto 50px;
        }

        .batch-section h2 {
            margin-bottom: 15px;
            color: #444;
        }

        .batch-link {
            display: block;
            padding: 10px 15px;
            margin-bottom: 8px;
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 6px;
            text-decoration: none;
            color: #007bff;
            transition: background-color 0.2s;
        }

        .batch-link:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>

    <h1>Chicken Farm Data Management</h1>

    <div class="button-container">
        <button class="button new-entry" onclick="window.location.href='data_entry.php'">New Data Entry</button>
        <button class="button view-data" onclick="window.location.href='data_display.php'">View Stored Data</button>
    </div>

    <div class="batch-section">
        <h2>Incomplete Batches</h2>
        <?php if ($incomplete->num_rows > 0): ?>
            <?php while ($row = $incomplete->fetch_assoc()): ?>
                <a class="batch-link" href="data_entry.php?batch_no=<?= $row['batch_no'] ?>&year=<?= $row['year'] ?>&month=<?= $row['month'] ?>">
                    Batch <?= htmlspecialchars($row['batch_no']) ?> - <?= $row['month'] ?>/<?= $row['year'] ?>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No incomplete batches found.</p>
        <?php endif; ?>
    </div>

    <div class="batch-section">
        <h2>Completed Batches</h2>
        <?php if ($completed->num_rows > 0): ?>
            <?php while ($row = $completed->fetch_assoc()): ?>
                <a class="batch-link" href="data_display.php?batch_no=<?= $row['batch_no'] ?>&year=<?= $row['year'] ?>&month=<?= $row['month'] ?>">
                    Batch <?= htmlspecialchars($row['batch_no']) ?> - <?= $row['month'] ?>/<?= $row['year'] ?>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No completed batches found.</p>
        <?php endif; ?>
    </div>

</body>
</html>

<?php $conn->close(); ?>
