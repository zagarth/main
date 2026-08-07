# Database Credential Encryption Setup

## Overview
This system uses encrypted configuration files to store sensitive credentials like database passwords, admin credentials, and API keys. The encryption is handled by `/var/www/html/homesite/config_encrypt.py`.

## How It Works

### 1. **Encryption System**
- **Encrypted Storage**: `/var/www/config_encrypted/` (encrypted files)
- **Decrypted Runtime**: `/var/www/config/` (decrypted for use)
- **Encryption Tool**: `config_encrypt.py` using Fernet (symmetric encryption)
- **Key Derivation**: PBKDF2 with 100,000 iterations

### 2. **Configuration Loading**
```
┌─────────────────────────────────────────┐
│  PHP Application Starts                 │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│  includes/config_loader.php             │
│  - Checks /var/www/config/.env.development│
│  - Falls back to local .env             │
│  - Loads into $_ENV                     │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│  includes/db_config_encrypted.php       │
│  - Uses DB_USER from $_ENV              │
│  - Uses DB_PASS from $_ENV              │
│  - Creates encrypted PDO connection     │
└─────────────────────────────────────────┘
```

## Setup Instructions

### Step 1: Create Your Configuration File

1. Copy the template:
```bash
cd /var/www/html/homesite
cp .env.template .env.development
```

2. Edit with your credentials:
```bash
nano .env.development
```

3. Generate admin password hash:
```bash
php -r "echo password_hash('YourAdminPassword', PASSWORD_DEFAULT) . PHP_EOL;"
```

4. Update these critical values:
   - `ADMIN_USERNAME` - Your admin username
   - `ADMIN_PASSWORD_HASH` - Hash from step 3
   - `DB_USER` - Database username (cadman_admin)
   - `DB_PASS` - Database password
   - `SESSION_SECRET_KEY` - Random string for sessions

### Step 2: Decrypt Existing Config (if needed)

If you already have an encrypted config, decrypt it first:

```bash
cd /var/www/html/homesite
python3 config_encrypt.py decrypt
# Enter your encryption password when prompted
```

This will create `/var/www/config/.env.development` from the encrypted version.

### Step 3: Move Config to Encryption Directory

```bash
# Create the config directory if it doesn't exist
sudo mkdir -p /var/www/config

# Move your .env.development file
sudo mv .env.development /var/www/config/

# Set proper permissions
sudo chown root:root /var/www/config/.env.development
sudo chmod 600 /var/www/config/.env.development
```

### Step 4: Encrypt the Configuration

```bash
cd /var/www/html/homesite
python3 config_encrypt.py encrypt
# Enter a strong encryption password when prompted
# REMEMBER THIS PASSWORD - you'll need it to decrypt!
```

This creates `/var/www/config_encrypted/.env.development.encrypted`

### Step 5: Secure the System

```bash
# Remove the unencrypted config
sudo rm -rf /var/www/config

# Verify only encrypted version exists
ls -la /var/www/config_encrypted/
```

### Step 6: Runtime Decryption

When your application needs to run, decrypt the config:

```bash
cd /var/www/html/homesite
python3 config_encrypt.py decrypt
# Enter your encryption password
```

**IMPORTANT**: The decrypted `/var/www/config/` directory should exist **only while the application is running**. For maximum security, encrypt it again when done:

```bash
python3 config_encrypt.py encrypt
sudo rm -rf /var/www/config
```

## File Structure

```
/var/www/
├── config/                          # Runtime decrypted config (temporary)
│   └── .env.development            # Decrypted credentials (600 permissions)
│
├── config_encrypted/               # Permanent encrypted storage
│   ├── .env.development.encrypted  # Encrypted credentials
│   └── encryption_metadata.json    # Encryption metadata (salt, file list)
│
└── html/homesite/
    ├── config_encrypt.py           # Encryption/decryption tool
    ├── .env.template              # Template for new configs
    ├── includes/
    │   ├── config_loader.php      # Loads config into $_ENV
    │   └── db_config_encrypted.php # Database functions
    └── admin/
        └── auth.php               # Uses $_ENV['ADMIN_PASSWORD_HASH']
```

## Security Best Practices

### 1. **Encryption Password**
- Use a strong, unique password (20+ characters)
- Store it in a password manager
- Never commit it to git
- Consider using a hardware security key

### 2. **File Permissions**
```bash
# Encrypted config (readable by all, but encrypted)
chmod 644 /var/www/config_encrypted/.env.development.encrypted

# Decrypted config (root only)
chmod 600 /var/www/config/.env.development
chown root:root /var/www/config/.env.development

# Encryption script
chmod 700 /var/www/html/homesite/config_encrypt.py
chown root:root /var/www/html/homesite/config_encrypt.py
```

### 3. **Runtime Security**
- Decrypt config only when needed
- Re-encrypt immediately after use if possible
- Monitor `/var/www/config/` for unauthorized access
- Use systemd service to auto-decrypt on boot (advanced)

### 4. **Backup Strategy**
```bash
# Backup encrypted config
sudo cp -r /var/www/config_encrypted /backup/config_encrypted_$(date +%Y%m%d)

# NEVER backup decrypted config to shared storage
```

## Usage in Code

### Loading Database Config
```php
<?php
// This automatically loads encrypted credentials
require_once __DIR__ . '/includes/db_config_encrypted.php';

// Use database functions
$user = verifyUser($username, $password);
$orders = getClientOrders($client_id);
?>
```

### Loading Admin Config
```php
<?php
// This automatically loads encrypted credentials
require_once __DIR__ . '/includes/config_loader.php';

// Access credentials
$admin_username = $_ENV['ADMIN_USERNAME'];
$admin_hash = $_ENV['ADMIN_PASSWORD_HASH'];
?>
```

## Troubleshooting

### "Database credentials not loaded"
1. Check if config is decrypted: `ls /var/www/config/`
2. Decrypt if needed: `python3 config_encrypt.py decrypt`
3. Verify file exists: `cat /var/www/config/.env.development`
4. Check permissions: `ls -la /var/www/config/`

### "No configuration file found"
1. Verify encrypted config exists: `ls /var/www/config_encrypted/`
2. Decrypt it: `python3 config_encrypt.py decrypt`
3. If missing, create from template: `cp .env.template /var/www/config/.env.development`

### "Decryption failed"
1. Verify you're using the correct password
2. Check encryption metadata: `cat /var/www/config_encrypted/encryption_metadata.json`
3. Ensure encrypted file isn't corrupted

## Migration from Plain .env

If you currently have credentials in plain files:

```bash
# 1. Collect all credentials into one file
cat admin/auth.php | grep ADMIN_PASSWORD_HASH  # Get admin hash
cat includes/db_config.php | grep DB_PASS      # Get DB password

# 2. Create .env.development from template
cp .env.template /var/www/config/.env.development

# 3. Edit and add your credentials
nano /var/www/config/.env.development

# 4. Encrypt it
python3 config_encrypt.py encrypt

# 5. Remove old plain credentials
# (Update files to use config_loader.php instead)

# 6. Test decryption
python3 config_encrypt.py decrypt
cat /var/www/config/.env.development  # Verify credentials
```

## Automated Startup (Optional)

Create a systemd service to decrypt on boot:

```bash
sudo nano /etc/systemd/system/cadman-config-decrypt.service
```

```ini
[Unit]
Description=Decrypt Cadman Configuration
Before=nginx.service mariadb.service

[Service]
Type=oneshot
ExecStart=/usr/bin/python3 /var/www/html/homesite/config_encrypt.py decrypt --password-file /root/.cadman_decrypt_key
User=root
RemainAfterExit=yes

[Install]
WantedBy=multi-user.target
```

Then store password securely:
```bash
echo "YourEncryptionPassword" | sudo tee /root/.cadman_decrypt_key
sudo chmod 400 /root/.cadman_decrypt_key
sudo systemctl enable cadman-config-decrypt.service
```

**WARNING**: This reduces security as the password is stored on disk. Only use in controlled environments.
