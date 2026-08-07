# Unified Login System Implementation Plan
## With Encrypted Database Credentials

---

## Overview
Merge two login systems into one with encrypted credential storage:
- **OLD**: `admin/login.php` + `admin/auth.php` + `.env` (file-based)
- **NEW**: `login.php` + `includes/db_config.php` + MySQL (database-based)
- **TARGET**: Single `admin/login.php` using encrypted database credentials

---

## Phase 1: Install & Configure Database (WITH ENCRYPTION)

### 1.1 Install MariaDB
```bash
sudo apt-get update
sudo apt-get install -y mariadb-server mariadb-client
sudo systemctl start mariadb
sudo systemctl enable mariadb
sudo mysql_secure_installation
```

### 1.2 Create Database & Tables
```bash
cd /var/www/html/homesite/database

# Run SQL scripts in order
sudo mysql < 01_create_database.sql
sudo mysql < 02_create_tables.sql
sudo mysql < 03_create_users.sql
sudo mysql < 05_create_users_tables.sql
```

### 1.3 Import Client Data
```bash
php import_clients.php
```

### 1.4 **SET UP ENCRYPTED CREDENTIALS**

#### Create Configuration File
```bash
# Copy template
cp .env.template .env.development

# Edit with real credentials
nano .env.development
```

Update these values:
```bash
# Admin credentials (generate hash first)
ADMIN_USERNAME=admin
ADMIN_PASSWORD_HASH=$(php -r "echo password_hash('YourPassword', PASSWORD_DEFAULT);")

# Database credentials
DB_HOST=localhost
DB_NAME=CadmanClients
DB_USER=cadman_admin
DB_PASS=Admin2025!Cadman  # Your actual password

# Session security
SESSION_SECRET_KEY=$(openssl rand -base64 32)
```

#### Encrypt the Configuration
```bash
# Move to secure location
sudo mkdir -p /var/www/config
sudo mv .env.development /var/www/config/
sudo chmod 600 /var/www/config/.env.development

# Encrypt it
python3 config_encrypt.py encrypt
# Enter a strong encryption password when prompted

# Remove unencrypted (optional - keep for initial testing)
# sudo rm -rf /var/www/config
```

#### Decrypt for Runtime
```bash
# When application needs to run
python3 config_encrypt.py decrypt
# Enter your encryption password
```

### 1.5 Verify Installation
```bash
# Test database connection
php -r "
require 'includes/db_config_encrypted.php';
try {
    \$pdo = getDBConnection();
    echo 'Database connection successful!\n';
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage() . '\n';
}
"

# Test admin credentials loading
php -r "
require 'includes/config_loader.php';
echo 'Admin Username: ' . (\$_ENV['ADMIN_USERNAME'] ?? 'NOT LOADED') . '\n';
echo 'DB User: ' . (\$_ENV['DB_USER'] ?? 'NOT LOADED') . '\n';
echo 'Config loaded successfully!\n';
"
```

**Checkpoint**: ✅ Database running, encrypted credentials working

---

## Phase 2: Merge Authentication Systems

### 2.1 Update admin/auth.php to Use Encrypted Config

**Current** (lines 45-46):
```php
define('ADMIN_USERNAME', $_ENV['ADMIN_USERNAME'] ?? '');
define('ADMIN_PASSWORD_HASH', $_ENV['ADMIN_PASSWORD_HASH'] ?? '');
```

**Action**: Update to load from encrypted config
```php
// Load encrypted configuration
require_once __DIR__ . '/../includes/config_loader.php';

define('ADMIN_USERNAME', $_ENV['ADMIN_USERNAME'] ?? '');
define('ADMIN_PASSWORD_HASH', $_ENV['ADMIN_PASSWORD_HASH'] ?? '');
```

### 2.2 Add Database User Verification to auth.php

Add after session security setup:
```php
// Load database functions
require_once __DIR__ . '/../includes/db_config_encrypted.php';

/**
 * Verify credentials (admin or database user)
 */
function verifyCredentials($username, $password) {
    // First check if it's the admin user
    if ($username === ADMIN_USERNAME && 
        password_verify($password, ADMIN_PASSWORD_HASH)) {
        return [
            'user_id' => 0,
            'username' => ADMIN_USERNAME,
            'role' => 'admin',
            'client_id' => null
        ];
    }
    
    // Otherwise check database users
    $user = verifyUser($username, $password);
    if ($user) {
        return $user;
    }
    
    return false;
}
```

### 2.3 Update admin/login.php to Support Both User Types

Find the password verification section (around line 200-250) and update:

**Replace**:
```php
if ($username === ADMIN_USERNAME && 
    hash_equals(ADMIN_PASSWORD_HASH, password_verify($password, ADMIN_PASSWORD_HASH))) {
    // Admin login logic
}
```

**With**:
```php
// Verify credentials (admin or business user)
$user = verifyCredentials($username, $password);

if ($user) {
    // Store user info in session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['client_id'] = $user['client_id'] ?? null;
    
    // 2FA check for admin only
    if ($user['role'] === 'admin' && ENABLE_2FA) {
        // Existing 2FA flow
    } else {
        // Direct login for business users
        $_SESSION['authenticated'] = true;
        
        // Redirect based on role
        if ($user['role'] === 'admin') {
            header('Location: dashboard.php');
        } else {
            header('Location: ../user/dashboard.php');
        }
        exit;
    }
}
```

### 2.4 Remove Duplicate /login.php

```bash
# Remove the duplicate root login page
rm /var/www/html/homesite/login.php

# Update any links to point to admin/login.php
```

**Checkpoint**: ✅ Single login page handles both admin and business users

---

## Phase 3: Update Admin Pages

### 3.1 Update All Admin Pages to Use Encrypted Config

Files that need `require_once 'auth.php'` at the top (10 files):
- `admin/dashboard.php`
- `admin/mass_email.php`
- `admin/file_upload.php`
- `admin/file_upload_status.php`
- `admin/gallerycreator.php`
- `admin/item_entry.php`
- `admin/login.php`
- `admin/monitor.php`
- `admin/user_management.php`
- `admin/view_emails.php`

**No changes needed** - they already use `auth.php` which now loads encrypted config!

### 3.2 Add User Management Link to Admin Dashboard

Edit `admin/dashboard.php`, add after line ~50 (in the tools section):

```php
<div class="admin-tool">
    <a href="user_management.php">
        <i class="fas fa-users"></i>
        <h3>User Management</h3>
        <p>Create business user accounts</p>
    </a>
</div>
```

**Checkpoint**: ✅ All admin pages secured and using encrypted credentials

---

## Phase 4: Testing & Validation

### 4.1 Test Admin Login
```bash
# Decrypt config first
python3 config_encrypt.py decrypt

# Navigate to: https://yourdomain.com/admin/login.php
# Enter admin credentials from encrypted .env
# Verify: Redirects to admin/dashboard.php
# Verify: 2FA works (if enabled)
# Verify: Can access all 10 admin pages
```

### 4.2 Test Business User Creation
```bash
# Login as admin
# Navigate to: admin/user_management.php
# Create test business account:
#   - Select client from list
#   - Username: testretailer
#   - Email: test@example.com
#   - Password: Test123!
# Verify: User created successfully
```

### 4.3 Test Business User Login
```bash
# Logout from admin
# Navigate to: https://yourdomain.com/admin/login.php
# Enter business credentials
# Verify: Redirects to user/dashboard.php
# Verify: Can see orders, profile
# Verify: CANNOT access admin pages
```

### 4.4 Test Security
```bash
# 1. Test encrypted credentials
sudo rm -rf /var/www/config  # Remove decrypted config
# Try to login - should fail with "Database credentials not loaded"
python3 config_encrypt.py decrypt  # Decrypt again
# Try to login - should work

# 2. Test session security
# Open browser developer tools
# Check session cookie is httponly, secure, samesite
# Try to access admin page without login - should redirect

# 3. Test role-based access
# Login as business user
# Try to access: admin/dashboard.php directly
# Should redirect or show "Access Denied"
```

### 4.5 Re-encrypt Configuration
```bash
# After testing, re-encrypt for security
python3 config_encrypt.py encrypt
sudo rm -rf /var/www/config

# For production, decrypt only during application runtime
```

**Checkpoint**: ✅ All functionality working with encrypted credentials

---

## Security Architecture

```
┌─────────────────────────────────────────────────────────┐
│  USER ACCESSES: https://domain.com/admin/login.php     │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│  admin/login.php                                        │
│  - CSRF protection                                      │
│  - Rate limiting                                        │
│  - Session security                                     │
│  - Input validation                                     │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│  admin/auth.php                                         │
│  - Loads config_loader.php                             │
│  - Defines ADMIN_USERNAME from $_ENV (encrypted)       │
│  - Defines ADMIN_PASSWORD_HASH from $_ENV (encrypted)  │
│  - Provides verifyCredentials()                        │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│  includes/config_loader.php                             │
│  - Checks /var/www/config/.env.development             │
│  - Loads encrypted credentials into $_ENV              │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│  includes/db_config_encrypted.php                       │
│  - Uses DB_USER from $_ENV (encrypted)                 │
│  - Uses DB_PASS from $_ENV (encrypted)                 │
│  - Creates PDO connection                              │
│  - Provides verifyUser() for database users            │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────┐
│  PASSWORD VERIFIED?                                     │
│                                                         │
│  Admin: hash_equals(ADMIN_PASSWORD_HASH, hash)         │
│  Business: password_verify() against DB hash            │
└────────────┬───────────────────────┬────────────────────┘
             │                       │
        YES  │                       │  NO
             ▼                       ▼
  ┌──────────────────────┐  ┌──────────────────┐
  │  Check Role          │  │  Login Failed    │
  │  - admin: 2FA?       │  │  - Log attempt   │
  │  - business: direct  │  │  - Rate limit    │
  └──────────┬───────────┘  └──────────────────┘
             │
             ▼
  ┌──────────────────────┐
  │  SET SESSION:        │
  │  - user_id          │
  │  - username         │
  │  - role             │
  │  - client_id        │
  │  - authenticated    │
  └──────────┬───────────┘
             │
             ▼
  ┌──────────────────────────────────┐
  │  REDIRECT BY ROLE:               │
  │  admin → admin/dashboard.php     │
  │  business → user/dashboard.php   │
  └──────────────────────────────────┘
```

---

## File Changes Summary

### New Files Created
- ✅ `includes/config_loader.php` - Loads encrypted config into $_ENV
- ✅ `includes/db_config_encrypted.php` - Database functions with encrypted credentials
- ✅ `.env.template` - Template for configuration
- ✅ `ENCRYPTION_SETUP.md` - Encryption documentation
- ✅ `database/01-05.sql` - Database schema scripts (already exist)

### Files to Modify
- 🔄 `admin/auth.php` - Add config_loader.php, add verifyCredentials()
- 🔄 `admin/login.php` - Use verifyCredentials(), add role-based redirect
- 🔄 `admin/dashboard.php` - Add user management link (optional)

### Files to Remove
- ❌ `login.php` (root) - Duplicate, remove
- ❌ `includes/db_config.php` - Replace with db_config_encrypted.php

### Files to Create at Runtime
- 📝 `/var/www/config/.env.development` - Decrypted config (from encrypted)
- 🔒 `/var/www/config_encrypted/.env.development.encrypted` - Encrypted config

---

## Rollback Plan

If something goes wrong:

```bash
# 1. Restore old db_config.php
git checkout includes/db_config.php

# 2. Restore old auth.php
git checkout admin/auth.php

# 3. Restore old login.php
git checkout admin/login.php

# 4. Remove new files
rm includes/config_loader.php
rm includes/db_config_encrypted.php

# 5. Decrypt config for emergency access
python3 config_encrypt.py decrypt
cat /var/www/config/.env.development  # View credentials
```

---

## Next Steps

Ready to proceed? Here's the execution order:

1. **Install MariaDB** (5 minutes)
2. **Create encrypted configuration** (10 minutes)
3. **Run database scripts** (5 minutes)
4. **Update auth.php** (5 minutes)
5. **Update login.php** (10 minutes)
6. **Test everything** (15 minutes)

**Total estimated time**: 50 minutes

Would you like me to proceed with Phase 1: Install MariaDB and set up encrypted credentials?
