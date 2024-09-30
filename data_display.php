<?php
session_start();

// File to store data
$data_file = 'chicken_data.json';

// Load existing data
$chicken_data = [];
if (file_exists($data_file)) {
    $chicken_data = json_decode(file_get_contents($data_file), true);
}

// Initialize filter variables
$selected_year = '';
$selected_month = '';
$filtered_data = [];

// Handle filter submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['filter'])) {
    $selected_year = htmlspecialchars($_POST['year'], ENT_QUOTES, 'UTF-8');
    $selected_month = intval($_POST['month']);

    // Filter data
    if (isset($chicken_data[$selected_year][$selected_month])) {
        $filtered_data = $chicken_data[$selected_year][$selected_month];
    }
}

// Start the HTML output
echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>
        <title>Stored Chicken Data</title>
        <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>
      </head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1 class='text-center mt-5'>Stored Chicken Data</h1>";

// Filter Form
echo "<form method='post' action=''>";
echo "<div class='form-group'>
        <label for='year'>Year:</label>
        <input type='text' name='year' class='form-control' value='$selected_year' required>
      </div>";

echo "<div class='form-group'>
        <label for='month'>Select Month:</label>
        <select name='month' class='form-control' required>";
for ($m = 1; $m <= 12; $m++) {
    $selected = ($m == $selected_month) ? 'selected' : '';
    echo "<option value='$m' $selected>" . date('F', mktime(0, 0, 0, $m, 1)) . "</option>";
}
echo "</select></div>";

echo "<button type='submit' name='filter' class='btn btn-primary'>Filter Data</button>";
echo "</form>";

// Data Display
if (!empty($filtered_data)) {
    $total_death_count = 0; // Initialize total death count
    $total_feed_taken_count = 0; // Initialize total feed taken count

    echo "<table class='table table-bordered mt-4'>";
    echo "<tr>
            <th>No. of Days</th>
            <th>Batch No</th>
            <th>Death in Day</th>
            <th>Feed Taken</th>
          </tr>";

    foreach ($filtered_data as $day => $data) {
        echo "<tr>";
        echo "<td>$day</td>";
        echo "<td>" . (isset($data['batch_no']) ? $data['batch_no'] : 'N/A') . "</td>";
        echo "<td>" . (isset($data['death_in_day']) ? $data['death_in_day'] : '0') . "</td>";
        echo "<td>" . (isset($data['feed_taken']) ? $data['feed_taken'] : '0') . "</td>";
        echo "</tr>";

        // Accumulate totals
        $total_death_count += intval($data['death_in_day'] ?? 0);
        $total_feed_taken_count += intval($data['feed_taken'] ?? 0);
    }

    echo "</table>";

    // Display totals
    echo "<div class='alert alert-info'>
            <strong>Total Deaths:</strong> $total_death_count<br>
            <strong>Total Feed Taken:</strong> $total_feed_taken_count
          </div>";
} else {
    echo "<div class='alert alert-info mt-4'>No data available for the selected year and month.</div>";
}

echo "<div class='text-center mt-5'>
        <a href='data_entry.php' class='btn btn-secondary'>Back to Data Entry</a>
      </div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
