<?php
include 'db.php';
// Initialize filter variables
$batch_no_filter = '';
$year_filter = date('Y');
$month_filter = date('m');
$all_data = [];
$total_death = 0;
$total_feed = 0;
$total_chickens = 0;
$mortality_rate = 0;

// Fetch existing batch numbers for filtering
$batch_numbers = [];
$stmt = $conn->prepare("SELECT DISTINCT batch_no FROM chicken_data ORDER BY batch_no DESC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $batch_numbers[] = $row['batch_no'];
}
$stmt->close();

// Handle filter form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter'])) {
    $batch_no_filter = trim($_POST['batch_no'] ?? '');
    $year_filter = intval($_POST['year'] ?? date('Y'));
    $month_filter = intval($_POST['month'] ?? date('n'));
}

// Fetch filtered data using prepared statements
$query = "SELECT * FROM chicken_data WHERE 1=1";
$params = [];
$types = '';

if ($batch_no_filter) {
    $query .= " AND batch_no = ?";
    $params[] = $batch_no_filter;
    $types .= 's';
}
if ($year_filter) {
    $query .= " AND year = ?";
    $params[] = $year_filter;
    $types .= 'i';
}
if ($month_filter) {
    $query .= " AND month = ?";
    $params[] = $month_filter;
    $types .= 'i';
}

$query .= " ORDER BY day ASC";

$stmt = $conn->prepare($query);
if ($stmt && count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_data[] = $row;
        $total_death += $row['death_in_day'];
        $total_feed += $row['feed_taken'];
    }
    $stmt->close();
}

// Get batch info for mortality rate calculation
if ($batch_no_filter && $year_filter && $month_filter) {
    $stmt = $conn->prepare("SELECT initial_chickens FROM chicken_batches WHERE batch_no = ? AND year = ? AND month = ?");
    $stmt->bind_param("sii", $batch_no_filter, $year_filter, $month_filter);
    $stmt->execute();
    $batch_result = $stmt->get_result();
    if ($batch_data = $batch_result->fetch_assoc()) {
        $total_chickens = $batch_data['initial_chickens'];
        if ($total_chickens > 0) {
            $mortality_rate = ($total_death / $total_chickens) * 100;
        }
    }
    $stmt->close();
}
?>

<?php include 'navbar.php'; ?>

        <!-- PAGE TITLE -->
        <div class="mb-4">
            <h1 class="page-title"><i class="fas fa-table"></i> Data Display & Analysis</h1>
            <p class="page-subtitle">View and analyze daily chicken farm data</p>
        </div>

        <!-- FILTER SECTION -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-filter"></i> Filter Data
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="batch_no">Batch No:</label>
                            <select name="batch_no" id="batch_no" class="form-control">
                                <option value="">-- All Batches --</option>
                                <?php foreach ($batch_numbers as $bn): ?>
                                    <option value="<?= htmlspecialchars($bn) ?>" <?= ($bn === $batch_no_filter) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($bn) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="year">Year:</label>
                            <input type="number" id="year" name="year" class="form-control" value="<?= htmlspecialchars($year_filter) ?>" min="2000" max="2099">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="month">Month:</label>
                            <select id="month" name="month" class="form-control">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= ($m == $month_filter) ? 'selected' : '' ?>>
                                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="filter" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter Data
                    </button>
                    <a href="data_display.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </form>
            </div>
        </div>

        <!-- SUMMARY CARDS -->
        <?php if (!empty($all_data)): ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card" style="border-left: 4px solid #667eea;">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Deaths</h6>
                        <h2 style="color: #dc3545; font-weight: bold;"><?= number_format($total_death) ?></h2>
                        <small class="text-muted">birds</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="border-left: 4px solid #ffc107;">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Feed</h6>
                        <h2 style="color: #ffc107; font-weight: bold;"><?= number_format($total_feed, 2) ?></h2>
                        <small class="text-muted">kg</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="border-left: 4px solid #17a2b8;">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Days</h6>
                        <h2 style="color: #17a2b8; font-weight: bold;"><?= count($all_data) ?></h2>
                        <small class="text-muted">records</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="border-left: 4px solid #28a745;">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Mortality Rate</h6>
                        <h2 style="color: <?= ($mortality_rate > 5) ? '#dc3545' : '#28a745' ?>; font-weight: bold;"><?= number_format($mortality_rate, 2) ?>%</h2>
                        <small class="text-muted">of flock</small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- DATA TABLE -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-database"></i> Filtered Data 
                <?php if (!empty($all_data)): ?>
                    <span class="badge badge-info"><?= count($all_data) ?> records</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($all_data)): ?>
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-info-circle"></i> No data found for the selected filters.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-barcode"></i> Batch No</th>
                                    <th><i class="fas fa-calendar"></i> Year</th>
                                    <th><i class="fas fa-calendar-day"></i> Month</th>
                                    <th><i class="fas fa-hourglass-half"></i> Day</th>
                                    <th><i class="fas fa-skull"></i> Deaths</th>
                                    <th><i class="fas fa-users"></i> Alive</th>
                                    <th><i class="fas fa-weight"></i> Feed (kg)</th>
                                    <th><i class="fas fa-dumbbell"></i> Avg Weight (kg)</th>
                                    <th><i class="fas fa-chart-line"></i> FCR</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_data as $data): ?>
                                    <?php 
                                    $fcr_value = 'N/A';
                                    if ($data['average_weight_kg'] > 0 && $data['feed_taken'] > 0) {
                                        $fcr_value = number_format($data['feed_taken'] / $data['average_weight_kg'], 2);
                                    }
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($data['batch_no']) ?></strong></td>
                                        <td><?= htmlspecialchars($data['year']) ?></td>
                                        <td><?= htmlspecialchars($data['month']) ?></td>
                                        <td><?= htmlspecialchars($data['day']) ?></td>
                                        <td>
                                            <span class="badge <?= $data['death_in_day'] > 0 ? 'badge-danger' : 'badge-success' ?>">
                                                <?= htmlspecialchars($data['death_in_day']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($data['alive_count']) ?></td>
                                        <td><?= number_format($data['feed_taken'], 2) ?></td>
                                        <td><?= number_format($data['average_weight_kg'], 3) ?></td>
                                        <td><?= $fcr_value ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- EXPORT & ACTIONS -->
        <?php if (!empty($all_data)): ?>
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-download"></i> Export Data
                    </div>
                    <div class="card-body">
                        <form method="POST" action="export_data.php" style="display: inline;">
                            <input type="hidden" name="batch_no" value="<?= htmlspecialchars($batch_no_filter) ?>">
                            <input type="hidden" name="year" value="<?= $year_filter ?>">
                            <input type="hidden" name="month" value="<?= $month_filter ?>">
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fas fa-file-excel"></i> Export as Excel
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-print"></i> Print
                    </div>
                    <div class="card-body">
                        <button onclick="window.print()" class="btn btn-info btn-block">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
