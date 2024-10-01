<?php
session_start();

// Database connection
$host = 'localhost';
$db = 'chicken_farm';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch incomplete batches
$incomplete_batches = [];
$result = $conn->query("SELECT DISTINCT batch_no FROM chicken_data WHERE (death_in_day = 0 OR feed_taken = 0)");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $incomplete_batches[] = $row['batch_no'];
    }
}

// Start the HTML output
echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Chicken Farm Dashboard</title>
        <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>
      </head>";
echo "<body>";

echo "<div class='container mt-5'>";
echo "<h1 class='text-center'>Welcome to the Chicken Farm Dashboard</h1>";
echo "<div class='text-center mt-4'>";

// Navigation buttons
echo "<a href='data_entry.php' class='btn btn-primary'>Data Entry</a>
      <a href='data_display.php' class='btn btn-secondary'>View Stored Data</a>";
echo "</div>";

echo "<div class='mt-5'>";
echo "<h2>Incomplete Batches</h2>";
echo "<ul class='list-group'>";

if (empty($incomplete_batches)) {
    echo "<li class='list-group-item'>No incomplete batches found.</li>";
} else {
    foreach ($incomplete_batches as $batch) {
        echo "<li class='list-group-item'>
                <a href='data_entry.php?batch_no=$batch'>$batch</a>
              </li>";
    }
}

echo "</ul>";
echo "</div>";

echo "</div>"; // Close container
echo "</body>";
echo "</html>";

$conn->close();
?>
