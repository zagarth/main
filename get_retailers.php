<?php
/**
 * Get Retailers API Endpoint
 * Returns retailer data in JSON format for the location search
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$retailersFile = "retailers.json";

if (!file_exists($retailersFile)) {
    http_response_code(404);
    echo json_encode(["error" => "Retailers data not found"]);
    exit;
}

$retailers = json_decode(file_get_contents($retailersFile), true);

if ($retailers === null) {
    http_response_code(500);
    echo json_encode(["error" => "Error reading retailers data"]);
    exit;
}

// Filter by query parameters if provided
$filtered = $retailers;

if (isset($_GET["state"]) && !empty($_GET["state"])) {
    $filtered = array_filter($filtered, function($retailer) {
        return strtolower($retailer["state"]) === strtolower($_GET["state"]);
    });
}

if (isset($_GET["city"]) && !empty($_GET["city"])) {
    $filtered = array_filter($filtered, function($retailer) {
        return stripos($retailer["city"], $_GET["city"]) !== false;
    });
}

if (isset($_GET["specialty"]) && !empty($_GET["specialty"])) {
    $filtered = array_filter($filtered, function($retailer) {
        return in_array($_GET["specialty"], $retailer["specialties"]);
    });
}

echo json_encode(array_values($filtered));
?>
