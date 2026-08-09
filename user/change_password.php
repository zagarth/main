<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') === 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$currentPw = trim((string)($input['current_password'] ?? ''));
$newPw     = trim((string)($input['new_password'] ?? ''));

if ($currentPw === '' || $newPw === '') {
    echo json_encode(['success' => false, 'error' => 'All fields are required.']);
    exit;
}

if (strlen($newPw) < 8) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters.']);
    exit;
}

$pdo  = getDBConnection();
$stmt = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = :id AND status = "active" LIMIT 1');
$stmt->execute([':id' => (int)$_SESSION['user_id']]);
$row  = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || !password_verify($currentPw, $row['password_hash'])) {
    echo json_encode(['success' => false, 'error' => 'Current password is incorrect.']);
    exit;
}

$hash = password_hash($newPw, PASSWORD_BCRYPT);
$upd  = $pdo->prepare('UPDATE users SET password_hash = :h, force_password_change = 0 WHERE user_id = :id');
$upd->execute([':h' => $hash, ':id' => (int)$_SESSION['user_id']]);

echo json_encode(['success' => true]);
