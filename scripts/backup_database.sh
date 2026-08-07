#!/bin/bash

# Database backup script - creates backups in two locations
# Usage: ./backup_database.sh

# Database credentials
DB_USER="cadman_admin"
DB_PASS="Admin2025!Cadman"
DB_NAME="CadmanClients"

# Timestamp for backup files
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Backup filename
BACKUP_FILE="cadmanclients_backup_${TIMESTAMP}.sql"

# Website backup location
WEBSITE_BACKUP_DIR="/var/www/html/homesite/database_backups"

# Normal backup location (assuming /home/user/backups or similar)
NORMAL_BACKUP_DIR="/home/$(whoami)/database_backups"

# Create backup directories if they don't exist
mkdir -p "$WEBSITE_BACKUP_DIR"
mkdir -p "$NORMAL_BACKUP_DIR"

echo "Creating database backup: $BACKUP_FILE"
echo "Timestamp: $(date)"
echo ""

# Create the database dump
echo "Dumping database $DB_NAME..."
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "/tmp/$BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo "✓ Database dump created successfully"
    
    # Copy to website location
    echo "Copying backup to website directory: $WEBSITE_BACKUP_DIR"
    cp "/tmp/$BACKUP_FILE" "$WEBSITE_BACKUP_DIR/"
    if [ $? -eq 0 ]; then
        echo "✓ Backup saved to website directory"
    else
        echo "✗ Failed to copy backup to website directory"
    fi
    
    # Copy to normal backup location
    echo "Copying backup to normal backup directory: $NORMAL_BACKUP_DIR"
    cp "/tmp/$BACKUP_FILE" "$NORMAL_BACKUP_DIR/"
    if [ $? -eq 0 ]; then
        echo "✓ Backup saved to normal backup directory"
    else
        echo "✗ Failed to copy backup to normal backup directory"
    fi
    
    # Clean up temporary file
    rm "/tmp/$BACKUP_FILE"
    
    # Compress backups to save space
    echo ""
    echo "Compressing backups..."
    gzip "$WEBSITE_BACKUP_DIR/$BACKUP_FILE" 2>/dev/null
    gzip "$NORMAL_BACKUP_DIR/$BACKUP_FILE" 2>/dev/null
    
    echo ""
    echo "=== BACKUP SUMMARY ==="
    echo "Database: $DB_NAME"
    echo "Timestamp: $TIMESTAMP"
    echo "Website backup: $WEBSITE_BACKUP_DIR/${BACKUP_FILE}.gz"
    echo "Normal backup: $NORMAL_BACKUP_DIR/${BACKUP_FILE}.gz"
    
    # Show file sizes
    if [ -f "$WEBSITE_BACKUP_DIR/${BACKUP_FILE}.gz" ]; then
        WEBSITE_SIZE=$(ls -lh "$WEBSITE_BACKUP_DIR/${BACKUP_FILE}.gz" | awk '{print $5}')
        echo "Website backup size: $WEBSITE_SIZE"
    fi
    
    if [ -f "$NORMAL_BACKUP_DIR/${BACKUP_FILE}.gz" ]; then
        NORMAL_SIZE=$(ls -lh "$NORMAL_BACKUP_DIR/${BACKUP_FILE}.gz" | awk '{print $5}')
        echo "Normal backup size: $NORMAL_SIZE"
    fi
    
else
    echo "✗ Database dump failed"
    rm -f "/tmp/$BACKUP_FILE"
    exit 1
fi

echo ""
echo "Backup completed successfully!"