<?php
// data_entry.php (fixed version)
// include your database connection file
include 'db.php';
session_start();

// --- Seasonal data (unchanged) ---
$seasonal_data = [
    'Summer' => [
        'months' => [3,4,5,6],
        'climate' => 'High temperatures (often exceeding 35-40°C), low to moderate humidity.',
        'challenges' => [
            'Heat Stress: Broilers highly susceptible, leading to panting, reduced feed intake, poor growth, lower immunity, and increased mortality.',
            'Dehydration: High temperatures increase water loss from birds.',
            'Ammonia Build-up: If ventilation is compromised, ammonia levels can rise.',
            'Disease Susceptibility: Stressed birds are more prone to infections.'
        ],
        'strategies' => [
            'Housing & Ventilation: Orient sheds East-West, use wet gunny bags/sprinklers on curtains, install fans/foggers, use reflective roof paint, ensure 3-4 ft overhangs.',
            'Stocking Density: Reduce by 10-15% to provide more space and reduce heat load.',
            'Water Management: Constant access to cool, fresh water (intake increases 6% for every 1°C rise between 20-32°C). Supplement with Electrolytes/Vitamin C. Frequent drinker cleaning.',
            'Feed Management: Adjust feeding to cooler parts of day (early morning/late evening). Increase nutrient density, consider fat supplementation.',
            'Litter Management: Maintain dry, loose litter, rake frequently.',
            'Observation: Closely monitor bird behavior (panting, lethargy).'
        ],
        'expected_impact' => 'Higher FCR (due to lower intake), reduced body weight gain, increased mortality if not managed well.',
        'css_class' => 'season-summer',
        'icon_class' => 'fas fa-sun'
    ],
    'Monsoon' => [
        'months' => [7,8,9],
        'climate' => 'High humidity, heavy rainfall, moderate to sometimes fluctuating temperatures.',
        'challenges' => [
            'High Humidity: Leads to wet litter, increased ammonia, and respiratory issues.',
            'Water Contamination: Rainwater runoff can contaminate water sources.',
            'Disease Outbreaks: Ideal conditions for bacterial, fungal, coccidiosis, and parasitic infections.',
            'Feed Spoilage: High humidity can lead to mold and mycotoxin formation.'
        ],
        'strategies' => [
            'Housing & Shelter: Ensure roofs are leak-proof, secure side curtains, ensure excellent drainage around sheds, sufficient roof overhangs (3-4 ft).',
            'Litter Management: Rake daily, add fresh/dry litter, absorbent materials (lime/ammonium sulphate). Aim for 25-30% moisture (over 40% is critical).',
            'Ventilation: Maintain adequate ventilation to remove moisture/gases, prevent ammonia > 25 ppm.',
            'Water Management: Rigorous sanitation (e.g., chlorination), store water in covered tanks.',
            'Feed Management: Store feed on wooden platforms (1ft off ground/away from walls) in dry, well-ventilated area. Avoid large quantities; use toxin binders.',
            'Disease Prevention: Strict biosecurity, robust vaccination, deworming, insect control.'
        ],
        'expected_impact' => 'Increased mortality due to respiratory and gut issues, higher incidence of wet litter diseases, potential for reduced feed quality impacting FCR.',
        'css_class' => 'season-monsoon',
        'icon_class' => 'fas fa-cloud-showers-heavy'
    ],
    'Post-Monsoon' => [
        'months' => [10,11],
        'climate' => 'Transition period with receding rains, gradual decrease in humidity, and pleasant, slowly decreasing temperatures.',
        'challenges' => [
            'Residual Humidity: Initial phase might still have lingering litter issues.',
            'Temperature Fluctuations: Significant day-night differences can stress birds.',
            'Respiratory Challenges: As temperatures drop, respiratory diseases can emerge if ventilation isn\'t managed well.'
        ],
        'strategies' => [
            'Gradual Ventilation Adjustment: Slowly reduce ventilation rates as temperatures drop, but ensure adequate airflow to remove moisture and ammonia.',
            'Litter Management: Continue diligent litter management, ensuring it remains dry and friable.',
            'Disease Surveillance: Remain vigilant for respiratory issues and parasitic problems carried over from monsoon.',
            'Feed Adjustments: Gradually increase energy content in feed in anticipation of winter needs.',
            'Preparation for Winter: Start preparing houses for winter (checking curtains, heating systems).'
        ],
        'expected_impact' => 'Generally better performance than monsoon or summer if managed well, but vigilance needed for transition-related stresses.',
        'css_class' => 'season-post-monsoon',
        'icon_class' => 'fas fa-leaf'
    ],
    'Winter' => [
        'months' => [12,1,2],
        'climate' => 'Low temperatures (can drop significantly), low humidity.',
        'challenges' => [
            'Cold Stress: Especially for young chicks, leading to higher brooding costs, slower growth, and increased susceptibility to disease.',
            'Poor Ventilation: Over-curtaining leads to poor air quality (ammonia, CO2) and wet litter.',
            'Respiratory Diseases: Higher incidence due to poor ventilation and cold.'
        ],
        'strategies' => [
            'Brooding: Pre-heat brooders 24-48 hrs before chicks, maintain optimal temp (32-35°C initially, reducing 2-3°C/week). Use brooder guards, extra litter (6+ inches), have backup heating.',
            'Housing & Insulation: Use thick plastic curtains/gunny bags, false ceilings, East-West orientation for passive heating.',
            'Ventilation: Balance heat retention with air quality (minimum ventilation, strategic opening of curtains, ammonia monitoring).',
            'Feed Management: Increase energy content (e.g., higher fat, higher protein/ME - 23% protein/3400 Kcal/kg ME). Ensure continuous availability.',
            'Water Management: Provide fresh, clean water at comfortable temperature.',
            'Disease Vigilance: Monitor for respiratory diseases, Coccidiosis, Avian influenza (more common in cooler temps).'
        ],
        'expected_impact' => 'Higher feed intake (for warmth), potentially better FCR if cold stress is managed, but risk of respiratory issues and higher brooding costs.',
        'css_class' => 'season-winter',
        'icon_class' => 'fas fa-snowflake'
    ]
];

// --- Initialize variables, use GET defaults if not provided ---
$batch_no = $_GET['batch_no'] ?? '';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('n');
$total_days = (int)($_GET['total_days'] ?? 42);
$weigh_in_interval = (int)($_GET['weigh_in_interval'] ?? 7);
$total_chickens = ''; // will be set from day1 record or retained from GET/POST
$initial_chick_weight = 0.040; // default

// --- Determine current season for display ---
$current_batch_month = (int)$month;
$current_season_data = null;
$current_season_name = 'Unknown';
$season_css_class = '';
$season_icon_class = '';

foreach ($seasonal_data as $season_name => $data) {
    if (in_array($current_batch_month, $data['months'])) {
        $current_season_data = $data;
        $current_season_name = $season_name;
        $season_css_class = $data['css_class'];
        $season_icon_class = $data['icon_class'];
        break;
    }
}

// --- Initialize day_data with defaults (1..$total_days) ---
$day_data = array_fill(1, max(1, $total_days), ['death' => 0, 'alive' => '', 'feed' => 0.00, 'average_weight' => 0.000]);

// --- If batch provided, fetch existing records for that batch ---
if ($batch_no && $year && $month) {
    $stmt = $conn->prepare("SELECT * FROM chicken_data WHERE batch_no=? AND year=? AND month=? ORDER BY day ASC");
    if ($stmt) {
        $stmt->bind_param("sii", $batch_no, $year, $month);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $day = (int)$row['day'];
            if ($day >= 1 && $day <= $total_days) {
                $day_data[$day] = [
                    'death' => (int)$row['death_in_day'],
                    'alive' => (int)$row['alive_count'],
                    'feed' => (float)$row['feed_taken'],
                    'average_weight' => (float)$row['average_weight_kg'],
                ];
                if ($day == 1) {
                    // set total_chickens from day 1 if present
                    $total_chickens = (int)$row['alive_count'] + (int)$row['death_in_day'];
                    if ($row['average_weight_kg'] > 0) {
                        $initial_chick_weight = (float)$row['average_weight_kg'];
                    }
                }
            }
        }
        $stmt->close();
    }
}

// --- Calculate total_deaths_so_far and initial_chickens (server-side) ---
$total_deaths_so_far = 0;
$initial_chickens = null;
if ($batch_no && $year && $month) {
    $stmt_deaths = $conn->prepare("SELECT SUM(death_in_day) AS total_deaths FROM chicken_data WHERE batch_no = ? AND year = ? AND month = ?");
    if ($stmt_deaths) {
        $stmt_deaths->bind_param("sii", $batch_no, $year, $month);
        $stmt_deaths->execute();
        $res = $stmt_deaths->get_result();
        $row = $res->fetch_assoc();
        $total_deaths_so_far = (int)($row['total_deaths'] ?? 0);
        $stmt_deaths->close();
    }

    // Prefer day-1 data for initial flock (if available), else fallback to $total_chickens (from GET/earlier)
    if (!empty($day_data[1]['alive']) || !empty($day_data[1]['death'])) {
        $initial_chickens = (int)$day_data[1]['alive'] + (int)$day_data[1]['death'];
    } else {
        // Try to read from chicken_batches table if you store initial_chickens there (optional)
        $stmt_batchinfo = $conn->prepare("SELECT initial_chickens FROM chicken_batches WHERE batch_no = ? AND year = ? AND month = ? LIMIT 1");
        if ($stmt_batchinfo) {
            $stmt_batchinfo->bind_param("sii", $batch_no, $year, $month);
            $stmt_batchinfo->execute();
            $res2 = $stmt_batchinfo->get_result();
            if ($row2 = $res2->fetch_assoc()) {
                $initial_chickens = (int)$row2['initial_chickens'];
            }
            $stmt_batchinfo->close();
        }
    }

    // fallback to GET/empty value (so the input shows something)
    if ($initial_chickens === null) {
        if (!empty($total_chickens)) {
            $initial_chickens = (int)$total_chickens;
        } else {
            $initial_chickens = 0;
        }
    }
}

// If $total_chickens is still empty (no day1 saved), try GET param
if ($total_chickens === '' && isset($_GET['total_chickens'])) {
    $total_chickens = (int)$_GET['total_chickens'];
}

// --- Handle form submission (saving data) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $batch_no = trim($_POST['batch_no']);
    $year = (int)$_POST['year'];
    $month = (int)$_POST['month'];
    $total_days = (int)$_POST['total_days'];
    $weigh_in_interval = (int)$_POST['weigh_in_interval'];
    $total_chickens_initial = (int)$_POST['total_chickens'];
    $initial_chick_weight_submitted = (float)$_POST['initial_chick_weight'];

    if (empty($batch_no) || $year <= 0 || $month <= 0 || $month > 12 || $total_days <= 30 || $total_chickens_initial < 0) {
        echo "<script>alert('Please fill in all required batch details correctly.'); window.location.href='index.php';</script>";
        exit();
    }

    $status = ($_POST['action'] === 'complete') ? 'complete' : 'incomplete';

    // Update/Insert into chicken_batches (if you have other columns, adjust accordingly).
    // Note: schema varies — this keeps your original columns safe.
    // --- UPDATED BATCH SAVING LOGIC ---
    // We now include 'initial_chickens' so we can calculate mortality rates later.
    $stmt_batch = $conn->prepare("INSERT INTO chicken_batches (batch_no, year, month, status, initial_chickens) 
                                  VALUES (?, ?, ?, ?, ?) 
                                  ON DUPLICATE KEY UPDATE status = ?, initial_chickens = ?");
    
    if ($stmt_batch === false) {
        error_log("Error preparing batch statement: " . $conn->error);
        echo "<script>alert('Error preparing batch statement.');</script>";
        exit();
    }

    // "sissi" means: String, Int, Int, String, Int (for the INSERT part)
    // "si" at the end is for: String, Int (for the UPDATE part)
    $stmt_batch->bind_param("sissisi", 
        $batch_no, 
        $year, 
        $month, 
        $status, 
        $total_chickens_initial, // Save it here
        $status, 
        $total_chickens_initial  // Update it here if it already exists
    );

    $stmt_batch->execute();
    
    if ($stmt_batch->error) {
        error_log("Error executing batch statement: " . $stmt_batch->error);
        echo "<script>alert('Error saving batch status.');</script>";
    }
    $stmt_batch->close();
    // --- END OF UPDATED BLOCK ---

    // Save daily rows (INSERT or UPDATE)
    for ($day = 1; $day <= $total_days; $day++) {
        $death = (int)($_POST['death_' . $day] ?? 0);
        $alive = (int)($_POST['alive_' . $day] ?? 0);
        $feed = (float)($_POST['feed_' . $day] ?? 0.00);
        $average_weight = (float)($_POST['average_weight_' . $day] ?? 0.000);

        // Check if record exists
        $check_stmt = $conn->prepare("SELECT id FROM chicken_data WHERE batch_no=? AND year=? AND month=? AND day=? LIMIT 1");
        if ($check_stmt === false) {
            error_log("Error preparing check statement for day $day: " . $conn->error);
            continue;
        }
        $check_stmt->bind_param("siii", $batch_no, $year, $month, $day);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $record_exists = $check_result->num_rows > 0;
        $check_result->free();
        $check_stmt->close();

        if ($record_exists) {
            $update_stmt = $conn->prepare("UPDATE chicken_data SET death_in_day=?, alive_count=?, feed_taken=?, average_weight_kg=? WHERE batch_no=? AND year=? AND month=? AND day=?");
            if ($update_stmt === false) {
                error_log("Error preparing update statement for day $day: " . $conn->error);
                continue;
            }
            // types: i (death), i (alive), d (feed), d (avg_weight), s (batch_no), i (year), i (month), i (day)
            $update_stmt->bind_param("iiddsiii", $death, $alive, $feed, $average_weight, $batch_no, $year, $month, $day);
            $update_stmt->execute();
            if ($update_stmt->error) {
                error_log("Update error for day $day: " . $update_stmt->error);
            }
            $update_stmt->close();
        } else {
            $insert_stmt = $conn->prepare("INSERT INTO chicken_data (batch_no, year, month, day, death_in_day, alive_count, feed_taken, average_weight_kg) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($insert_stmt === false) {
                error_log("Error preparing insert statement for day $day: " . $conn->error);
                continue;
            }
            // Correct types: s (batch_no), i (year), i (month), i (day), i (death), i (alive), d (feed), d (avg_weight)
            $insert_stmt->bind_param("siiiiidd", $batch_no, $year, $month, $day, $death, $alive, $feed, $average_weight);
            $insert_stmt->execute();
            if ($insert_stmt->error) {
                error_log("Insert error for day $day: " . $insert_stmt->error);
            }
            $insert_stmt->close();
        }
    }

    echo "<script>alert('Data saved as $status!'); window.location.href='index.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chicken Farm Data Entry</title>
    <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src='https://code.jquery.com/jquery-3.5.1.min.js'></script>
    <script src='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js'></script>
    <style>
        /* (kept your styling as-is for brevity) */
        body { font-family: Arial, sans-serif; background-color:#f4f7f6; padding:20px; }
        .container { background-color:#fff; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.1); padding:30px; margin-top:30px; }
        h2 { color:#2c3e50; margin-bottom:25px; font-size:2em; text-align:center; }
        .form-control.rounded { border-radius:0.5rem !important; }
        .form-control[readonly] { background-color:#e9ecef; cursor:not-allowed; }
        input:required { border-left:3px solid #dc3545; }
        input:valid { border-left:3px solid #28a745; }
        input:invalid:not(:focus):not(:placeholder-shown) { border-color:#dc3545; box-shadow:0 0 0 0.2rem rgba(220,53,69,0.25); }

        .card.season-summer { background-color:#fff8e1; border-color:#ffecb3; box-shadow:0 4px 8px rgba(255,204,102,0.2); border-radius:10px; }
        .card.season-monsoon { background-color:#e0f2f7; border-color:#b3e5fc; box-shadow:0 4px 8px rgba(102,204,255,0.2); border-radius:10px; }
        .card.season-post-monsoon { background-color:#f1f8e9; border-color:#c5e1a5; box-shadow:0 4px 8px rgba(153,204,102,0.2); border-radius:10px; }
        .card.season-winter { background-color:#e8f5e9; border-color:#c8e6c9; box-shadow:0 4px 8px rgba(102,178,102,0.2); border-radius:10px; }
        .card-header h5 i { margin-right:8px; font-size:1.2em; }
        .card-header.bg-info { background-color:#17a2b8 !important; }

        .table { border-radius:8px; overflow:hidden; box-shadow:0 2px 5px rgba(0,0,0,0.1); margin-bottom:25px; }
        .table thead th { background-color:#343a40; color:white; border-bottom:none; padding:0.75rem; }
        .table tbody tr:nth-child(even) { background-color:#f2f2f2; }
        .table td { vertical-align:middle; padding:0.5rem; }
        .table-responsive { display:block; width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch; -ms-overflow-style:-ms-autohiding-scrollbar; }

        .btn-lg { padding:12px 30px; font-size:1.15rem; border-radius:8px; box-shadow:0 4px 6px rgba(0,0,0,0.1); transition:all 0.3s ease; }
        .btn-warning { background-color:#ffc107; border-color:#ffc107; color:#212529; }
        .btn-warning:hover { background-color:#e0a800; border-color:#e0a800; box-shadow:0 6px 10px rgba(0,0,0,0.15); }
        .btn-success { background-color:#28a745; border-color:#28a745; }
        .btn-success:hover { background-color:#218838; border-color:#1e7e34; box-shadow:0 6px 10px rgba(0,0,0,0.15); }
    </style>
</head>
<body>
    <div class='container mt-4'>
        <h2 class='text-center mb-4'>Chicken Daily Entry Form</h2>

        <?php if ($current_season_data): ?>
        <div class="card mb-4 <?= htmlspecialchars($season_css_class) ?>">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="<?= htmlspecialchars($season_icon_class) ?>"></i> Current Season: <?= htmlspecialchars($current_season_name) ?> (<?= date('F', mktime(0, 0, 0, $current_batch_month, 1)) ?>)
                </h5>
            </div>
            <div class="card-body">
                <p><strong>Climate:</strong> <?= htmlspecialchars($current_season_data['climate']) ?></p>
                <h6>Key Challenges:</h6>
                <ul>
                    <?php foreach ($current_season_data['challenges'] as $challenge): ?>
                        <li><?= htmlspecialchars($challenge) ?></li>
                    <?php endforeach; ?>
                </ul>
                <h6>Management Strategies:</h6>
                <ul>
                    <?php foreach ($current_season_data['strategies'] as $strategy): ?>
                        <li><?= htmlspecialchars($strategy) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p><strong>Expected Impact on Performance:</strong> <?= htmlspecialchars($current_season_data['expected_impact']) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-row mb-3">
                <div class="col-md-3">
                    <label for="batch_no">Batch No</label>
                    <input type="text" class="form-control rounded" id="batch_no" name="batch_no" placeholder="Enter Batch No" required value="<?= htmlspecialchars($batch_no) ?>">
                </div>
                <div class="col-md-2">
                    <label for="year">Year</label>
                    <input type="number" id="year" name="year" class="form-control rounded" value="<?= htmlspecialchars($year) ?>" required min="2000" max="2099">
                </div>
                <div class="col-md-2">
                    <label for="month">Month</label>
                    <select class="form-control rounded" id="month" name="month" required>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= ($month == $m) ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="total_days_input">Total Days for Batch</label>
                   <input type="number" id="total_days_input" name="total_days" class="form-control rounded" value="<?= htmlspecialchars($total_days) ?>" required min="1" oninput="changeParameters()"> 
                </div>
                <div class="col-md-3">
                    <label for="weigh_in_interval_input">Weigh-in Every (Days)</label>
                    <input type="number" id="weigh_in_interval_input" name="weigh_in_interval" class="form-control rounded" value="<?= htmlspecialchars($weigh_in_interval) ?>" required min="1" oninput="changeParameters()">
                </div>
            </div>
            <div class="form-row mb-3">
                <div class="col-md-4">
                    <label for="total_chickens">Total Chickens (Day 1)</label>
                    <input type="number" id="total_chickens" name="total_chickens" class="form-control rounded" value="<?= htmlspecialchars((int)$total_chickens) ?>" required min="0" oninput="updateAliveCounts(); updateFCR();">
                </div>
                <div class="col-md-4">
                    <label for="initial_chick_weight">Initial Chick Weight (kg)</label>
                    <input type="number" id="initial_chick_weight" name="initial_chick_weight" class="form-control rounded" value="<?= htmlspecialchars(sprintf('%.3f', $initial_chick_weight)) ?>" step="0.001" required min="0" oninput="updateFCR()">
                    <small class="form-text text-muted">Typical day-old chick weight is ~0.040 kg.</small>
                </div>
            </div>

            <div class="form-group row">
                <label for="mortality_rate_display" class="col-sm-5 col-form-label">Mortality Rate:</label>
                <div class="col-sm-7">
                    <div class="input-group">
                        <input type="text" id="mortality_rate_display" class="form-control rounded-left" value="0.00" readonly>
                        <div class="input-group-append">
                            <span class="input-group-text rounded-right">%</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered text-center table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>Day</th>
                            <th>Death in Day</th>
                            <th>Alive Count</th>
                            <th>Feed Consumed (kg)</th>
                            <th>Avg. Weight (kg/bird)</th>
                            <th>FCR (Cumulative)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($day = 1; $day <= $total_days; $day++):
                            $feed = $day_data[$day]['feed'] ?? 0.00;
                            $alive = $day_data[$day]['alive'] ?? '';
                            $death = $day_data[$day]['death'] ?? 0;
                            $average_weight = $day_data[$day]['average_weight'] ?? 0.000;
                            $is_weigh_in_day = ($day == 1 || ($weigh_in_interval > 0 && $day % $weigh_in_interval == 0));
                            $readonly_avg_weight = $is_weigh_in_day ? '' : 'readonly';
                        ?>
                        <tr>
                            <td><?= $day ?></td>
                            <td><input type="number" class="form-control rounded" name="death_<?= $day ?>" value="<?= htmlspecialchars($death) ?>" min="0" oninput="updateAliveCounts(); updateFCR()"></td>
                            <td><input type="number" class="form-control rounded" name="alive_<?= $day ?>" value="<?= htmlspecialchars($alive) ?>" readonly></td>
                            <td><input type="number" class="form-control rounded" name="feed_<?= $day ?>" value="<?= htmlspecialchars(sprintf('%.2f', $feed)) ?>" step="0.01" min="0" oninput="updateFCR()"></td>
                            <td>
                                <input type="number"
                                       class="form-control rounded"
                                       name="average_weight_<?= $day ?>"
                                       value="<?= htmlspecialchars(sprintf('%.3f', $average_weight)) ?>"
                                       step="0.001"
                                       min="0"
                                       <?= $readonly_avg_weight ?>
                                       oninput="updateFCR()">
                            </td>
                            <td id="fcr_<?= $day ?>"></td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <div class="text-center mt-4">
                <button class="btn btn-warning btn-lg rounded" type="submit" name="action" value="incomplete">💾 Save as Incomplete</button>
                <button class="btn btn-success btn-lg rounded ml-3" type="submit" name="action" value="complete">✅ Mark as Complete</button>
            </div>

        </form>
    </div>

    <script>
    // Single robust updateAliveCounts (fixed selectors and IDs)
     function updateAliveCounts() {
    const totalDays = parseInt(document.getElementById('total_days_input').value) || 0;
    const initialChickensInput = document.getElementById('total_chickens');
    const initialChickens = parseFloat(initialChickensInput.value) || 0;
    
    let currentTotalDeaths = 0;

    for (let day = 1; day <= totalDays; day++) {
        const deathInput = document.querySelector(`[name="death_${day}"]`);
        const aliveInput = document.querySelector(`[name="alive_${day}"]`);
        
        if (deathInput && aliveInput) {
            const deathsToday = parseFloat(deathInput.value) || 0;
            currentTotalDeaths += deathsToday;

            if (day === 1) {
                // Day 1 Alive = Initial - Day 1 Deaths
                aliveInput.value = Math.max(0, initialChickens - deathsToday);
            } else {
                // Day X Alive = Previous Day Alive - Day X Deaths
                const prevAlive = parseFloat(document.querySelector(`[name="alive_${day - 1}"]`).value) || 0;
                aliveInput.value = Math.max(0, prevAlive - deathsToday);
            }
        }
    }

    // Update the Mortality Rate Display
    let mortalityRate = 0;
    if (initialChickens > 0) {
        mortalityRate = (currentTotalDeaths / initialChickens) * 100;
    }
    document.getElementById('mortality_rate_display').value = mortalityRate.toFixed(2);
}

    // FCR calculation (keeps your interpolation logic, but uses the alive values just populated)
    function updateFCR() {
        const totalDays = parseInt(document.getElementById('total_days_input').value) || 0;
        const initialFlockCount = parseFloat(document.getElementById('total_chickens').value) || 0;
        const initialChickWeightKg = parseFloat(document.getElementById('initial_chick_weight').value) || 0;
        const weighInInterval = parseInt(document.getElementById('weigh_in_interval_input').value) || 7;

        let cumulativeFeed = 0;
        const initialTotalFlockWeight = initialFlockCount * initialChickWeightKg;

        // collect manually entered weights
        const averageWeights = {};
        for (let day = 1; day <= totalDays; day++) {
            const avgInput = document.querySelector(`[name="average_weight_${day}"]`);
            if (avgInput && !avgInput.readOnly) {
                const val = parseFloat(avgInput.value);
                if (!isNaN(val) && val > 0) averageWeights[day] = val;
                else if (day === 1) averageWeights[day] = initialChickWeightKg;
            }
        }

        if (!averageWeights[1] || averageWeights[1] === 0) {
            averageWeights[1] = initialChickWeightKg;
            const d1 = document.querySelector('[name="average_weight_1"]');
            if (d1) d1.value = initialChickWeightKg.toFixed(3);
        }

        // interpolate missing weights
        let lastKnownWeightDay = 1;
        let lastKnownWeightValue = averageWeights[1] || 0;
        for (let day = 1; day <= totalDays; day++) {
            if (averageWeights[day] === undefined || averageWeights[day] === 0) {
                let nextKnownDay = null, nextKnownValue = null;
                for (let i = day + 1; i <= totalDays; i++) {
                    if (averageWeights[i] !== undefined && averageWeights[i] > 0) {
                        nextKnownDay = i;
                        nextKnownValue = averageWeights[i];
                        break;
                    }
                }
                if (nextKnownDay !== null && lastKnownWeightDay <= nextKnownDay) {
                    const slope = (nextKnownValue - lastKnownWeightValue) / (nextKnownDay - lastKnownWeightDay);
                    averageWeights[day] = lastKnownWeightValue + slope * (day - lastKnownWeightDay);
                } else {
                    averageWeights[day] = lastKnownWeightValue;
                }

                const avgInput = document.querySelector(`[name="average_weight_${day}"]`);
                if (avgInput && avgInput.readOnly) {
                    avgInput.value = averageWeights[day].toFixed(3);
                }
            } else {
                lastKnownWeightDay = day;
                lastKnownWeightValue = averageWeights[day];
            }
        }

        // compute cumulative FCR per day
        let cumulativeFeedSoFar = 0;
        for (let day = 1; day <= totalDays; day++) {
            const feedInput = document.querySelector(`[name="feed_${day}"]`);
            const aliveInput = document.querySelector(`[name="alive_${day}"]`);
            const fcrCell = document.getElementById(`fcr_${day}`);

            const dailyFeed = parseFloat(feedInput ? feedInput.value : 0) || 0;
            const aliveCountToday = parseFloat(aliveInput ? aliveInput.value : 0) || 0;
            const avgWeightToday = averageWeights[day] || 0;

            cumulativeFeedSoFar += dailyFeed;

            let cumulativeWeightGain = 0;
            if (initialFlockCount > 0 && aliveCountToday > 0 && avgWeightToday > 0) {
                const currentTotalFlockWeight = avgWeightToday * aliveCountToday;
                cumulativeWeightGain = currentTotalFlockWeight - initialTotalFlockWeight;
                if (cumulativeWeightGain < 0) cumulativeWeightGain = 0;
            }

            let fcrValue = 'N/A';
            if (cumulativeFeedSoFar > 0 && cumulativeWeightGain > 0) {
                fcrValue = (cumulativeFeedSoFar / cumulativeWeightGain).toFixed(2);
            } else if (cumulativeFeedSoFar > 0 && cumulativeWeightGain === 0) {
                fcrValue = '∞';
            }
            if (fcrCell) fcrCell.innerText = fcrValue;
        }
    }

    function changeParameters() {
        let params = new URLSearchParams(window.location.search);
        const totalDays = document.getElementById('total_days_input').value;
        const weighInInterval = document.getElementById('weigh_in_interval_input').value;
        const batchNo = document.getElementById('batch_no').value;
        const year = document.getElementById('year').value;
        const month = document.getElementById('month').value;

        params.set('total_days', totalDays);
        params.set('weigh_in_interval', weighInInterval);
        if (batchNo) params.set('batch_no', batchNo);
        if (year) params.set('year', year);
        if (month) params.set('month', month);

        // reload with updated params
        window.location.search = params.toString();
    }

    $(document).ready(function() {
        // initial run
        updateAliveCounts();
        updateFCR();
    });
    </script>
    <script>
    // 🔄 Auto-refresh the form when total_days changes
    document.getElementById('total_days_input').addEventListener('change', function() {
        document.querySelector('form').submit();
    });
</script>
</body>
</html> 
