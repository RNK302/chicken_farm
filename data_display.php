<?php
session_start();

// Database connection
$host = 'localhost'; // Change if necessary
$db = 'chicken_farm'; // Your database name
$user = 'root'; // Your database user
$pass = ''; // Your database password

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize filter variables
$selected_year = '';
$selected_month = '';
$selected_batch_no = ''; // New variable for batch number
$filtered_data = [];

// Handle filter submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['filter'])) {
    $selected_year = intval(htmlspecialchars($_POST['year'], ENT_QUOTES, 'UTF-8'));
    $selected_month = intval($_POST['month']);
    $selected_batch_no = htmlspecialchars($_POST['batch_no'], ENT_QUOTES, 'UTF-8'); // Get batch number

    // Prepare the SQL query
    $sql = "SELECT day, batch_no, death_in_day, feed_taken 
            FROM chicken_data 
            WHERE year = ? AND month = ?
            ORDER BY day"; // Sort by day

    // Append batch number filter if provided
    if (!empty($selected_batch_no)) {
        $sql .= " AND batch_no = ?";
    }

    // Prepare and bind parameters
    $stmt = $conn->prepare($sql);
    if (!empty($selected_batch_no)) {
        $stmt->bind_param("iis", $selected_year, $selected_month, $selected_batch_no);
    } else {
        $stmt->bind_param("ii", $selected_year, $selected_month);
    }

    // Execute the statement
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    // Fetch data
    while ($row = $result->fetch_assoc()) {
        $filtered_data[$row['day']] = $row;
    }

    $stmt->close();
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
        <input type='number' name='year' class='form-control' value='$selected_year' required>
      </div>";

echo "<div class='form-group'>
        <label for='month'>Select Month:</label>
        <select name='month' class='form-control' required>";
for ($m = 1; $m <= 12; $m++) {
    $selected = ($m == $selected_month) ? 'selected' : '';
    echo "<option value='$m' $selected>" . date('F', mktime(0, 0, 0, $m, 1)) . "</option>";
}
echo "</select></div>";

// New Batch No Input
echo "<div class='form-group'>
        <label for='batch_no'>Batch No:</label>
        <input type='text' name='batch_no' class='form-control' value='$selected_batch_no'>
      </div>";

echo "<button type='submit' name='filter' class='btn btn-primary'>Filter Data</button>";
echo "</form>";

// Link to return to data entry
echo "<div class='text-center mt-4'>
        <a href='data_entry.php' class='btn btn-secondary'>Back to Data Entry</a>
      </div>";

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
    echo "<div class='alert alert-info mt-4'>No data available for the selected year, month, and batch number.</div>";
}

echo "</div>";
echo "</body>";
echo "</html>";

// Close the database connection
$conn->close();
?>
