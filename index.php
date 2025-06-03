<?php
include 'db.php';
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Chicken Farm Data Management</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px; text-align: center; }
        h1 { margin-bottom: 30px; }
        .button-container { margin-bottom: 40px; }
        button { padding: 10px 20px; margin: 0 10px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        .new-btn { background-color: #28a745; color: white; }
        .view-btn { background-color: #007bff; color: white; }
        .batch-section { text-align: left; max-width: 600px; margin: 0 auto 40px; }
        .batch-box { background: white; border: 1px solid #ccc; border-radius: 5px; padding: 10px 20px; margin-bottom: 10px; }
        .batch-box a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
<h1>Chicken Farm Data Management</h1>
<div class="button-container">
    <button class="new-btn" onclick="location.href='data_entry.php'">New Data Entry</button>
    <button class="view-btn" onclick="location.href='data_display.php'">View Stored Data</button>
</div>
<!-- Incomplete Batches -->
<div class="batch-section">
    <h2>Incomplete Batches</h2>
    <?php
    $incomplete_sql = "
        SELECT * FROM chicken_batches cb
        WHERE status = 'incomplete'
        AND NOT EXISTS (
            SELECT 1 FROM chicken_batches cb2
            WHERE cb.batch_no = cb2.batch_no
            AND cb.year = cb2.year
            AND cb.month = cb2.month
            AND cb2.status = 'complete'
        )
    ";
    $stmt = $conn->prepare($incomplete_sql);
    if ($stmt === false) {
        echo "Prepare failed: " . $conn->error;
    } else {
        $stmt->execute();
        $incomplete_result = $stmt->get_result();
        if ($incomplete_result && $incomplete_result->num_rows > 0) {
            while ($row = $incomplete_result->fetch_assoc()) {
                echo '<div class="batch-box"><a href="data_entry.php?batch_no=' . htmlspecialchars($row['batch_no']) . '&year=' . htmlspecialchars($row['year']) . '&month=' . htmlspecialchars($row['month']) . '">Batch ' . htmlspecialchars($row['batch_no']) . ' - ' . htmlspecialchars($row['month']) . '/' . htmlspecialchars($row['year']) . '</a></div>';
            }
        } else {
            echo "<p>No incomplete batches found.</p>";
        }
        $stmt->close();
    }
    ?>
</div>
<!-- Completed Batches -->
<div class="batch-section">
    <h2>Completed Batches</h2>
    <?php
    $complete_sql = "
        SELECT * FROM chicken_batches
        WHERE status = 'complete'
        GROUP BY batch_no, year, month
    ";
    $stmt = $conn->prepare($complete_sql);
    if ($stmt === false) {
        echo "Prepare failed: " . $conn->error;
    } else {
        $stmt->execute();
        $complete_result = $stmt->get_result();
        if ($complete_result && $complete_result->num_rows > 0) {
            while ($row = $complete_result->fetch_assoc()) {
                echo '<div class="batch-box"><a href="data_entry.php?batch_no=' . htmlspecialchars($row['batch_no']) . '&year=' . htmlspecialchars($row['year']) . '&month=' . htmlspecialchars($row['month']) . '">Batch ' . htmlspecialchars($row['batch_no']) . ' - ' . htmlspecialchars($row['month']) . '/' . htmlspecialchars($row['year']) . '</a></div>';
            }
        } else {
            echo "<p>No completed batches found.</p>";
        }
        $stmt->close();
    }
    mysqli_close($conn);
    ?>
</div>
</body>
</html>
