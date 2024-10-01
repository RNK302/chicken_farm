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

// Initialize variables
$days_to_show = 31; // Days to show
$batch_no = '';
$existing_data = [];
$batch_numbers = [];

// Fetch existing batch numbers
$result = $conn->query("SELECT DISTINCT batch_no FROM chicken_data");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $batch_numbers[] = $row['batch_no'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $year = intval($_POST['year']);
    $month = intval($_POST['month']);
    $batch_no = trim($_POST['batch_no']);

    // Prepare the statement
    $stmt = $conn->prepare("INSERT INTO chicken_data (year, month, day, batch_no, death_in_day, feed_taken)
        VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE death_in_day = ?, feed_taken = ?");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Insert data for each day
    for ($day = 1; $day <= $days_to_show; $day++) {
        $death_in_day = intval($_POST["death_in_day_$day"] ?? 0);
        $feed_taken = intval($_POST["feed_taken_$day"] ?? 0);
        $stmt->bind_param("iiisiiii", $year, $month, $day, $batch_no, $death_in_day, $feed_taken, $death_in_day, $feed_taken);
        $stmt->execute();
    }

    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Check if a batch number is selected
if (isset($_GET['batch_no'])) {
    $batch_no = trim($_GET['batch_no']);
    $year = intval($_GET['year']);
    $month = intval($_GET['month']);

    // Fetch existing data for the selected batch number
    $stmt = $conn->prepare("SELECT * FROM chicken_data WHERE batch_no = ? AND year = ? AND month = ? ORDER BY day");
    $stmt->bind_param("sii", $batch_no, $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $existing_data[$row['day']] = $row;
    }
    $stmt->close();
}

// Start the HTML output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Chicken Data Entry</title>
    <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>
    <script src='https://code.jquery.com/jquery-3.5.1.min.js'></script>
</head>
<body>
<div class='container'>
    <h1 class='text-center mt-5'>Chicken Data Entry</h1>
    <form method='post' action=''>
        <div class='form-group'>
            <label for='batch_no'>Select Batch No:</label>
            <select name='batch_no' class='form-control' id='batchSelect' required>
                <option value=''>Select a Batch</option>
                <?php foreach ($batch_numbers as $bn): ?>
                    <option value='<?= $bn ?>' <?= ($bn === $batch_no) ? 'selected' : '' ?>><?= $bn ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class='form-group'>
            <label for='year'>Year:</label>
            <input type='number' name='year' class='form-control' value='<?= date('Y') ?>' required>
        </div>
        <div class='form-group'>
            <label for='month'>Select Month:</label>
            <select name='month' class='form-control' required>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value='<?= $m ?>' <?= ($m === (isset($month) ? $month : 0)) ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <table class='table table-bordered' id='dataEntryTable'>
            <thead>
                <tr>
                    <th>No. of Days</th>
                    <th>Death in Day</th>
                    <th>Feed Taken</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($day = 1; $day <= $days_to_show; $day++): ?>
                    <tr>
                        <td class='day-number'><?= $day ?></td>
                        <td><input type='number' name='death_in_day_<?= $day ?>' class='form-control' value='<?= $existing_data[$day]['death_in_day'] ?? 0 ?>' required></td>
                        <td><input type='number' name='feed_taken_<?= $day ?>' class='form-control' value='<?= $existing_data[$day]['feed_taken'] ?? 0 ?>' required></td>
                    </tr>
                <?php endfor; ?>
                <tr>
                    <td colspan='3' class='text-center'>
                        <button type='button' id='addDay' class='btn btn-secondary'>Add Another Day</button>
                    </td>
                </tr>
            </tbody>
        </table>
        <button type='submit' name='submit' class='btn btn-primary'>Submit Data</button>
    </form>
    <div class='text-center mt-5'>
        <a href='data_display.php' class='btn btn-secondary'>View Stored Data</a>
    </div>
</div>
<script>
$(document).ready(function() {
    $('#batchSelect').change(function() {
        const batch_no = $(this).val();
        const year = $('input[name="year"]').val();
        const month = $('select[name="month"]').val();

        if (batch_no) {
            window.location.href = '<?= $_SERVER['PHP_SELF'] ?>?batch_no=' + batch_no + '&year=' + year + '&month=' + month;
        } else {
            $('#dataEntryTable tbody').empty();
            for (let day = 1; day <= 31; day++) {
                $('#dataEntryTable tbody').append(`
                    <tr>
                        <td class='day-number'>${day}</td>
                        <td><input type='number' name='death_in_day_${day}' class='form-control' required></td>
                        <td><input type='number' name='feed_taken_${day}' class='form-control' required></td>
                    </tr>
                `);
            }
        }
    });

    $('#addDay').on('click', function() {
        const table = $('#dataEntryTable tbody');
        const rowCount = table.children().length - 1; // Exclude the last row (Add Another Day)
        const newRow = `
            <tr>
                <td class='day-number'>${rowCount + 1}</td>
                <td><input type='number' name='death_in_day_${rowCount + 1}' class='form-control' required></td>
                <td><input type='number' name='feed_taken_${rowCount + 1}' class='form-control' required></td>
            </tr>`;
        table.append(newRow);
    });
});
</script>
</body>
</html>
<?php
$conn->close();
?>
