<?php
/**
 * Contact Form Component
 * Reusable contact form with verification for Cadman Manufacturing website
 */

// Session manager still needed for admin/cart compatibility
require_once __DIR__ . '/session_manager.php';

function renderContactForm($formTitle = 'Contact Us', $prefilledMessage = '') {
    // Generate stateless CSRF token using HMAC (no session needed)
    $timestamp = time();
    $secret = defined('CSRF_SECRET') ? CSRF_SECRET : 'cadman-mfg-secret-2026';
    $csrfToken = hash_hmac('sha256', $timestamp, $secret);
    $tokenData = base64_encode($timestamp . '|' . $csrfToken);
    
    // Generate honeypot field name (changes daily so bots can't learn it)
    $honeypotField = 'field_' . substr(hash('sha256', date('Y-m-d') . $secret), 0, 16);
?>
<!-- Contact Form JavaScript Functions -->
<script src="/js/contact-validation.js" defer></script>

<!-- Contact Form -->
<div id="formtable" style="background: rgba(255,255,255,0.95); margin: 40px 20px; padding: 30px; border-radius: 10px; max-width: 600px; margin-left: auto; margin-right: auto;">
    <h2 style="text-align: center; color: #333; margin-bottom: 20px;"><?php echo htmlspecialchars($formTitle); ?></h2>
    
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
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($tokenData, ENT_QUOTES, 'UTF-8'); ?>">
        
        <!-- Form Timestamp (for rate limiting) -->
        <input type="hidden" name="form_timestamp" value="<?php echo $timestamp; ?>">
        
        <!-- Honeypot field (anti-bot) - hidden from real users -->
        <input type="text" name="<?php echo $honeypotField; ?>" value="" style="position: absolute; left: -9999px; width: 1px; height: 1px;" tabindex="-1" autocomplete="off">
        
        <div style="display: grid; gap: 15px;">
            <div>
                <label for="name" style="display: block; font-weight: bold; margin-bottom: 5px; color: #333;">Name: *</label>
                <input onfocus="clearInput(this)" onblur="checkInput(this)" name="name" id="name" type="text" value="name" size="20" maxlength="50" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required aria-describedby="name-help">
                <small id="name-help" style="color: #666; font-size: 12px;">Please enter your full name</small>
            </div>
            
            <div>
                <label for="email" style="display: block; font-weight: bold; margin-bottom: 5px; color: #333;">Email Address: *</label>
                <input onfocus="clearInput(this)" onblur="checkInput(this)" name="email" id="email" type="email" value="email@email.com" size="20" maxlength="50" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required aria-describedby="email-help">
                <small id="email-help" style="color: #666; font-size: 12px;">We'll use this to respond to your message</small>
            </div>
            
            <div>
                <label for="message" style="display: block; font-weight: bold; margin-bottom: 5px; color: #333;">Message: *</label>
                <textarea name="message" id="message" rows="5" cols="30" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;" required aria-describedby="message-help" placeholder="Please describe your inquiry or request..."><?php echo htmlspecialchars($prefilledMessage); ?></textarea>
                <small id="message-help" style="color: #666; font-size: 12px;">Tell us about your jewelry needs or questions</small>
            </div>
            
            <div>
                <label for="verify" style="display: block; font-weight: bold; margin-bottom: 5px; color: #333;">Verification Code: *</label>
                <img src="/create.php?t=<?php echo $timestamp; ?>" alt="Verification Code Image" style="margin-bottom: 10px; border: 1px solid #ccc; display: block;">
                <input onfocus="clearInput(this)" onblur="checkInput(this)" name="verify" id="verify" type="text" value="Code" size="20" maxlength="50" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" required aria-describedby="verify-help">
                <small id="verify-help" style="color: #666; font-size: 12px;">Enter the code shown in the image above</small>
            </div>
            
            <div style="text-align: center; margin-top: 10px;">
                <button type="submit" value="submit" style="background: linear-gradient(145deg, #0066CC, #004499); color: white; border: none; padding: 12px 30px; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.3s ease;" aria-label="Send your message to Cadman Manufacturing">Send Message</button>
            </div>
        </div>
    </form>
</div>

<!-- Contact Form Hover Effects -->
<style>
#formtable form button:hover {
    background: linear-gradient(145deg, #0077DD, #0055AA);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,102,204,0.4);
}
</style>
<?php
}

// If the file is called directly (not included), render the default form
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    renderContactForm();
}
?>
