<?php
$pdo = new PDO('mysql:host=localhost;dbname=CadmanClients;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$users = [
    ['nui', 'nui@cadmanmfg.com', 'simplepass123', 'admin'],
    ['anna', 'anna@cadmanmfg.com', 'simplepass123', 'admin'],
    ['nastia', 'nastia@cadmanmfg.com', 'simplepass123', 'admin'],
];

foreach ($users as [$username, $email, $password, $role]) {
    $check = $pdo->prepare('SELECT user_id FROM users WHERE username = :username OR email = :email');
    $check->execute([':username' => $username, ':email' => $email]);
    if ($check->fetch()) {
        echo "$username already exists\n";
        continue;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (client_id, username, email, password_hash, role, status, created_at) VALUES (NULL, :username, :email, :password_hash, :role, :status, NOW())');
    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password_hash' => $passwordHash,
        ':role' => $role,
        ':status' => 'active',
    ]);

    echo "Created $username\n";
}
