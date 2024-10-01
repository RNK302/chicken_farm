<?php
session_start();

// Database connection
$host = 'localhost'; // Adjust as necessary
$db = 'chicken_farm';
$user = 'root'; // Adjust as necessary
$pass = ''; // Adjust as necessary

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$days_to_show = 1; // Start with showing data for one day

// Handle form submission for data entry
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $year = intval(htmlspecialchars($_POST['year'], ENT_QUOTES, 'UTF-8'));
    $month = intval($_POST['month']);
    $batch_no = htmlspecialchars($_POST['batch_no'], ENT_QUOTES, 'UTF-8');

    // Prepare the statement
    $stmt = $conn->prepare("INSERT INTO chicken_data (year, month, day, batch_no, death_in_day, feed_taken)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE death_in_day = ?, feed_taken = ?");
    
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Insert data for each day
    for ($day = 1; $day <= $days_to_show; $day++) {
        $death_in_day = intval(htmlspecialchars($_POST["death_in_day_$day"] ?? 0, ENT_QUOTES, 'UTF-8'));
        $feed_taken = intval(htmlspecialchars($_POST["feed_taken_$day"] ?? 0, ENT_QUOTES, 'UTF-8'));

        // Bind the parameters
        $stmt->bind_param("iiisiiii", $year, $month, $day, $batch_no, $death_in_day, $feed_taken, $death_in_day, $feed_taken);
        
        // Execute the statement
        $stmt->execute();
    }

    $stmt->close(); // Close the prepared statement
    header("Location: " . $_SERVER['PHP_SELF'] . "?days_to_show=" . $days_to_show);
    exit();
}

// Get the number of days to show from URL parameter or default to 1
if (isset($_GET['days_to_show'])) {
    $days_to_show = intval($_GET['days_to_show']);
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

echo "<div class='form-group'>
        <label for='batch_no'>Batch No:</label>
        <input type='text' name='batch_no' class='form-control' required>
      </div>";

echo "<div class='form-group'>
        <label for='year'>Year:</label>
        <input type='number' name='year' class='form-control' value='" . date('Y') . "' required>
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

// Show input fields for the specified number of days
for ($day = 1; $day <= $days_to_show; $day++) {
    echo "<tr>";
    echo "<td class='day-number'>$day</td>";
    echo "<td><input type='number' name='death_in_day_$day' class='form-control' required></td>";
    echo "<td><input type='number' name='feed_taken_$day' class='form-control' required></td>";
    echo "</tr>";
}

// Provide an option to add more days
echo "<tr>
        <td colspan='3' class='text-center'>
            <button type='button' id='addDay' class='btn btn-secondary'>Add Another Day</button>
        </td>
      </tr>";

echo "</table>";
echo "<button type='submit' name='submit' class='btn btn-primary'>Submit Data</button>";
echo "</form>";

echo "<div class='text-center mt-5'>
        <a href='data_display.php' class='btn btn-secondary'>View Stored Data</a>
      </div>";

echo "</div>";
echo "</body>";
echo "</html>";

$conn->close();
?>

<script>
document.getElementById('addDay').addEventListener('click', function() {
    const table = document.querySelector('table');
    const rowCount = table.rows.length - 1; // Exclude the last row (Add Another Day)
    const newRow = table.insertRow(rowCount);
    const cell1 = newRow.insertCell(0);
    const cell2 = newRow.insertCell(1);
    const cell3 = newRow.insertCell(2);
    
    // Set the new day number correctly
    cell1.innerHTML = rowCount; // This will show the correct day number
    cell2.innerHTML = '<input type="number" name="death_in_day_' + (rowCount) + '" class="form-control" required>';
    cell3.innerHTML = '<input type="number" name="feed_taken_' + (rowCount) + '" class="form-control" required>';
});
</script>
