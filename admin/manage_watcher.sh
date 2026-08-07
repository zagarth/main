#!/bin/bash

# Universal Collections File Watcher Management Script
# Controls the file watcher service and provides status information

SERVICE_NAME="collections-watcher"
SERVICE_FILE="/var/www/html/homesite/admin/collections-watcher.service"
SYSTEM_SERVICE_FILE="/etc/systemd/system/collections-watcher.service"
LOG_FILE="/var/log/collections_watcher.log"
PID_FILE="/var/run/collections_watcher.pid"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
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

# Function to check service status
check_status() {
    if systemctl is-active --quiet $SERVICE_NAME; then
        print_success "File watcher service is running"
        echo "Status: $(systemctl is-active $SERVICE_NAME)"
        echo "PID: $(systemctl show --property MainPID --value $SERVICE_NAME)"
        echo "Memory: $(systemctl show --property MemoryCurrent --value $SERVICE_NAME | numfmt --to=iec)"
        echo "Started: $(systemctl show --property ActiveEnterTimestamp --value $SERVICE_NAME)"
        
        # Show recent log entries
        echo ""
        print_status "Recent log entries:"
        tail -n 10 "$LOG_FILE" 2>/dev/null || echo "No log file found"
        
    elif systemctl is-enabled --quiet $SERVICE_NAME; then
        print_warning "Service is installed but not running"
        echo "Status: $(systemctl is-active $SERVICE_NAME)"
    else
        print_error "Service is not installed or enabled"
    fi
}

# Function to install the service
install_service() {
    print_status "Installing file watcher service..."
    
    # Check if running as root
    if [ "$EUID" -ne 0 ]; then
        print_error "Please run with sudo to install the service"
        exit 1
    fi
    
    # Copy service file
    cp "$SERVICE_FILE" "$SYSTEM_SERVICE_FILE"
    
    # Set permissions
    chmod 644 "$SYSTEM_SERVICE_FILE"
    chown root:root "$SYSTEM_SERVICE_FILE"
    
    # Create log file with proper permissions
    touch "$LOG_FILE"
    chown www-data:www-data "$LOG_FILE"
    chmod 644 "$LOG_FILE"
    
    # Reload systemd and enable service
    systemctl daemon-reload
    systemctl enable $SERVICE_NAME
    
    print_success "Service installed and enabled"
    print_status "You can now start it with: sudo ./manage_watcher.sh start"
}

# Function to uninstall the service
uninstall_service() {
    print_status "Uninstalling file watcher service..."
    
    # Check if running as root
    if [ "$EUID" -ne 0 ]; then
        print_error "Please run with sudo to uninstall the service"
        exit 1
    fi
    
    # Stop and disable service
    systemctl stop $SERVICE_NAME 2>/dev/null
    systemctl disable $SERVICE_NAME 2>/dev/null
    
    # Remove service file
    rm -f "$SYSTEM_SERVICE_FILE"
    
    # Reload systemd
    systemctl daemon-reload
    
    print_success "Service uninstalled"
}

# Function to start the service
start_service() {
    if [ "$EUID" -ne 0 ]; then
        print_error "Please run with sudo to start the service"
        exit 1
    fi
    
    print_status "Starting file watcher service..."
    
    if systemctl start $SERVICE_NAME; then
        print_success "Service started successfully"
        sleep 2
        check_status
    else
        print_error "Failed to start service"
        systemctl status $SERVICE_NAME
    fi
}

# Function to stop the service
stop_service() {
    if [ "$EUID" -ne 0 ]; then
        print_error "Please run with sudo to stop the service"
        exit 1
    fi
    
    print_status "Stopping file watcher service..."
    
    if systemctl stop $SERVICE_NAME; then
        print_success "Service stopped successfully"
    else
        print_error "Failed to stop service"
    fi
}

# Function to restart the service
restart_service() {
    if [ "$EUID" -ne 0 ]; then
        print_error "Please run with sudo to restart the service"
        exit 1
    fi
    
    print_status "Restarting file watcher service..."
    
    if systemctl restart $SERVICE_NAME; then
        print_success "Service restarted successfully"
        sleep 2
        check_status
    else
        print_error "Failed to restart service"
        systemctl status $SERVICE_NAME
    fi
}

# Function to show logs
show_logs() {
    if [ -f "$LOG_FILE" ]; then
        echo "=== File Watcher Logs ==="
        tail -f "$LOG_FILE"
    else
        print_error "Log file not found: $LOG_FILE"
    fi
}

# Function to show help
show_help() {
    echo "Universal Collections File Watcher Management"
    echo ""
    echo "Usage: $0 {install|uninstall|start|stop|restart|status|logs|help}"
    echo ""
    echo "Commands:"
    echo "  install     Install the service (requires sudo)"
    echo "  uninstall   Remove the service (requires sudo)"
    echo "  start       Start the service (requires sudo)"
    echo "  stop        Stop the service (requires sudo)"
    echo "  restart     Restart the service (requires sudo)"
    echo "  status      Show service status"
    echo "  logs        Show live logs"
    echo "  help        Show this help message"
    echo ""
    echo "Examples:"
    echo "  sudo $0 install     # Install and enable the service"
    echo "  sudo $0 start       # Start monitoring"
    echo "  $0 status           # Check if running"
    echo "  $0 logs             # View live logs"
}

# Main script logic
case "${1:-help}" in
    install)
        install_service
        ;;
    uninstall)
        uninstall_service
        ;;
    start)
        start_service
        ;;
    stop)
        stop_service
        ;;
    restart)
        restart_service
        ;;
    status)
        check_status
        ;;
    logs)
        show_logs
        ;;
    help)
        show_help
        ;;
    *)
        print_error "Unknown command: $1"
        show_help
        exit 1
        ;;
esac
