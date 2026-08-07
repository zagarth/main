#!/bin/bash

# Ladys Stoneset Auto-Processing System Setup Script
# This script sets up the automated content management system

echo "Ladys Stoneset Auto-Processing System Setup"
echo "==========================================="
echo ""

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Check PHP GD extension
echo "Checking PHP GD extension..."
if php -m | grep -q "gd"; then
    echo "✓ PHP GD extension is available"
else
    echo "✗ PHP GD extension is NOT available"
    echo "Please install with: sudo apt-get install php-gd"
    exit 1
fi

# Check if directories exist
echo ""
echo "Checking directory structure..."

REQUIRED_DIRS=(
    "$SCRIPT_DIR/ladys_stoneset_php"
    "$SCRIPT_DIR/ladys_stoneset_php/Gems"
    "$SCRIPT_DIR/ladys_stoneset_php/Pearls"
)

for dir in "${REQUIRED_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo "✓ Directory exists: $(basename "$dir")"
    else
        echo "✗ Creating directory: $(basename "$dir")"
        mkdir -p "$dir"
    fi
done

# Create thumbs directories
echo ""
echo "Setting up thumbnail directories..."

THUMB_DIRS=(
    "$SCRIPT_DIR/ladys_stoneset_php/thumbs"
    "$SCRIPT_DIR/ladys_stoneset_php/thumbs/Gems"
    "$SCRIPT_DIR/ladys_stoneset_php/thumbs/Pearls"
)

for dir in "${THUMB_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo "✓ Thumbs directory exists: $(basename "$dir")"
    else
        echo "✗ Creating thumbs directory: $(basename "$dir")"
        mkdir -p "$dir"
        chmod 755 "$dir"
    fi
done

# Set proper permissions
echo ""
echo "Setting directory permissions..."
chmod 755 "$SCRIPT_DIR/ladys_stoneset_php"
chmod 755 "$SCRIPT_DIR/ladys_stoneset_php/Gems"
chmod 755 "$SCRIPT_DIR/ladys_stoneset_php/Pearls"
chmod -R 755 "$SCRIPT_DIR/ladys_stoneset_php/thumbs"

# Make scripts executable
echo ""
echo "Setting script permissions..."
chmod +x "$SCRIPT_DIR/ladys_stoneset_watcher.sh"
chmod 644 "$SCRIPT_DIR/ladys_stoneset_auto_processor.php"
chmod 644 "$SCRIPT_DIR/ladys_stoneset_dashboard.html"

echo "✓ Permissions set"

# Test the auto-processor
echo ""
echo "Testing auto-processor..."
php "$SCRIPT_DIR/ladys_stoneset_auto_processor.php" > /dev/null 2>&1

if [ $? -eq 0 ]; then
    echo "✓ Auto-processor test successful"
else
    echo "✗ Auto-processor test failed"
    exit 1
fi

# Check for inotify-tools (for file watcher)
echo ""
echo "Checking for file watcher dependencies..."
if command -v inotifywait &> /dev/null; then
    echo "✓ inotify-tools is available (file watcher can be used)"
    WATCHER_AVAILABLE=true
else
    echo "⚠ inotify-tools is NOT available"
    echo "  Install with: sudo apt-get install inotify-tools"
    echo "  File watcher will not work without this"
    WATCHER_AVAILABLE=false
fi

# Show current status
echo ""
echo "Checking current collection status..."
GEMS_COUNT=$(find "$SCRIPT_DIR/ladys_stoneset_php/Gems" -name "*.png" -o -name "*.jpg" 2>/dev/null | grep -v "_alt" | wc -l)
PEARLS_COUNT=$(find "$SCRIPT_DIR/ladys_stoneset_php/Pearls" -name "*.png" -o -name "*.jpg" 2>/dev/null | grep -v "_alt" | wc -l)
DETAIL_COUNT=$(find "$SCRIPT_DIR/ladys_stoneset_php" -name "*_detail.php" 2>/dev/null | wc -l)

echo "Current collection:"
echo "  - Gems: $GEMS_COUNT images"
echo "  - Pearls: $PEARLS_COUNT images"
echo "  - Detail pages: $DETAIL_COUNT"

# Installation complete
echo ""
echo "==========================================="
echo "Setup Complete!"
echo "==========================================="
echo ""
echo "Available tools:"
echo ""
echo "1. Auto-Processor (Manual):"
echo "   php ladys_stoneset_auto_processor.php"
echo ""
echo "2. Auto-Processor (Web Interface):"
echo "   http://yoursite.com/ladys_stoneset_auto_processor.php"
echo ""
echo "3. Dashboard (Web Interface):"
echo "   http://yoursite.com/ladys_stoneset_dashboard.html"
echo ""

if [ "$WATCHER_AVAILABLE" = true ]; then
    echo "4. File Watcher (Background):"
    echo "   ./ladys_stoneset_watcher.sh"
    echo "   (Run this to automatically process new images)"
    echo ""
fi

echo "Usage Instructions:"
echo "==================="
echo ""
echo "To add new images:"
echo "1. Copy image files to ladys_stoneset_php/Gems/ or ladys_stoneset_php/Pearls/"
echo "2. Run the auto-processor manually, or use the file watcher"
echo "3. Thumbnails and detail pages will be created automatically"
echo ""
echo "The main collection page (Ladys_Stoneset.php) will automatically"
echo "detect and display new items without any code changes needed."
echo ""
echo "For monitoring and management, use the dashboard at:"
echo "ladys_stoneset_dashboard.html"
