#!/bin/bash

# Cadman Manufacturing - Encrypted Credentials Setup Helper
# This script helps you create and encrypt your configuration file

set -e  # Exit on error

echo "════════════════════════════════════════════════════════════"
echo "  Cadman Manufacturing - Configuration Setup"
echo "════════════════════════════════════════════════════════════"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if running as root for secure permissions
if [ "$EUID" -ne 0 ]; then 
    echo -e "${YELLOW}⚠️  Not running as root. Some permissions may be limited.${NC}"
    echo ""
fi

# Function to generate random password
generate_password() {
    openssl rand -base64 24 | tr -d '/+=' | cut -c1-20
}

# Function to hash password
hash_password() {
    php -r "echo password_hash('$1', PASSWORD_DEFAULT);"
}

echo -e "${BLUE}Step 1: Generate Admin Credentials${NC}"
echo "──────────────────────────────────────────────────"
echo ""

# Get admin username
read -p "Enter admin username [admin]: " ADMIN_USER
ADMIN_USER=${ADMIN_USER:-admin}

# Get admin password
echo ""
read -sp "Enter admin password (or press ENTER to generate): " ADMIN_PASS
echo ""

if [ -z "$ADMIN_PASS" ]; then
    ADMIN_PASS=$(generate_password)
    echo -e "${GREEN}✓ Generated password: ${ADMIN_PASS}${NC}"
    echo -e "${YELLOW}⚠️  SAVE THIS PASSWORD - you won't see it again!${NC}"
    echo ""
fi

# Hash the admin password
echo "Hashing password..."
ADMIN_HASH=$(hash_password "$ADMIN_PASS")
echo -e "${GREEN}✓ Password hashed${NC}"
echo ""

echo -e "${BLUE}Step 2: Database Credentials${NC}"
echo "──────────────────────────────────────────────────"
echo ""

# Get database credentials
read -p "Database host [localhost]: " DB_HOST
DB_HOST=${DB_HOST:-localhost}

read -p "Database name [CadmanClients]: " DB_NAME
DB_NAME=${DB_NAME:-CadmanClients}

read -p "Database user [cadman_admin]: " DB_USER
DB_USER=${DB_USER:-cadman_admin}

read -sp "Database password (or press ENTER to generate): " DB_PASS
echo ""

if [ -z "$DB_PASS" ]; then
    DB_PASS=$(generate_password)
    echo -e "${GREEN}✓ Generated DB password: ${DB_PASS}${NC}"
    echo -e "${YELLOW}⚠️  SAVE THIS PASSWORD - you'll need it for MySQL setup!${NC}"
    echo ""
fi

echo -e "${BLUE}Step 3: Generate Session Secret${NC}"
echo "──────────────────────────────────────────────────"
echo ""

SESSION_SECRET=$(openssl rand -base64 32)
echo -e "${GREEN}✓ Session secret generated${NC}"
echo ""

echo -e "${BLUE}Step 4: Create Configuration File${NC}"
echo "──────────────────────────────────────────────────"
echo ""

# Create the config file
CONFIG_FILE="/var/www/config/.env.development"

# Create directory if it doesn't exist
mkdir -p /var/www/config

# Write the configuration
cat > "$CONFIG_FILE" << EOF
# Cadman Manufacturing - Configuration
# Generated: $(date)
# DO NOT COMMIT THIS FILE TO GIT

# =====================================================
# ADMIN CREDENTIALS
# =====================================================
ADMIN_USERNAME=$ADMIN_USER
ADMIN_PASSWORD_HASH=$ADMIN_HASH

# =====================================================
# DATABASE CREDENTIALS
# =====================================================
DB_HOST=$DB_HOST
DB_NAME=$DB_NAME
DB_USER=$DB_USER
DB_PASS=$DB_PASS

# =====================================================
# SESSION SECURITY
# =====================================================
SESSION_SECRET_KEY=$SESSION_SECRET

# =====================================================
# ENVIRONMENT
# =====================================================
APP_ENV=production
APP_DEBUG=false
EOF

# Set secure permissions
chmod 600 "$CONFIG_FILE"

echo -e "${GREEN}✓ Configuration file created: $CONFIG_FILE${NC}"
echo ""

echo -e "${BLUE}Step 5: Encrypt Configuration${NC}"
echo "──────────────────────────────────────────────────"
echo ""

echo "You need to encrypt this configuration file for security."
echo ""
read -p "Encrypt now? (y/n) [y]: " ENCRYPT_NOW
ENCRYPT_NOW=${ENCRYPT_NOW:-y}

if [[ "$ENCRYPT_NOW" =~ ^[Yy]$ ]]; then
    cd /var/www/html/homesite
    
    echo ""
    echo "Running encryption tool..."
    echo -e "${YELLOW}You will be prompted for an encryption password.${NC}"
    echo -e "${YELLOW}SAVE THIS PASSWORD - you'll need it to decrypt the config!${NC}"
    echo ""
    
    python3 config_encrypt.py encrypt
    
    echo ""
    echo -e "${GREEN}✓ Configuration encrypted${NC}"
    echo ""
    
    read -p "Remove unencrypted config? (y/n) [n]: " REMOVE_PLAIN
    REMOVE_PLAIN=${REMOVE_PLAIN:-n}
    
    if [[ "$REMOVE_PLAIN" =~ ^[Yy]$ ]]; then
        rm -rf /var/www/config
        echo -e "${GREEN}✓ Unencrypted config removed${NC}"
    else
        echo -e "${YELLOW}⚠️  Unencrypted config still exists at: $CONFIG_FILE${NC}"
        echo "   Remember to delete it manually for security!"
    fi
else
    echo -e "${YELLOW}⚠️  Configuration NOT encrypted${NC}"
    echo "   Run manually: cd /var/www/html/homesite && python3 config_encrypt.py encrypt"
fi

echo ""
echo "════════════════════════════════════════════════════════════"
echo -e "${GREEN}✓ Setup Complete!${NC}"
echo "════════════════════════════════════════════════════════════"
echo ""

echo -e "${BLUE}Credentials Summary:${NC}"
echo "──────────────────────────────────────────────────"
echo "Admin Username: $ADMIN_USER"
echo "Admin Password: $ADMIN_PASS"
echo ""
echo "Database Host: $DB_HOST"
echo "Database Name: $DB_NAME"
echo "Database User: $DB_USER"
echo "Database Password: $DB_PASS"
echo ""

echo -e "${YELLOW}⚠️  IMPORTANT:${NC}"
echo "1. Save these credentials in a secure password manager"
echo "2. Use the DB password to create MySQL user:"
echo ""
echo -e "${BLUE}   sudo mysql${NC}"
echo -e "${BLUE}   CREATE USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';${NC}"
echo -e "${BLUE}   GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';${NC}"
echo -e "${BLUE}   FLUSH PRIVILEGES;${NC}"
echo -e "${BLUE}   EXIT;${NC}"
echo ""
echo "3. To use the application, decrypt the config first:"
echo -e "${BLUE}   cd /var/www/html/homesite${NC}"
echo -e "${BLUE}   python3 config_encrypt.py decrypt${NC}"
echo ""
echo "4. After use, re-encrypt for security:"
echo -e "${BLUE}   python3 config_encrypt.py encrypt${NC}"
echo -e "${BLUE}   sudo rm -rf /var/www/config${NC}"
echo ""

echo -e "${GREEN}Next steps: Follow UNIFIED_LOGIN_IMPLEMENTATION.md for full setup${NC}"
echo ""
