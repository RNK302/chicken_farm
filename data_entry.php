<?php
include 'db.php';
session_start();

// Prefill values if batch info is provided
$batch_no = $_GET['batch_no'] ?? '';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('n');
$total_chickens = '';

// Initialize daily values
$day_data = array_fill(1, 31, ['death' => 0, 'alive' => '', 'feed' => 0]);

if ($batch_no && $year && $month) {
    $result = $conn->query("SELECT * FROM chicken_data WHERE batch_no='$batch_no' AND year=$year AND month=$month ORDER BY day ASC");
    while ($row = $result->fetch_assoc()) {
        $day = (int)$row['day'];
        $day_data[$day] = [
            'death' => $row['death_in_day'],
            'alive' => $row['alive_count'],
            'feed' => $row['feed_taken']
        ];
        if ($day == 1) {
            $total_chickens = $row['alive_count'] + $row['death_in_day'];
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $batch_no = $_POST['batch_no'];
    $year = $_POST['year'];
    $month = $_POST['month'];
    $total_chickens = $_POST['total_chickens'];
    $status = ($_POST['action'] === 'complete') ? 'complete' : 'incomplete';

    $stmt = $conn->prepare("INSERT INTO chicken_batches (batch_no, year, month, status) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = ?");
    $stmt->bind_param("sisss", $batch_no, $year, $month, $status, $status);
    $stmt->execute();

    for ($day = 1; $day <= 31; $day++) {
        $death = $_POST['death_' . $day] ?? 0;
        $alive = $_POST['alive_' . $day] ?? 0;
        $feed = $_POST['feed_' . $day] ?? 0;

        $check = $conn->query("SELECT * FROM chicken_data WHERE batch_no='$batch_no' AND year=$year AND month=$month AND day=$day");
        if ($check->num_rows > 0) {
            $conn->query("UPDATE chicken_data SET death_in_day=$death, alive_count=$alive, feed_taken=$feed WHERE batch_no='$batch_no' AND year=$year AND month=$month AND day=$day");
        } else {
            $conn->query("INSERT INTO chicken_data (batch_no, year, month, day, death_in_day, alive_count, feed_taken) VALUES ('$batch_no', $year, $month, $day, $death, $alive, $feed)");
        }
    }

    echo "<script>alert('Data saved as $status'); window.location.href='index.php';</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Chicken Farm Data Entry</title>
    <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>
    <script src='https://code.jquery.com/jquery-3.5.1.min.js'></script>
</head>
<body>
<div class='container mt-4'>
    <h2 class='text-center mb-4'>Chicken Daily Entry Form</h2>

    <form method="post" action="">
         <div class="form-row mb-3">
            <div class="col">
                <label>Batch No</label>
                <input type="text" class="form-control" name="batch_no" placeholder="Enter Batch No" required value="<?= htmlspecialchars($batch_no) ?>">
            </div>
            <div class="col">
                <label>Year</label>
                <input type="number" name="year" class="form-control" value="<?= $year ?>" required>
            </div>
            <div class="col">
                <label>Month</label>
                <select class="form-control" name="month" required>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= ($month == $m) ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col">
                <label>Total Chickens (Day 1)</label>
                <input type="number" id="total_chickens" name="total_chickens" class="form-control" value="<?= $total_chickens ?>" required oninput="updateAliveCounts()">
            </div>
        </div>

        <table class="table table-bordered text-center">
            <thead class="thead-dark">
                <tr>
                    <th>Day</th>
                    <th>Death In Day</th>
                    <th>Alive Count</th>
                    <th>Feed Consumed in kgs</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($day = 1; $day <= 31; $day++): ?>
                <tr>
                    <td><?= $day ?></td>
                    <td>
                        <input type="number" class="form-control death" id="death_<?= $day ?>" name="death_<?= $day ?>" value="<?= $day_data[$day]['death'] ?>" oninput="updateAliveCounts()">
                    </td>
                    <td>
                        <input type="number" class="form-control alive" id="alive_<?= $day ?>" name="alive_<?= $day ?>" value="<?= $day_data[$day]['alive'] ?>" readonly>
                    </td>
                    <td>
                        <input type="number" class="form-control" name="feed_<?= $day ?>" value="<?= $day_data[$day]['feed'] ?>">
                    </td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <div class="text-center">
            <button class="btn btn-warning" type="submit" name="action" value="incomplete">💾 Save as Incomplete</button>
            <button class="btn btn-success" type="submit" name="action" value="complete">✅ Mark as Complete</button>
        </div>

    </form>
</div>

<script>
function updateAliveCounts() {
    const totalDays = 31;
    let totalChickens = parseInt(document.getElementById('total_chickens').value) || 0;

    for (let day = 1; day <= totalDays; day++) {
        let prevAlive = (day === 1) 
            ? totalChickens 
            : parseInt(document.getElementById('alive_' + (day - 1)).value) || 0;

        let deathToday = parseInt(document.getElementById('death_' + day).value) || 0;
        let todayAlive = prevAlive - deathToday;

        document.getElementById('alive_' + day).value = (todayAlive >= 0) ? todayAlive : 0;
    }
}
</script>
</body>
</html>
