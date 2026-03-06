<?php
include 'db.php';

$batch = $_GET['batch_no'];
$year = $_GET['year'];
$month = $_GET['month'];

mysqli_query($conn,"DELETE FROM chicken_batches WHERE batch_no='$batch' AND year='$year' AND month='$month'");
mysqli_query($conn,"DELETE FROM chicken_data WHERE batch_no='$batch' AND year='$year' AND month='$month'");

header("Location: index.php");
?>
