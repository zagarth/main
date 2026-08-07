<?php
require_once 'mail_config.php';
require_once 'packemail.php';

$mailer = new SimpleSMTP(MAIL_HOST, MAIL_PORT, MAIL_ENCRYPTION);
$result = $mailer->sendMail(
    MAIL_TO_EMAIL,
    'Test Email from CLI - ' . date('Y-m-d H:i:s'),
    'This is a test email to verify the mail system is working.',
    MAIL_FROM_EMAIL,
    MAIL_FROM_NAME
);

echo 'Success: ' . (isset($result['success']) && $result['success'] ? 'YES' : 'NO') . PHP_EOL;
if (!empty($result['error'])) {
    echo 'Error: ' . $result['error'] . PHP_EOL;
}
if (!empty($result['message'])) {
    echo 'Message: ' . $result['message'] . PHP_EOL;
}
