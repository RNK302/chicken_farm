<?php
include 'db.php';
session_start();

// Fetch incomplete and completed batches from chicken_batches table
$incomplete_batches = [];
$completed_batches = [];

$result = $conn->query("SELECT * FROM chicken_batches");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['status'] === 'incomplete') {
            $incomplete_batches[] = $row;
        } elseif ($row['status'] === 'complete') {
            $completed_batches[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Chicken Farm Dashboard</title>
    <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center mb-4">Chicken Farm Data Management</h2>

    <div class="text-center mb-4">
        <a href="data_entry.php" class="btn btn-success btn-lg">New Data Entry</a>
        <a href="data_display.php" class="btn btn-primary btn-lg">View Stored Data</a>
    </div>

    <!-- Incomplete Batches -->
    <div class="mt-5">
        <h4>Incomplete Batches</h4>
        <?php if (empty($incomplete_batches)): ?>
            <p>No incomplete batches found.</p>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($incomplete_batches as $batch): ?>
                    <li class="list-group-item">
                        <a href="data_entry.php?batch_no=<?= $batch['batch_no'] ?>&year=<?= $batch['year'] ?>&month=<?= $batch['month'] ?>">
                            Batch <?= $batch['batch_no'] ?> - <?= $batch['month'] ?>/<?= $batch['year'] ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Completed Batches -->
    <div class="mt-5">
        <h4>Completed Batches</h4>
        <?php if (empty($completed_batches)): ?>
            <p>No completed batches yet.</p>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($completed_batches as $batch): ?>
                    <li class="list-group-item">
                    <a href="data_entry.php?batch_no=<?= $batch['batch_no'] ?>&year=<?= $batch['year'] ?>&month=<?= $batch['month'] ?>">
                        Batch <?= $batch['batch_no'] ?> - <?= $batch['month'] ?>/<?= $batch['year'] ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
