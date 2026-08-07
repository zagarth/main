<?php
/**
 * Retail/Business user rollout tool.
 *
 * Modes:
 *   --mode=dry-run (default): audit and preview actions without writes
 *   --mode=apply: execute password updates and missing user creation
 *
 * Options:
 *   --limit=N              Limit processed rows per action group
 *   --only-client-id=N     Process only one client_id (for testing)
 */

require_once __DIR__ . '/../includes/db_config_encrypted.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$opts = getopt('', ['mode::', 'limit::', 'only-client-id::']);
$mode = $opts['mode'] ?? 'dry-run';
$mode = strtolower(trim((string) $mode));
$limit = isset($opts['limit']) ? max(1, (int) $opts['limit']) : null;
$onlyClientId = isset($opts['only-client-id']) ? (int) $opts['only-client-id'] : null;

if (!in_array($mode, ['dry-run', 'apply'], true)) {
    fwrite(STDERR, "Invalid mode. Use --mode=dry-run or --mode=apply\n");
    exit(1);
}

function isValidEmailValue($email) {
    $email = trim((string) ($email ?? ''));
    return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL);
}

function cleanedPostalPrefix($postalCode) {
    $clean = preg_replace('/[^A-Za-z0-9]/', '', (string) ($postalCode ?? ''));
    if (strlen($clean) < 3) {
        return null;
    }

    return substr($clean, 0, 3);
}

function rolloutPasswordFromPostal($postalCode) {
    $prefix = cleanedPostalPrefix($postalCode);
    if ($prefix === null) {
        return null;
    }

    return 'Cadman' . $prefix;
}

function buildUniqueValue($base, array &$usedValues, $suffixPrefix = '_') {
    $candidate = $base;
    $counter = 1;

    while (isset($usedValues[strtolower($candidate)])) {
        $candidate = $base . $suffixPrefix . $counter;
        $counter++;
    }

    $usedValues[strtolower($candidate)] = true;
    return $candidate;
}

function buildUniqueEmail($baseEmail, array &$usedEmails) {
    $email = strtolower($baseEmail);
    if (!isset($usedEmails[$email])) {
        $usedEmails[$email] = true;
        return $email;
    }

    $parts = explode('@', $email, 2);
    $local = $parts[0] ?? 'user';
    $domain = $parts[1] ?? 'placeholder.local';
    $counter = 1;

    do {
        $candidate = $local . '+' . $counter . '@' . $domain;
        $counter++;
    } while (isset($usedEmails[$candidate]));

    $usedEmails[$candidate] = true;
    return $candidate;
}

function printUsage() {
    echo "Retail/Business User Rollout\n";
    echo "Usage:\n";
    echo "  php database/retail_user_rollout.php --mode=dry-run\n";
    echo "  php database/retail_user_rollout.php --mode=apply --limit=25\n";
    echo "  php database/retail_user_rollout.php --mode=apply --only-client-id=123\n\n";
}

if (isset($opts['help'])) {
    printUsage();
    exit(0);
}

echo "=====================================================\n";
echo "Retail/Business User Rollout\n";
echo "Mode: " . strtoupper($mode) . "\n";
echo "Password rule: Cadman + first 3 postal characters\n";
echo "=====================================================\n\n";

$pdo = getAdminConnection();

$scopeFilterSql = '';
$scopeParams = [];
if ($onlyClientId !== null) {
    $scopeFilterSql = ' AND c.client_id = :only_client_id';
    $scopeParams[':only_client_id'] = $onlyClientId;
}

$retailerCounts = $pdo->prepare("\n    SELECT
        COUNT(*) AS total_retailers,
        SUM(CASE WHEN c.status = 'Active' THEN 1 ELSE 0 END) AS active_retailers,
        SUM(CASE WHEN c.email IS NOT NULL AND TRIM(c.email) <> '' THEN 1 ELSE 0 END) AS retailers_with_email,
        SUM(CASE WHEN c.email IS NULL OR TRIM(c.email) = '' THEN 1 ELSE 0 END) AS retailers_without_email
    FROM clients c
    WHERE c.client_type = 'Retailer'" . $scopeFilterSql
);
$retailerCounts->execute($scopeParams);
$retailerSummary = $retailerCounts->fetch();

$existingUsersStmt = $pdo->prepare("\n    SELECT
        u.user_id,
        u.client_id,
        u.username,
        u.email AS user_email,
        u.status AS user_status,
        c.business_name,
        c.email AS client_email,
        c.phone,
        c.postal_code,
        c.client_type,
        c.status AS client_status
    FROM users u
    LEFT JOIN clients c ON c.client_id = u.client_id
    WHERE u.role = 'business'" . $scopeFilterSql . "
    ORDER BY u.user_id
");
$existingUsersStmt->execute($scopeParams);
$existingUsers = $existingUsersStmt->fetchAll();

$missingRetailerUsersStmt = $pdo->prepare("\n    SELECT
                c.client_id,
                c.business_name,
                c.email,
                c.phone,
                c.postal_code,
                c.client_type,
                c.status
        FROM clients c
        LEFT JOIN users u
                ON c.client_id = u.client_id
                AND u.role = 'business'
        WHERE c.client_type = 'Retailer'
            AND u.user_id IS NULL" . $scopeFilterSql . "
        ORDER BY c.client_id
");
$missingRetailerUsersStmt->execute($scopeParams);
$missingRetailerUsers = $missingRetailerUsersStmt->fetchAll();

$allUniqueStmt = $pdo->query("SELECT username, email FROM users");
$allUniqueRows = $allUniqueStmt->fetchAll();
$usedUsernames = [];
$usedEmails = [];
foreach ($allUniqueRows as $row) {
    if (!empty($row['username'])) {
        $usedUsernames[strtolower($row['username'])] = true;
    }
    if (!empty($row['email'])) {
        $usedEmails[strtolower($row['email'])] = true;
    }
}

$existingActions = [];
$missingActions = [];

$summary = [
    'updated' => 0,
    'created' => 0,
    'skipped' => 0,
    'errors' => 0,
    'email_outreach' => 0,
    'manual_outreach' => 0,
    'existing_total' => count($existingUsers),
    'missing_total' => count($missingRetailerUsers),
];

echo "Audit Snapshot:\n";
echo "- Retailers total: " . (int) ($retailerSummary['total_retailers'] ?? 0) . "\n";
echo "- Retailers active: " . (int) ($retailerSummary['active_retailers'] ?? 0) . "\n";
echo "- Retailers with email: " . (int) ($retailerSummary['retailers_with_email'] ?? 0) . "\n";
echo "- Retailers without email: " . (int) ($retailerSummary['retailers_without_email'] ?? 0) . "\n";
echo "- Existing business users in scope: " . $summary['existing_total'] . "\n";
echo "- Retailers missing user account: " . $summary['missing_total'] . "\n\n";

echo "Processing existing business users (password resets)...\n";
$processedExisting = 0;
foreach ($existingUsers as $row) {
    if ($limit !== null && $processedExisting >= $limit) {
        break;
    }
    $processedExisting++;

    $userId = (int) $row['user_id'];
    $clientId = $row['client_id'] !== null ? (int) $row['client_id'] : null;
    $businessName = trim((string) ($row['business_name'] ?? 'Unlinked Business User'));
    $postalCode = $row['postal_code'] ?? '';
    $password = rolloutPasswordFromPostal($postalCode);

    $emailForOutreach = isValidEmailValue($row['user_email'])
        ? strtolower(trim((string) $row['user_email']))
        : (isValidEmailValue($row['client_email']) ? strtolower(trim((string) $row['client_email'])) : null);

    $outreachType = $emailForOutreach ? 'email' : 'manual';
    if ($outreachType === 'email') {
        $summary['email_outreach']++;
    } else {
        $summary['manual_outreach']++;
    }

    if ($password === null) {
        $summary['skipped']++;
        $existingActions[] = [
            'action' => 'SKIP',
            'reason' => 'Missing or short postal code (<3 alnum)',
            'client_id' => $clientId,
            'business_name' => $businessName,
            'user_id' => $userId,
            'username' => $row['username'],
            'outreach' => $outreachType,
            'email' => $emailForOutreach,
        ];
        continue;
    }

    if ($mode === 'apply') {
        try {
            $ok = updateUserPassword($userId, $password);
            if ($ok) {
                $summary['updated']++;
                $existingActions[] = [
                    'action' => 'UPDATE_PASSWORD',
                    'reason' => 'Updated',
                    'client_id' => $clientId,
                    'business_name' => $businessName,
                    'user_id' => $userId,
                    'username' => $row['username'],
                    'outreach' => $outreachType,
                    'email' => $emailForOutreach,
                ];
            } else {
                $summary['errors']++;
                $existingActions[] = [
                    'action' => 'ERROR',
                    'reason' => 'updateUserPassword returned false',
                    'client_id' => $clientId,
                    'business_name' => $businessName,
                    'user_id' => $userId,
                    'username' => $row['username'],
                    'outreach' => $outreachType,
                    'email' => $emailForOutreach,
                ];
            }
        } catch (Throwable $e) {
            $summary['errors']++;
            $existingActions[] = [
                'action' => 'ERROR',
                'reason' => $e->getMessage(),
                'client_id' => $clientId,
                'business_name' => $businessName,
                'user_id' => $userId,
                'username' => $row['username'],
                'outreach' => $outreachType,
                'email' => $emailForOutreach,
            ];
        }
    } else {
        $existingActions[] = [
            'action' => 'UPDATE_PASSWORD',
            'reason' => 'Dry-run preview',
            'client_id' => $clientId,
            'business_name' => $businessName,
            'user_id' => $userId,
            'username' => $row['username'],
            'outreach' => $outreachType,
            'email' => $emailForOutreach,
        ];
    }
}

echo "Processing retailers missing business user account...\n";
$processedMissing = 0;
foreach ($missingRetailerUsers as $row) {
    if ($limit !== null && $processedMissing >= $limit) {
        break;
    }
    $processedMissing++;

    $clientId = (int) $row['client_id'];
    $businessName = trim((string) ($row['business_name'] ?? 'Unknown Retailer'));
    $clientEmail = trim((string) ($row['email'] ?? ''));
    $phone = trim((string) ($row['phone'] ?? ''));
    $password = rolloutPasswordFromPostal($row['postal_code'] ?? '');

    $baseUsername = null;
    if (isValidEmailValue($clientEmail)) {
        $baseUsername = strtolower($clientEmail);
    } else {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($digits) >= 7) {
            $baseUsername = 'phone' . substr($digits, -7);
        } else {
            $baseUsername = 'client' . $clientId;
        }
    }
    $username = buildUniqueValue($baseUsername, $usedUsernames);

    $baseEmail = isValidEmailValue($clientEmail)
        ? strtolower($clientEmail)
        : 'client' . $clientId . '@placeholder.local';
    $userEmail = buildUniqueEmail($baseEmail, $usedEmails);

    $outreachType = isValidEmailValue($clientEmail) ? 'email' : 'manual';
    if ($outreachType === 'email') {
        $summary['email_outreach']++;
    } else {
        $summary['manual_outreach']++;
    }

    if ($password === null) {
        $summary['skipped']++;
        $missingActions[] = [
            'action' => 'SKIP',
            'reason' => 'Missing or short postal code (<3 alnum)',
            'client_id' => $clientId,
            'business_name' => $businessName,
            'username' => $username,
            'email' => $userEmail,
            'outreach' => $outreachType,
        ];
        continue;
    }

    if ($mode === 'apply') {
        try {
            $ok = createUser($clientId, $username, $userEmail, $password, 'business');
            if ($ok) {
                $summary['created']++;
                $missingActions[] = [
                    'action' => 'CREATE_USER',
                    'reason' => 'Created',
                    'client_id' => $clientId,
                    'business_name' => $businessName,
                    'username' => $username,
                    'email' => $userEmail,
                    'outreach' => $outreachType,
                ];
            } else {
                $summary['errors']++;
                $missingActions[] = [
                    'action' => 'ERROR',
                    'reason' => 'createUser returned false',
                    'client_id' => $clientId,
                    'business_name' => $businessName,
                    'username' => $username,
                    'email' => $userEmail,
                    'outreach' => $outreachType,
                ];
            }
        } catch (Throwable $e) {
            $summary['errors']++;
            $missingActions[] = [
                'action' => 'ERROR',
                'reason' => $e->getMessage(),
                'client_id' => $clientId,
                'business_name' => $businessName,
                'username' => $username,
                'email' => $userEmail,
                'outreach' => $outreachType,
            ];
        }
    } else {
        $missingActions[] = [
            'action' => 'CREATE_USER',
            'reason' => 'Dry-run preview',
            'client_id' => $clientId,
            'business_name' => $businessName,
            'username' => $username,
            'email' => $userEmail,
            'outreach' => $outreachType,
        ];
    }
}

echo "\nAction Summary:\n";
echo "- Updated passwords: " . $summary['updated'] . "\n";
echo "- Created users: " . $summary['created'] . "\n";
echo "- Skipped: " . $summary['skipped'] . "\n";
echo "- Errors: " . $summary['errors'] . "\n";
echo "- Outreach by email: " . $summary['email_outreach'] . "\n";
echo "- Outreach manual (no valid email): " . $summary['manual_outreach'] . "\n\n";

echo "Existing User Actions (first 15):\n";
$existingPreview = array_slice($existingActions, 0, 15);
foreach ($existingPreview as $item) {
    echo sprintf(
        "  [%s] user_id=%s client_id=%s user=%s outreach=%s reason=%s\n",
        $item['action'],
        $item['user_id'] ?? '-',
        $item['client_id'] ?? '-',
        $item['username'] ?? '-',
        $item['outreach'] ?? '-',
        $item['reason'] ?? '-'
    );
}

echo "\nMissing Retailer Actions (first 15):\n";
$missingPreview = array_slice($missingActions, 0, 15);
foreach ($missingPreview as $item) {
    echo sprintf(
        "  [%s] client_id=%s user=%s email=%s outreach=%s reason=%s\n",
        $item['action'],
        $item['client_id'] ?? '-',
        $item['username'] ?? '-',
        $item['email'] ?? '-',
        $item['outreach'] ?? '-',
        $item['reason'] ?? '-'
    );
}

echo "\nPost-Run Verification Snapshot:\n";
$postUsers = $pdo->query("SELECT COUNT(*) AS total_business_users FROM users WHERE role = 'business'")->fetch();
$postUsersWithEmail = $pdo->query("\n    SELECT
        SUM(CASE WHEN email IS NOT NULL AND TRIM(email) <> '' THEN 1 ELSE 0 END) AS with_email,
        SUM(CASE WHEN email IS NULL OR TRIM(email) = '' THEN 1 ELSE 0 END) AS without_email
    FROM users
    WHERE role = 'business'
")->fetch();

echo "- Total business users now: " . (int) ($postUsers['total_business_users'] ?? 0) . "\n";
echo "- Business users with email: " . (int) ($postUsersWithEmail['with_email'] ?? 0) . "\n";
echo "- Business users without email: " . (int) ($postUsersWithEmail['without_email'] ?? 0) . "\n";

if ($mode === 'dry-run') {
    echo "\nDry-run complete. No changes were written.\n";
} else {
    echo "\nApply run complete. Changes were written where actions succeeded.\n";
}
