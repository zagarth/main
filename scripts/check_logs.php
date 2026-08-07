<?php
/**
 * Simple log file checker for debugging
 */

header('Content-Type: text/plain');

echo "=== CONTACT FORM LOGS ===\n\n";

$logs = [
    '/tmp/cadman_mail.log' => 'Mail Log',
    '/tmp/contact_tracking.log' => 'Tracking Log'
];

foreach ($logs as $file => $name) {
    echo "$name ($file):\n";
    echo str_repeat('-', 60) . "\n";
    
    if (file_exists($file)) {
        echo "File size: " . filesize($file) . " bytes\n";
        echo "Last modified: " . date('Y-m-d H:i:s', filemtime($file)) . "\n\n";
        
        $lines = file($file);
        $recent = array_slice($lines, -20); // Last 20 lines
        
        if (empty($recent)) {
            echo "File is empty\n";
        } else {
            echo "Last 20 lines:\n";
            echo implode('', $recent);
        }
    } else {
        echo "File does not exist\n";
    }
    
    echo "\n" . str_repeat('=', 60) . "\n\n";
}

// Check PHP error log
echo "PHP Error Log:\n";
echo str_repeat('-', 60) . "\n";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    echo "Location: $error_log\n";
    $lines = `tail -10 $error_log 2>&1`;
    echo $lines;
} else {
    echo "No PHP error log configured or found\n";
}
?>
