<?php
/**
 * Simplified Contact Form - No Session Required
 * Uses stateless security: HMAC tokens + honeypot + rate limiting
 */

// Use session manager just for session init (admin/user compatibility)
require_once __DIR__ . '/session_manager.php';

function renderContactForm($formTitle = 'Contact Us', $prefilledMessage = '') {
    // Generate stateless CSRF token using HMAC
    $timestamp = time();
    $secret = defined('CSRF_SECRET') ? CSRF_SECRET : 'your-secret-key-change-this';
    $csrfToken = hash_hmac('sha256', $timestamp, $secret);
    $tokenData = base64_encode($timestamp . '|' . $csrfToken);
    
    // Generate honeypot field name (changes daily so bots can't learn it)
    $honeypotField = 'field_' . substr(hash('sha256', date('Y-m-d') . $secret), 0, 16);
    
    ?>
<div style="max-width: 600px; margin: 0 auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h2 style="color: #0066CC; margin-bottom: 10px;"><?php echo htmlspecialchars($formTitle); ?></h2>
    
    <?php
    // Display success or error messages from URL parameters
    if (isset($_GET['success']) && !empty($_GET['success'])) {
        echo '<div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">';
        echo htmlspecialchars(urldecode($_GET['success']));
        echo '</div>';
    }
    
    if (isset($_GET['error']) && !empty($_GET['error'])) {
        echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb;">';
        echo htmlspecialchars(urldecode($_GET['error']));
        echo '</div>';
    }
    ?>
    
    <form method="post" action="packemail.php">
        <!-- Stateless CSRF Token (timestamp + HMAC) -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($tokenData); ?>">
        
        <!-- Form Timestamp (for rate limiting) -->
        <input type="hidden" name="form_timestamp" value="<?php echo $timestamp; ?>">
        
        <!-- Honeypot field (anti-bot) - hidden from real users -->
        <input type="text" name="<?php echo $honeypotField; ?>" value="" style="position: absolute; left: -9999px; width: 1px; height: 1px;" tabindex="-1" autocomplete="off">
        
        <div style="display: grid; gap: 15px;">
            <div>
                <label for="name" style="display: block; font-weight: bold; margin-bottom: 5px; color: #333;">Name: *</label>
                <input onfocus="clearInput(this)" onblur="checkInput(this)" name="name" id="name" type="text" value="name" size="20" maxlength="50" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
            </div>
            
            <div>
                <label for="email" style="display: block; font-weight: bold; margin-bottom: 5px; color: #333;">Email Address: *</label>
                <input onfocus="clearInput(this)" onblur="checkInput(this)" name="email" id="email" type="email" value="email@email.com" size="20" maxlength="50" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
            </div>
            
            <div>
                <label for="message" style="display: block; font-weight: bold; margin-bottom: 5px; color: #333;">Message: *</label>
                <textarea name="message" id="message" rows="5" cols="30" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;" required placeholder="Please describe your inquiry or request..."><?php echo htmlspecialchars($prefilledMessage); ?></textarea>
            </div>
            
            <div>
                <label for="verify" style="display: block; font-weight: bold; margin-bottom: 5px; color: #333;">Verification Code: *</label>
                <img src="create.php?t=<?php echo $timestamp; ?>" alt="Verification Code Image" style="margin-bottom: 10px; border: 1px solid #ccc; display: block;">
                <input onfocus="clearInput(this)" onblur="checkInput(this)" name="verify" id="verify" type="text" value="Code" size="20" maxlength="50" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required>
                <!-- Store captcha in hidden field with HMAC signature -->
                <input type="hidden" name="captcha_check" id="captcha_check" value="">
            </div>
            
            <div style="text-align: center; margin-top: 10px;">
                <button type="submit" value="submit" style="background: linear-gradient(145deg, #0066CC, #004499); color: white; border: none; padding: 12px 30px; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.3s ease;">Send Message</button>
            </div>
        </div>
    </form>
</div>

<script>
function clearInput(element) {
    if (element.value === element.defaultValue) {
        element.value = '';
        element.style.color = '#000';
    }
}

function checkInput(element) {
    if (element.value === '') {
        element.value = element.defaultValue;
        element.style.color = '#999';
    }
}
</script>
<?php
}

// If the file is called directly, render the default form
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    renderContactForm();
}
?>
