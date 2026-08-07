<?php
// Ultra simple test - just check if POST works at all
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h1 style='color: green;'>✅ FORM SUBMISSION WORKS!</h1>";
    echo "<p>POST data received:</p>";
    echo "<pre style='background: #f0f0f0; padding: 10px;'>";
    print_r($_POST);
    echo "</pre>";
    
    if (isset($_POST['test_submit'])) {
        echo "<p style='color: blue;'>✅ Button parameter found!</p>";
    }
    
    echo "<hr>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Basic Form Test</title>
</head>
<body>
    <h1>🔧 Basic Form Submission Test</h1>
    
    <div style="background: #f8f9fa; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
        <h3>Ultra Simple Test Form</h3>
        <form method="POST" action="">
            <p>
                <label>Test Field: <input type="text" name="test_field" value="hello_world"></label>
            </p>
            <p>
                <button type="submit" name="test_submit" value="1">Click to Test</button>
            </p>
        </form>
    </div>
    
    <div style="background: #fff3cd; padding: 15px; margin: 20px 0; border: 1px solid #ffeaa7;">
        <h4>Instructions:</h4>
        <ol>
            <li>Click the "Click to Test" button above</li>
            <li>If you see "✅ FORM SUBMISSION WORKS!" at the top, forms work</li>
            <li>If nothing happens, there's a deeper server/browser issue</li>
        </ol>
    </div>
    
    <p><strong>Current info:</strong></p>
    <ul>
        <li>Request method: <?php echo $_SERVER['REQUEST_METHOD']; ?></li>
        <li>PHP working: ✅ Yes</li>
        <li>Time: <?php echo date('Y-m-d H:i:s'); ?></li>
    </ul>
</body>
</html>