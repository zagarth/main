#!/bin/bash

# Ladys Stoneset Collection - File Watcher Script
# This script monitors the Gems and Pearls directories for new images
# and automatically processes them when detected

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WATCH_DIRS=("$SCRIPT_DIR/ladys_stoneset_php/Gems" "$SCRIPT_DIR/ladys_stoneset_php/Pearls")
PROCESSOR_SCRIPT="$SCRIPT_DIR/ladys_stoneset_auto_processor.php"
LOG_FILE="$SCRIPT_DIR/ladys_stoneset_watcher.log"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Function to process new images
process_images() {
    log_message "New images detected! Processing..."
    
    # Run the auto processor
    php "$PROCESSOR_SCRIPT" >> "$LOG_FILE" 2>&1
    
    if [ $? -eq 0 ]; then
        log_message "Processing completed successfully"
    else
        log_message "Processing failed with error code $?"
    fi
}

# Check if inotify-tools is installed
if ! command -v inotifywait &> /dev/null; then
    echo "Error: inotify-tools is required but not installed."
    echo "Please install it with: sudo apt-get install inotify-tools"
    exit 1
fi

# Create directories if they don't exist
for dir in "${WATCH_DIRS[@]}"; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
        log_message "Created directory: $dir"
    fi
done

log_message "Starting file watcher for Ladys Stoneset Collection"
log_message "Monitoring directories: ${WATCH_DIRS[*]}"

# Watch for file events
while true; do
    # Watch for new files, moved files, and file modifications
    inotifywait -e create,move,modify -r "${WATCH_DIRS[@]}" --format '%w%f %e' 2>/dev/null | while read file event; do
        # Check if it's an image file
        if [[ "$file" =~ \.(jpg|jpeg|png|gif|webp)$ ]]; then
            log_message "Detected $event on image file: $file"
            
            # Wait a moment for file operations to complete
            sleep 2
            
            # Process the new images
            process_images
        fi
    done
    
    # If inotifywait exits, wait a bit and restart
    log_message "File watcher stopped, restarting in 5 seconds..."
    sleep 5
done
