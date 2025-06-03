<?php
include 'db.php';
session_start();

// Seasonal Tip Logic
$current_month = date('n');
$seasonal_tip = "";
switch ($current_month) {
    case 12: case 1: case 2:
        $seasonal_tip = "Winter Tip: Ensure proper heating for the chickens to maintain their health."; break;
    case 3: case 4: case 5:
        $seasonal_tip = "Spring Tip: Keep the chicken coop clean to prevent springtime diseases."; break;
    case 6: case 7: case 8:
        $seasonal_tip = "Summer Tip: Provide plenty of fresh water to keep chickens hydrated."; break;
    case 9: case 10: case 11:
        $seasonal_tip = "Autumn Tip: Prepare for colder weather by reinforcing coop insulation."; break;
    default:
        $seasonal_tip = "Keep your chickens healthy and happy throughout the year!";
}

// Prefill values if batch info is provided
$batch_no = $_GET['batch_no'] ?? '';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('n');
$total_days = $_GET['total_days'] ?? 31;
$total_chickens = '';

// Initialize daily values
$day_data = array_fill(1, $total_days, ['death' => 0, 'alive' => '', 'feed' => 0, 'fcr' => 0]);

if ($batch_no && $year && $month) {
    $stmt = $conn->prepare("SELECT * FROM chicken_data WHERE batch_no=? AND year=? AND month=? ORDER BY day ASC");
    $stmt->bind_param("sii", $batch_no, $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $day = (int)$row['day'];
        $day_data[$day] = [
            'death' => (int)$row['death_in_day'],
            'alive' => (int)$row['alive_count'],
            'feed' => (int)$row['feed_taken'],
            'fcr' => 0,
        ];
        if ($day == 1) {
            $total_chickens = $row['alive_count'] + $row['death_in_day'];
        }
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $batch_no = $_POST['batch_no'];
    $year = $_POST['year'];
    $month = $_POST['month'];
    $total_days = $_POST['total_days'];
    $total_chickens = $_POST['total_chickens'];
    $status = ($_POST['action'] === 'complete') ? 'complete' : 'incomplete';

    $stmt = $conn->prepare("INSERT INTO chicken_batches (batch_no, year, month, status) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = ?");
    $stmt->bind_param("sisss", $batch_no, $year, $month, $status, $status);
    $stmt->execute();
    $stmt->close();

    for ($day = 1; $day <= $total_days; $day++) {
        $death = $_POST['death_' . $day] ?? 0;
        $alive = $_POST['alive_' . $day] ?? 0;
        $feed = $_POST['feed_' . $day] ?? 0;

        $stmt = $conn->prepare("SELECT COUNT(*) FROM chicken_data WHERE batch_no=? AND year=? AND month=? AND day=?");
        $stmt->bind_param("siii", $batch_no, $year, $month, $day);
        $stmt->execute();
        $stmt->bind_result($exists);
        $stmt->fetch();
        $stmt->close();

        if ($exists) {
            $stmt = $conn->prepare("UPDATE chicken_data SET death_in_day=?, alive_count=?, feed_taken=? WHERE batch_no=? AND year=? AND month=? AND day=?");
            $stmt->bind_param("iiisiii", $death, $alive, $feed, $batch_no, $year, $month, $day);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO chicken_data (batch_no, year, month, day, death_in_day, alive_count, feed_taken) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siiiiii", $batch_no, $year, $month, $day, $death, $alive, $feed);
            $stmt->execute();
            $stmt->close();
        }
    }
    echo "<script>alert('Data saved as $status'); window.location.href='index.php';</script>";
    exit;
}

// PHP: Prefill alive counts for display (if total_chickens is set)
if ($total_chickens !== '' && $total_chickens > 0) {
    $prev_alive = $total_chickens;
    for ($day = 1; $day <= $total_days; $day++) {
        $death = isset($day_data[$day]['death']) ? (int)$day_data[$day]['death'] : 0;
        if ($day == 1) {
            $day_data[$day]['alive'] = $total_chickens;
        } else {
            $day_data[$day]['alive'] = $prev_alive - $death;
        }
        $prev_alive = $day_data[$day]['alive'];
    }
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
    <div class="alert alert-info">
        <strong>Seasonal Tip:</strong> <?= htmlspecialchars($seasonal_tip) ?>
    </div>
    <form method="post" action="">
         <div class="form-row mb-3">
            <div class="col">
                <label>Batch No</label>
                <input type="text" class="form-control" name="batch_no" placeholder="Enter Batch No" required value="<?= htmlspecialchars($batch_no) ?>">
            </div>
            <div class="col">
                <label>Year</label>
                <input type="number" name="year" class="form-control" value="<?= htmlspecialchars($year) ?>" required>
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
                <label>Total Days</label>
                <input type="number" name="total_days" class="form-control" value="<?= htmlspecialchars($total_days) ?>" required onchange="changeDays(this.value)">
            </div>
            <div class="col">
                <label>Total Chickens (Day 1)</label>
                <input type="number" id="total_chickens" name="total_chickens" class="form-control" value="<?= htmlspecialchars($total_chickens) ?>" required oninput="updateAliveCounts()">
            </div>
        </div>
        <table class="table table-bordered text-center">
            <thead class="thead-dark">
                <tr>
                    <th>Day</th>
                    <th>Death in Day</th>
                    <th>Alive Count</th>
                    <th>Feed Consumed in kgs</th>
                    <th>FCR</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($day = 1; $day <= $total_days; $day++): 
                    $feed = $day_data[$day]['feed'];
                    $alive = $day_data[$day]['alive'];
                    $death = $day_data[$day]['death'];
                    $weight_gain = ($feed > 0 && $alive > 0) ? round($feed / 1.6, 2) : 0;
                    $fcr = ($weight_gain > 0) ? round($feed / $weight_gain, 2) : 0;
                ?>
                <tr>
                    <td><?= $day ?></td>
                    <td>
                        <input type="number" class="form-control" name="death_<?= $day ?>" value="<?= htmlspecialchars($death) ?>" oninput="updateAliveCounts()">
                    </td>
                    <td>
                        <input type="number" class="form-control" name="alive_<?= $day ?>" 
                        value="<?= htmlspecialchars($alive) ?>"
                        readonly>
                    </td>
                    <td><input type="number" class="form-control" name="feed_<?= $day ?>" value="<?= htmlspecialchars($feed) ?>"></td>
                    <td><?= htmlspecialchars($fcr) ?></td>
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
    var totalDays = parseInt(document.querySelector('[name="total_days"]').value) || 31;
    var totalChickens = parseInt(document.getElementById('total_chickens').value) || 0;
    for (var day = 1; day <= totalDays; day++) {
        var prevAlive = (day === 1) 
            ? totalChickens 
            : parseInt(document.querySelector('[name="alive_' + (day - 1) + '"]').value) || 0;
        var deathToday = parseInt(document.querySelector('[name="death_' + day + '"]').value) || 0;
        var todayAlive = prevAlive - deathToday;
        document.querySelector('[name="alive_' + day + '"]').value = (todayAlive >= 0) ? todayAlive : 0;
    }
}
window.onload = updateAliveCounts;
function changeDays(val) {
    var params = new URLSearchParams(window.location.search);
    params.set('total_days', val);
    <?php if ($batch_no) { ?>params.set('batch_no', '<?= $batch_no ?>');<?php } ?>
    <?php if ($year) { ?>params.set('year', '<?= $year ?>');<?php } ?>
    <?php if ($month) { ?>params.set('month', '<?= $month ?>');<?php } ?>
    window.location.search = params.toString();
}
</script>
</body>
</html>
