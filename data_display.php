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

// Initialize filter variables
$batch_no_filter = '';
$year_filter = date('Y');
$month_filter = date('m');
$all_data = [];
$total_death = 0;
$total_feed = 0;

// Fetch existing batch numbers for filtering
$batch_numbers = [];
$result = $conn->query("SELECT DISTINCT batch_no FROM chicken_data");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $batch_numbers[] = $row['batch_no'];
    }
}

// Handle filter form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter'])) {
    $batch_no_filter = trim($_POST['batch_no']);
    $year_filter = intval($_POST['year']);
    $month_filter = intval($_POST['month']);
}

// Fetch filtered data
$query = "SELECT * FROM chicken_data WHERE 1=1";
if ($batch_no_filter) {
    $query .= " AND batch_no = '$batch_no_filter'";
}
if ($year_filter) {
    $query .= " AND year = $year_filter";
}
if ($month_filter) {
    $query .= " AND month = $month_filter";
}

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_data[] = $row;
        $total_death += $row['death_in_day'];
        $total_feed += $row['feed_taken'];
    }
}

// Fetch incomplete batches
$incomplete_data = [];
$incomplete_query = "SELECT DISTINCT batch_no FROM chicken_data WHERE (year, month, day) NOT IN (SELECT year, month, day FROM chicken_data)";
$incomplete_result = $conn->query($incomplete_query);
if ($incomplete_result) {
    while ($row = $incomplete_result->fetch_assoc()) {
        $incomplete_data[] = $row['batch_no'];
    }
}

// Start the HTML output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Chicken Data Display</title>
    <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>
</head>
<body>
<div class='container'>
    <h1 class='text-center mt-5'>Chicken Data Display</h1>
    <form method='post' action=''>
        <div class='form-row'>
            <div class='form-group col-md-4'>
                <label for='batch_no'>Select Batch No:</label>
                <select name='batch_no' class='form-control'>
                    <option value=''>All Batches</option>
                    <?php foreach ($batch_numbers as $bn): ?>
                        <option value='<?= $bn ?>' <?= ($bn === $batch_no_filter) ? 'selected' : '' ?>><?= $bn ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class='form-group col-md-4'>
                <label for='year'>Year:</label>
                <input type='number' name='year' class='form-control' value='<?= $year_filter ?>' required>
            </div>
            <div class='form-group col-md-4'>
                <label for='month'>Select Month:</label>
                <select name='month' class='form-control' required>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value='<?= $m ?>' <?= ($m === $month_filter) ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        <button type='submit' name='filter' class='btn btn-primary'>Filter Data</button>
    </form>
    
    <h3 class='mt-5'>Filtered Data</h3>
    <table class='table table-bordered'>
        <thead>
            <tr>
                <th>Batch No</th>
                <th>Year</th>
                <th>Month</th>
                <th>Day</th>
                <th>Death in Day</th>
                <th>Feed Taken</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($all_data)): ?>
                <tr>
                    <td colspan='6' class='text-center'>No data found for the selected filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($all_data as $data): ?>
                    <tr>
                        <td><?= $data['batch_no'] ?></td>
                        <td><?= $data['year'] ?></td>
                        <td><?= $data['month'] ?></td>
                        <td><?= $data['day'] ?></td>
                        <td><?= $data['death_in_day'] ?></td>
                        <td><?= $data['feed_taken'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <h3 class='mt-5'>Total Feed and Death</h3>
    <p>Total Death: <?= $total_death ?></p>
    <p>Total Feed Taken: <?= $total_feed ?></p>

    <h3 class='mt-5'>Incomplete Batches</h3>
    <ul>
        <?php foreach ($incomplete_data as $incomplete_batch): ?>
            <li><?= $incomplete_batch ?></li>
        <?php endforeach; ?>
    </ul>
</div>
</body>
</html>
<?php
$conn->close();
?>
