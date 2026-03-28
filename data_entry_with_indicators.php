<?php
// data_entry_with_indicators.php

echo "Real-time Monitoring Indicators for Chicken Farm\n";

date_default_timezone_set('UTC');
$datetime = date('Y-m-d H:i:s');

// Variables (these would be pulled from your data source in real-time)
$mortalityRate = 0.05; // Example mortality rate as a percentage
$feedConsumption = 150; // Example feed consumption in kg
$highMortalityThreshold = 0.1; // 10%
$highFeedConsumptionThreshold = 120; // kg

// Check for high mortality
if ($mortalityRate > $highMortalityThreshold) {
    echo "Warning: High Mortality Rate detected! Current rate: " . ($mortalityRate * 100) . "%\n";
}

// Check for high feed consumption
if ($feedConsumption > $highFeedConsumptionThreshold) {
    echo "Warning: High Feed Consumption detected! Current consumption: " . $feedConsumption . " kg\n";
}

echo "Current Date and Time: $datetime\n";
?>