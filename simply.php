<?php
session_start(); // Start a session to store user input

// Initialize the session variable if it doesn't exist
if (!isset($_SESSION['data'])) {
    $_SESSION['data'] = [];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get input values
    $noOfDays = $_POST['noOfDays'] ?? '';
    $noOfChickens = $_POST['noOfChickens'] ?? '';
    $noOfDeaths = $_POST['noOfDeaths'] ?? '';
    $noOfFeedbags = $_POST['noOfFeedbags'] ?? '';
    $index = $_POST['index'] ?? null;

    // Validate input to ensure they are numeric
    if (is_numeric($noOfDays) && is_numeric($noOfChickens) && is_numeric($noOfDeaths) && is_numeric($noOfFeedbags)) {
        // If editing an existing entry
        if ($index !== null) {
            $_SESSION['data'][$index] = [
                $noOfDays,
                $noOfChickens,
                $noOfDeaths,
                $noOfFeedbags,
            ];
        } else {
            // Store the new data in session
            $_SESSION['data'][] = [
                $noOfDays,
                $noOfChickens,
                $noOfDeaths,
                $noOfFeedbags,
            ];
        }
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Function to populate form fields for editing
function getEditData($index) {
    return isset($_SESSION['data'][$index]) ? $_SESSION['data'][$index] : ['', '', '', ''];
}

// Get the index for editing, if provided
$editIndex = $_GET['edit'] ?? null;
$editData = getEditData($editIndex);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>Daily Data Entry</title>
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Daily Data Entry</h2>
    
    <form method="post" class="mb-4">
        <input type="hidden" name="index" value="<?= htmlspecialchars($editIndex) ?>">
        <div class="form-group">
            <label for="noOfDays">No. of Days:</label>
            <input type="number" name="noOfDays" class="form-control" value="<?= htmlspecialchars($editData[0]) ?>" required>
        </div>
        <div class="form-group">
            <label for="noOfChickens">No. of Chickens:</label>
            <input type="number" name="noOfChickens" class="form-control" value="<?= htmlspecialchars($editData[1]) ?>" required>
        </div>
        <div class="form-group">
            <label for="noOfDeaths">No. of Deaths:</label>
            <input type="number" name="noOfDeaths" class="form-control" value="<?= htmlspecialchars($editData[2]) ?>" required>
        </div>
        <div class="form-group">
            <label for="noOfFeedbags">No. of Feedbags:</label>
            <input type="number" name="noOfFeedbags" class="form-control" value="<?= htmlspecialchars($editData[3]) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary"><?= $editIndex !== null ? 'Update' : 'Submit' ?></button>
    </form>

    <h2 class="mb-4">Data Table</h2>
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>No. of Days</th>
            <th>No. of Chickens</th>
            <th>No. of Deaths</th>
            <th>No. of Feedbags</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($_SESSION['data'])): ?>
            <tr>
                <td colspan="5" class="text-center">No data available</td>
            </tr>
        <?php else: ?>
            <?php
            // Loop through session data to create rows
            foreach ($_SESSION['data'] as $index => $row) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row[0]) . '</td>';
                echo '<td>' . htmlspecialchars($row[1]) . '</td>';
                echo '<td>' . htmlspecialchars($row[2]) . '</td>';
                echo '<td>' . htmlspecialchars($row[3]) . '</td>';
                echo '<td><a href="?edit=' . $index . '" class="btn btn-warning btn-sm">Edit</a></td>';
                echo '</tr>';
            }
            ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
