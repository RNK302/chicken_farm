<?php
session_start();

// File to store data
$data_file = 'chicken_data.json';

// Load existing data
if (file_exists($data_file)) {
    $chicken_data = json_decode(file_get_contents($data_file), true);
} else {
    $chicken_data = [];
}

// Handle form submission for data entry
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $year = htmlspecialchars($_POST['year'], ENT_QUOTES, 'UTF-8');
    $month = intval($_POST['month']);
    $batch_no = htmlspecialchars($_POST['batch_no'], ENT_QUOTES, 'UTF-8');

    // Initialize the month data structure if not set
    if (!isset($chicken_data[$year][$month])) {
        $chicken_data[$year][$month] = array_fill(1, 31, [
            'batch_no' => $batch_no,
            'death_in_day' => '',
            'feed_taken' => ''
        ]);
    }

    // Update data for each day
    for ($day = 1; $day <= 31; $day++) {
        $chicken_data[$year][$month][$day]['death_in_day'] = htmlspecialchars($_POST["death_in_day_$day"] ?? '', ENT_QUOTES, 'UTF-8');
        $chicken_data[$year][$month][$day]['feed_taken'] = htmlspecialchars($_POST["feed_taken_$day"] ?? '', ENT_QUOTES, 'UTF-8');
    }

    // Save to file
    file_put_contents($data_file, json_encode($chicken_data));

    // Clear the inputs after submission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Start the HTML output
echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>
        <title>Chicken Data Entry</title>
        <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>
      </head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1 class='text-center mt-5'>Chicken Data Entry</h1>";

// Data Entry Form
echo "<form method='post' action=''>";

// Batch Number Input
echo "<div class='form-group'>
        <label for='batch_no'>Batch No:</label>
        <input type='text' name='batch_no' class='form-control' required>
      </div>";

echo "<div class='form-group'>
        <label for='year'>Year:</label>
        <input type='text' name='year' class='form-control' value='" . date('Y') . "' required>
      </div>";

echo "<div class='form-group'>
        <label for='month'>Select Month:</label>
        <select name='month' class='form-control' required>";
for ($m = 1; $m <= 12; $m++) {
    echo "<option value='$m'>" . date('F', mktime(0, 0, 0, $m, 1)) . "</option>";
}
echo "</select></div>";

echo "<table class='table table-bordered'>";
echo "<tr>
        <th>No. of Days</th>
        <th>Death in Day</th>
        <th>Feed Taken</th>
      </tr>";

$total_death_count = 0; // Initialize total death count
$total_feed_taken_count = 0; // Initialize total feed taken count

for ($day = 1; $day <= 31; $day++) {
    echo "<tr>";
    echo "<td>$day</td>";
    echo "<td><input type='text' name='death_in_day_$day' class='form-control' oninput='updateTotalDeath()'></td>";
    echo "<td><input type='text' name='feed_taken_$day' class='form-control'></td>";
    echo "</tr>";
}

// Calculate total death and total feed taken count from the posted data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    for ($day = 1; $day <= 31; $day++) {
        $total_death_count += intval($_POST["death_in_day_$day"] ?? 0);
        $total_feed_taken_count += intval($_POST["feed_taken_$day"] ?? 0);
    }
}

echo "<tr>
        <td colspan='2' class='text-right'><strong>Total Deaths:</strong></td>
        <td><strong>$total_death_count</strong></td>
      </tr>";

echo "<tr>
        <td colspan='2' class='text-right'><strong>Total Feed Taken:</strong></td>
        <td><strong>$total_feed_taken_count</strong></td>
      </tr>";

echo "</table>";
echo "<button type='submit' name='submit' class='btn btn-primary'>Submit Data</button>";
echo "</form>";

// Link to view stored data
echo "<div class='text-center mt-5'>
        <a href='data_display.php' class='btn btn-secondary'>View Stored Data</a>
      </div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
