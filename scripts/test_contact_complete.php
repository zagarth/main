<?php
// Test the complete contact form flow
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

echo "=== CONTACT FORM TEST ===\n";
echo "Session ID: " . session_id() . "\n\n";

// Include the contact form to test
ob_start();
include 'contact_form.php';
// Actually call the function
renderContactForm();
$form_output = ob_get_contents();
ob_end_clean();

echo "Contact form generated successfully\n";

// Check if captcha was set in session during form rendering
if (isset($_SESSION['captcha'])) {
    echo "Captcha in session after form render: " . $_SESSION['captcha'] . "\n";
} else {
    echo "No captcha in session after form render\n";
}

// Check if the form contains the proper image tag
if (strpos($form_output, 'create.php?' . session_name() . '=' . session_id()) !== false) {
    echo "✓ Form contains correct image URL with session ID\n";
} else {
    echo "✗ Form does not contain correct image URL\n";
}

// Extract the image URL from the form
preg_match('/src="([^"]*create\.php[^"]*)"/', $form_output, $matches);
if (isset($matches[1])) {
    echo "Image URL found: " . $matches[1] . "\n";
} else {
    echo "No image URL found in form\n";
}

echo "\n=== FORM HTML SNIPPET ===\n";
// Show just the captcha portion of the form
if (preg_match('/<div class="form-group">.*?<img[^>]*>.*?<\/div>/s', $form_output, $captcha_section)) {
    echo $captcha_section[0] . "\n";
}
?>
