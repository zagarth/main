#!/bin/bash

# Universal Collections Auto-Processor Site-Wide Setup Script
# Configures the complete automated content management system

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Function to print colored output
print_header() {
    echo -e "\n${PURPLE}======================================${NC}"
    echo -e "${PURPLE}  $1${NC}"
    echo -e "${PURPLE}======================================${NC}\n"
}

print_step() {
    echo -e "${BLUE}[STEP]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Site configuration
SITE_DIR="/var/www/html/homesite"
WEB_USER="www-data"

# Collections to set up
COLLECTIONS=(
    "accessories"
    "bands"
    "corp"
    "engagement"
    "family"
    "ladys_stoneset"
    "school"
    "signet"
)

print_header "Universal Collections Auto-Processor Setup"
echo "This script will configure automated content management for all jewelry collections."
echo "It will create directories, set permissions, and install the file watcher service."
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    print_error "This script must be run as root (with sudo)"
    exit 1
fi

print_step "Checking system requirements..."

# Check if GD extension is available
if ! php -m | grep -q "gd"; then
    print_error "PHP GD extension is not installed"
    echo "Please install it with: sudo apt-get install php-gd"
    exit 1
fi
print_success "PHP GD extension found"

# Check if inotify-tools is available
if ! command -v inotifywait &> /dev/null; then
    print_warning "inotify-tools not found, installing..."
    apt-get update && apt-get install -y inotify-tools
    if [ $? -eq 0 ]; then
        print_success "inotify-tools installed"
    else
        print_error "Failed to install inotify-tools"
        exit 1
    fi
else
    print_success "inotify-tools found"
fi

print_step "Creating collection directory structure..."

# Create directories for each collection
for collection in "${COLLECTIONS[@]}"; do
    echo "Setting up $collection collection..."
    
    # Create main directories
    mkdir -p "$SITE_DIR/$collection"
    mkdir -p "$SITE_DIR/$collection/images"
    mkdir -p "$SITE_DIR/$collection/thumbs"
    mkdir -p "$SITE_DIR/_php/$collection"
    
    # Set ownership
    chown -R $WEB_USER:$WEB_USER "$SITE_DIR/$collection"
    chown -R $WEB_USER:$WEB_USER "$SITE_DIR/_php/$collection"
    
    # Set permissions
    chmod -R 755 "$SITE_DIR/$collection"
    chmod -R 755 "$SITE_DIR/_php/$collection"
    
    # Make images directory writable
    chmod 775 "$SITE_DIR/$collection/images"
    chmod 775 "$SITE_DIR/$collection/thumbs"
    
    print_success "$collection collection directory structure created"
done

print_step "Setting up log directory..."

# Create log directory
mkdir -p /var/log
touch /var/log/collections_watcher.log
chown $WEB_USER:$WEB_USER /var/log/collections_watcher.log
chmod 644 /var/log/collections_watcher.log
print_success "Log file created"

print_step "Installing file watcher service..."

# Install the file watcher service
if [ -f "$SITE_DIR/manage_watcher.sh" ]; then
    cd "$SITE_DIR"
    ./manage_watcher.sh install
    print_success "File watcher service installed"
else
    print_error "manage_watcher.sh not found"
    exit 1
fi

print_step "Testing universal processor..."

# Test the universal processor
if [ -f "$SITE_DIR/universal_collection_processor.php" ]; then
    # Test with a simple status check
    response=$(curl -s "http://localhost/homesite/universal_collection_processor.php?action=status" 2>/dev/null)
    if [[ $response == *"accessories"* ]]; then
        print_success "Universal processor is responding"
    else
        print_warning "Universal processor may not be fully functional"
        echo "Response: $response"
    fi
else
    print_error "universal_collection_processor.php not found"
    exit 1
fi

print_step "Setting up web dashboard..."

# Check if dashboard is accessible
if [ -f "$SITE_DIR/universal_collections_dashboard.html" ]; then
    chown $WEB_USER:$WEB_USER "$SITE_DIR/universal_collections_dashboard.html"
    chmod 644 "$SITE_DIR/universal_collections_dashboard.html"
    print_success "Web dashboard configured"
else
    print_error "universal_collections_dashboard.html not found"
fi

print_step "Creating quick access scripts..."

# Create a simple test script
cat > "$SITE_DIR/test_processor.sh" << 'EOF'
#!/bin/bash
echo "Testing Universal Collections Processor..."
echo ""
echo "1. Testing processor status:"
curl -s "http://localhost/homesite/universal_collection_processor.php?action=status" | head -c 200
echo -e "\n"
echo ""
echo "2. Testing collections list:"
curl -s "http://localhost/homesite/universal_collection_processor.php?action=collections"
echo ""
echo ""
echo "3. File watcher status:"
./manage_watcher.sh status
EOF

chmod +x "$SITE_DIR/test_processor.sh"
chown $WEB_USER:$WEB_USER "$SITE_DIR/test_processor.sh"

# Create a quick start script
cat > "$SITE_DIR/quick_start.sh" << 'EOF'
#!/bin/bash
echo "Starting Universal Collections Auto-Processor..."
echo ""
echo "1. Starting file watcher service..."
sudo ./manage_watcher.sh start
echo ""
echo "2. Opening web dashboard..."
echo "Dashboard URL: http://localhost/homesite/universal_collections_dashboard.html"
echo ""
echo "3. Processor API endpoint:"
echo "API URL: http://localhost/homesite/universal_collection_processor.php"
echo ""
echo "System is ready! You can now:"
echo "- Add images to any collection's 'images' folder"
echo "- They will be automatically processed"
echo "- Use the web dashboard to monitor progress"
echo "- Check logs with: sudo ./manage_watcher.sh logs"
EOF

chmod +x "$SITE_DIR/quick_start.sh"
chown $WEB_USER:$WEB_USER "$SITE_DIR/quick_start.sh"

print_header "Setup Complete!"

echo -e "${GREEN}✅ Universal Collections Auto-Processor is now installed site-wide!${NC}"
echo ""
echo "📁 Collection directories created for:"
for collection in "${COLLECTIONS[@]}"; do
    echo "   - $collection"
done
echo ""
echo "🔧 Available commands:"
echo "   sudo ./manage_watcher.sh start    # Start automatic monitoring"
echo "   sudo ./manage_watcher.sh status   # Check service status"
echo "   sudo ./manage_watcher.sh logs     # View live logs"
echo "   ./test_processor.sh               # Test the system"
echo "   ./quick_start.sh                  # Quick start guide"
echo ""
echo "🌐 Web interfaces:"
echo "   Dashboard: http://localhost/homesite/universal_collections_dashboard.html"
echo "   API: http://localhost/homesite/universal_collection_processor.php"
echo ""
echo "📋 To start using the system:"
echo "   1. Run: sudo ./manage_watcher.sh start"
echo "   2. Open the dashboard in your browser"
echo "   3. Add images to any collection's 'images' folder"
echo "   4. Watch them get processed automatically!"
echo ""
echo "🎯 The system will now automatically:"
echo "   • Create thumbnails for new images (240x240)"
echo "   • Generate detail pages with pricing"
echo "   • Update collection displays"
echo "   • Log all activities"
echo ""

print_success "Setup completed successfully!"
