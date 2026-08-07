<?php
require_once 'retailer_manager.php';

$manager = new RetailerManager();

echo "Exporting current XML to JSON...\n";
$retailers = $manager->exportToJSON();

echo "Updating get_retailers.php...\n";
$manager->updateGetRetailersScript();

echo "Current retailer count: " . count($retailers) . "\n";
echo "Process complete!\n";
?>
