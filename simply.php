<?php
session_start(); // Start the session

// Initialize data if not already set
if (!isset($_SESSION['chicken_data'])) {
    $_SESSION['chicken_data'] = array_fill(1, 31, ['chicken_alive' => '', 'feed_taken' => '', 'reason_of_death' => '']);
}

// Handle form submission via POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    for ($row = 1; $row <= 31; $row++) {
        $_SESSION['chicken_data'][$row]['chicken_alive'] = $_POST["chicken_alive_$row"] ?? 0;
        $_SESSION['chicken_data'][$row]['feed_taken'] = htmlspecialchars($_POST["feed_taken_$row"] ?? '', ENT_QUOTES, 'UTF-8');
        $_SESSION['chicken_data'][$row]['reason_of_death'] = htmlspecialchars($_POST["reason_of_death_$row"] ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Handle session reset via POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset'])) {
    session_unset(); // Clear session data
    session_destroy(); // Optionally destroy the session completely
    header("Location: " . $_SERVER['PHP_SELF']); // Redirect to the same page to refresh
    exit;
}

// Start the HTML output
echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>
        <title>Chicken Data Entry</title>
        <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>
        <script src='https://code.jquery.com/jquery-3.5.1.min.js'></script>
      </head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1 class='text-center mt-5'>Chicken Data Entry</h1>";

// Create a table
echo "<form method='post' id='chickenForm' action=''>"; // Start the form
echo "<table class='table table-bordered mt-3'>";

// Table header
echo "<tr>
        <th>Days</th>
        <th>Chicken Alive</th>
        <th>Feed Taken</th>
        <th>Reason of Death</th>
      </tr>";

// Generate the rows with input fields
for ($row = 1; $row <= 31; $row++) {
    $chicken_alive = htmlspecialchars($_SESSION['chicken_data'][$row]['chicken_alive'], ENT_QUOTES, 'UTF-8');
    $feed_taken = htmlspecialchars($_SESSION['chicken_data'][$row]['feed_taken'], ENT_QUOTES, 'UTF-8');
    $reason_of_death = htmlspecialchars($_SESSION['chicken_data'][$row]['reason_of_death'], ENT_QUOTES, 'UTF-8');
    
    echo "<tr>";
    echo "<td>$row</td>";  
    // Updated chicken_alive input field to remove number spinner
    echo "<td><input type='text' class='form-control' name='chicken_alive_$row' value='$chicken_alive' pattern='[0-9]*' title='Only numbers allowed' required></td>";  
    echo "<td><input type='text' class='form-control' name='feed_taken_$row' value='$feed_taken'></td>";  
    echo "<td><input type='text' class='form-control' name='reason_of_death_$row' value='$reason_of_death'></td>";  
    echo "</tr>";
}

echo "</table>";
echo "<div class='text-center'>
        <button type='submit' name='submit' class='btn btn-primary'>Submit</button>
        <button type='button' id='resetButton' class='btn btn-danger'>Reset Data</button>
      </div>";
echo "</form>";
echo "</div>";

echo "<script>
        // Handle form reset with confirmation
        $('#resetButton').on('click', function() {
            if (confirm('Are you sure you want to reset all data? This cannot be undone.')) {
                $('<form method=\"post\"><input type=\"hidden\" name=\"reset\"></form>').appendTo('body').submit();
            }
        });

        // Optional AJAX form submission
        $('#chickenForm').on('submit', function(e) {
            e.preventDefault(); // Prevent the default form submission
            $.ajax({
                url: '', // Form action to the same page
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    alert('Data submitted successfully!');
                    location.reload(); // Optionally reload the page after successful submission
                },
                error: function() {
                    alert('There was an error submitting the form.');
                }
            });
        });
      </script>";

echo "</body>";
echo "</html>";
?>
