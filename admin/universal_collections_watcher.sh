#!/bin/bash

# Universal File Watcher for All Jewelry Collections
# Monitors all collection directories for new images and processes them automatically

# Set the root directory for collections
SITE_DIR="/var/www/html/homesite"
PROCESSOR_URL="http://localhost/homesite/admin/universal_collection_processor.php"
LOG_FILE="/var/log/collections_watcher.log"
PID_FILE="/var/run/collections_watcher.pid"

# Collection directories to monitor
COLLECTIONS=(
    "accessories/images"
    "bands/images"
    "corp/images"
    "engagement/images"
    "family/images"
    "ladys_stoneset/images"
    "school/images"
    "signet/images"
)

# Check if script is already running
if [ -f "$PID_FILE" ]; then
    if kill -0 $(cat "$PID_FILE") 2>/dev/null; then
        echo "File watcher is already running with PID $(cat $PID_FILE)"
        exit 1
    else
        rm -f "$PID_FILE"
    fi
fi

# Save PID
echo $$ > "$PID_FILE"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Function to cleanup on exit
cleanup() {
    log_message "File watcher shutting down..."
    rm -f "$PID_FILE"
    exit 0
}

# Set up signal handlers
trap cleanup SIGTERM SIGINT

# Function to process a new image
process_new_image() {
    local file_path="$1"
    local collection="$2"
    
    log_message "New image detected: $file_path in $collection collection"
    
    # Wait a moment for file to be fully written
    sleep 2
    
    # Check if file still exists and is not empty
    if [ ! -f "$file_path" ] || [ ! -s "$file_path" ]; then
        log_message "File no longer exists or is empty: $file_path"
        return 1
    fi
    
    # Get file info
    filename=$(basename "$file_path")
    filesize=$(stat -c%s "$file_path")
    
    log_message "Processing: $filename (${filesize} bytes) for $collection"
    
    # Call the universal processor via HTTP
    local response
    response=$(curl -s -X POST \
        -F "action=process_single" \
        -F "collection=$collection" \
        -F "image_path=$file_path" \
        "$PROCESSOR_URL" 2>&1)
    
    if [ $? -eq 0 ]; then
        # Parse JSON response
        local success
        success=$(echo "$response" | grep -o '"success":[^,}]*' | cut -d':' -f2 | tr -d ' ')
        
        if [ "$success" = "true" ]; then
            log_message "Successfully processed $filename"
            
            # Extract details from response
            local thumbs_created
            local detail_created
            thumbs_created=$(echo "$response" | grep -o '"thumbnails_created":[^,}]*' | cut -d':' -f2 | tr -d ' ')
            detail_created=$(echo "$response" | grep -o '"detail_pages_created":[^,}]*' | cut -d':' -f2 | tr -d ' ')
            
            if [ "$thumbs_created" = "1" ]; then
                log_message "  - Thumbnail created"
            fi
            
            if [ "$detail_created" = "1" ]; then
                log_message "  - Detail page created"
            fi
            
        else
            local error_msg
            error_msg=$(echo "$response" | grep -o '"error":"[^"]*"' | cut -d'"' -f4)
            log_message "Error processing $filename: $error_msg"
        fi
    else
        log_message "Failed to call processor for $filename: $response"
    fi
}

# Function to check if a file is an image
is_image_file() {
    local file="$1"
    local ext="${file##*.}"
    
    case "${ext,,}" in
        jpg|jpeg|png|gif|bmp|webp)
            return 0
            ;;
        *)
            return 1
            ;;
    esac
}

# Function to get collection name from path
get_collection_from_path() {
    local path="$1"
    
    for collection in "${COLLECTIONS[@]}"; do
        if [[ "$path" == *"$collection"* ]]; then
            echo "${collection%/images}"
            return 0
        fi
    done
    
    echo "unknown"
}

# Check if inotify-tools is installed
if ! command -v inotifywait &> /dev/null; then
    log_message "Error: inotify-tools is not installed. Please install it first:"
    log_message "  sudo apt-get install inotify-tools"
    exit 1
fi

# Start monitoring
log_message "Starting Universal Collections File Watcher..."
log_message "Monitoring directories:"

# Build the directory list for inotifywait
WATCH_DIRS=""
for collection in "${COLLECTIONS[@]}"; do
    dir="$SITE_DIR/$collection"
    if [ -d "$dir" ]; then
        WATCH_DIRS="$WATCH_DIRS $dir"
        log_message "  - $dir"
    else
        log_message "  - $dir (NOT FOUND - will be created if needed)"
        mkdir -p "$dir"
        WATCH_DIRS="$WATCH_DIRS $dir"
    fi
done

log_message "File watcher started successfully (PID: $$)"
log_message "Processor URL: $PROCESSOR_URL"

# Monitor for file events
inotifywait -m -r --format '%w%f %e' \
    -e close_write,moved_to \
    $WATCH_DIRS 2>/dev/null | \
while read file event; do
    # Check if it's an image file
    if is_image_file "$file"; then
        # Skip if it's a thumbnail
        if [[ "$file" == *"_thumb."* ]] || [[ "$file" == *"/thumbs/"* ]]; then
            continue
        fi
        
        # Get collection name
        collection=$(get_collection_from_path "$file")
        
        if [ "$collection" != "unknown" ]; then
            # Process the new image
            process_new_image "$file" "$collection"
        else
            log_message "Unknown collection for file: $file"
        fi
    fi
done
