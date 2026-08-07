<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Form Debug - Cadman Manufacturing</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0066CC;
            border-bottom: 3px solid #0066CC;
            padding-bottom: 10px;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-left: 4px solid #0066CC;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border-color: #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border-color: #dc3545;
        }
        button {
            background: #0066CC;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
        }
        button:hover {
            background: #0055AA;
        }
        pre {
            background: #272822;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        #result {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Contact Form Debugging Tool</h1>
        
        <div class="test-section">
            <h2>Current Configuration</h2>
            <?php
            session_start();
            require_once 'mail_config.php';
            
            echo "<pre>";
            echo "Mail Server: " . MAIL_HOST . ":" . MAIL_PORT . "\n";
            echo "Encryption: " . MAIL_ENCRYPTION . "\n";
            echo "From: " . MAIL_FROM_EMAIL . "\n";
            echo "To: " . MAIL_TO_EMAIL . "\n";
            echo "\nSession Status: " . (session_status() === PHP_SESSION_ACTIVE ? "✓ ACTIVE" : "❌ INACTIVE") . "\n";
            echo "Session ID: " . session_id() . "\n";
            
            if (isset($_SESSION['contact_csrf_token'])) {
                echo "\n✓ CSRF Token exists in session\n";
                echo "Token: " . substr($_SESSION['contact_csrf_token'], 0, 16) . "...\n";
            } else {
                $_SESSION['contact_csrf_token'] = bin2hex(random_bytes(32));
                echo "\n⚠ Generated new CSRF Token\n";
            }
            
            if (isset($_SESSION['contact_source'])) {
                echo "\n✓ Tracking data in session:\n";
                print_r($_SESSION['contact_source']);
            } else {
                echo "\n⚠ No tracking data in session\n";
            }
            echo "</pre>";
            ?>
        </div>
        
        <div class="test-section">
            <h2>Quick Tests</h2>
            <button onclick="testModal()">1. Test Modal Open</button>
            <button onclick="testTracking()">2. Test Tracking</button>
            <button onclick="testFormSubmit()">3. Test Form Submit</button>
            <button onclick="checkLogs()">4. Check Logs</button>
        </div>
        
        <div id="result"></div>
        
        <!-- Include the contact modal -->
        <?php
        include_once 'navigation.php';
        renderNavigation('');
        ?>
    </div>
    
    <script>
    function testModal() {
        const result = document.getElementById('result');
        result.innerHTML = '<div class="test-section"><h3>Testing Modal...</h3></div>';
        
        try {
            if (typeof openContactModal === 'function') {
                openContactModal('Test message prefill');
                result.innerHTML = '<div class="test-section success"><h3>✓ Success</h3><p>Modal function exists and was called. Check if modal opened.</p></div>';
            } else {
                result.innerHTML = '<div class="test-section error"><h3>❌ Error</h3><p>openContactModal function not found!</p></div>';
            }
        } catch (e) {
            result.innerHTML = '<div class="test-section error"><h3>❌ Error</h3><p>' + e.message + '</p></div>';
        }
    }
    
    function testTracking() {
        const result = document.getElementById('result');
        result.innerHTML = '<div class="test-section"><h3>Testing Tracking...</h3></div>';
        
        try {
            if (typeof openContactModalWithTracking === 'function') {
                openContactModalWithTracking('Debug Page', 'Manual Test', 'Testing from debug page');
                result.innerHTML = '<div class="test-section success"><h3>✓ Success</h3><p>Tracking function called. Check browser console for AJAX response.</p></div>';
            } else {
                result.innerHTML = '<div class="test-section error"><h3>❌ Error</h3><p>openContactModalWithTracking function not found!</p></div>';
            }
        } catch (e) {
            result.innerHTML = '<div class="test-section error"><h3>❌ Error</h3><p>' + e.message + '</p></div>';
        }
    }
    
    function testFormSubmit() {
        const result = document.getElementById('result');
        result.innerHTML = '<div class="test-section"><h3>Form Submission Test</h3><p>Fill out the contact form with test data and submit. Check this page for results after submission.</p></div>';
        
        // Prefill the form if modal is open
        setTimeout(() => {
            const nameField = document.getElementById('name');
            const emailField = document.getElementById('email');
            const messageField = document.getElementById('message');
            
            if (nameField && emailField && messageField) {
                if (nameField.value === 'name' || nameField.value === '') nameField.value = 'Debug Test User';
                if (emailField.value === 'email@email.com' || emailField.value === '') emailField.value = 'test@example.com';
                if (messageField.value === '') messageField.value = 'This is a test message from the debug page.';
                
                result.innerHTML = '<div class="test-section success"><h3>✓ Form Prefilled</h3><p>Enter the CAPTCHA code and click Send Message</p></div>';
            }
        }, 500);
        
        openContactModalWithTracking('Debug Page', 'Form Submit Test', 'Test submission from debug page');
    }
    
    function checkLogs() {
        const result = document.getElementById('result');
        result.innerHTML = '<div class="test-section"><h3>Checking Logs...</h3></div>';
        
        fetch('check_logs.php')
            .then(response => response.text())
            .then(data => {
                result.innerHTML = '<div class="test-section"><h3>Log Contents</h3><pre>' + data + '</pre></div>';
            })
            .catch(error => {
                result.innerHTML = '<div class="test-section error"><h3>❌ Error</h3><p>Could not fetch logs: ' + error + '</p></div>';
            });
    }
    
    // Check for success/error messages in URL
    window.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const success = urlParams.get('success');
        const error = urlParams.get('error');
        
        const result = document.getElementById('result');
        
        if (success) {
            result.innerHTML = '<div class="test-section success"><h3>✓ Form Submission Success</h3><p>' + decodeURIComponent(success) + '</p></div>';
        } else if (error) {
            result.innerHTML = '<div class="test-section error"><h3>❌ Form Submission Error</h3><p>' + decodeURIComponent(error) + '</p></div>';
        }
    });
    </script>
</body>
</html>
