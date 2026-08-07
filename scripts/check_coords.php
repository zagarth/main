<?php
$retailers = json_decode(file_get_contents('retailers.json'), true);
$uniqueCoords = [];

foreach($retailers as $retailer) {
    $coord = $retailer['lat'] . ',' . $retailer['lng'];
    if(!isset($uniqueCoords[$coord])) {
        $uniqueCoords[$coord] = 0;
    }
    $uniqueCoords[$coord]++;
}

echo "Unique coordinate combinations:\n";
arsort($uniqueCoords);
$count = 0;
foreach($uniqueCoords as $coord => $retailers_count) {
    echo $coord . ' (' . $retailers_count . ' retailers)' . "\n";
    if(++$count >= 10) break;
}
?>
