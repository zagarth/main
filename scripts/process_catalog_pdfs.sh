#!/bin/bash
# Comprehensive PDF Processing Script
# 1. Remove price elements from page_*.pdf files
# 2. Test for issues and mobile compatibility 
# 3. Optimize for mobile browsers

echo "CATALOG PDF PROCESSOR"
echo "===================="
echo "Processing: Remove prices → Test for issues → Optimize"
echo ""

# Configuration
BACKUP_DIR="pdf_backups_$(date +%Y%m%d_%H%M%S)"
CATALOG_DIR="Cadman_catalog"
ERROR_LOG="$CATALOG_DIR/processing_errors_$(date +%Y%m%d_%H%M%S).log"
SILENT=true

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Log function for errors only
log_error() {
    echo "$(date '+%Y-%m-%d %H:%M:%S'): ERROR: $1" >> "$ERROR_LOG"
    if [ "$SILENT" != "true" ]; then
        echo "❌ $1"
    fi
}

# Progress function for silent mode
show_progress() {
    if [ "$SILENT" != "true" ]; then
        echo "$1"
    fi
}

# Function to remove prices from PDF
remove_prices() {
    local input_file="$1"
    local temp_ps="temp_$(basename "$input_file" .pdf).ps"
    
    # Convert PDF to PostScript (silent)
    if ! gs -q -dNOPAUSE -dBATCH -sDEVICE=ps2write -sOutputFile="$temp_ps" "$input_file" 2>/dev/null; then
        log_error "Failed to convert $input_file to PostScript"
        return 1
    fi
    
    # Remove price-related text
    sed -i '/Price/d' "$temp_ps" 2>/dev/null
    sed -i '/^(Price)/d' "$temp_ps" 2>/dev/null
    sed -i '/Price.*show/d' "$temp_ps" 2>/dev/null
    sed -i '/Price.*Tj/d' "$temp_ps" 2>/dev/null
    
    # Convert back with optimization (using proven settings from remove_prices_gs.sh)
    if ! gs -q -dNOPAUSE -dBATCH \
       -sDEVICE=pdfwrite \
       -dCompatibilityLevel=1.4 \
       -dDownsampleColorImages=true \
       -dDownsampleGrayImages=true \
       -dDownsampleMonoImages=true \
       -dColorImageResolution=96 \
       -dGrayImageResolution=96 \
       -dMonoImageResolution=300 \
       -dColorImageDownsampleType=/Bicubic \
       -dGrayImageDownsampleType=/Bicubic \
       -dMonoImageDownsampleType=/Bicubic \
       -dAutoFilterColorImages=false \
       -dAutoFilterGrayImages=false \
       -dColorImageFilter=/DCTEncode \
       -dGrayImageFilter=/DCTEncode \
       -dOptimize=true \
       -dPDFSETTINGS=/ebook \
       -sOutputFile="${input_file}.temp" \
       "$temp_ps" 2>/dev/null; then
        log_error "Failed to optimize $input_file"
        rm -f "$temp_ps"
        return 1
    fi
    
    # Clean up
    rm -f "$temp_ps"
    
    if [ -f "${input_file}.temp" ]; then
        mv "${input_file}.temp" "$input_file"
        return 0
    else
        log_error "Failed to create optimized PDF for $input_file"
        return 1
    fi
}

# Function to test PDF for issues
test_pdf() {
    local pdf_file="$1"
    local basename=$(basename "$pdf_file" .pdf)
    
    # Basic validation
    local file_size_kb=$(du -k "$pdf_file" | cut -f1)
    
    # Check for corruption (exclude informational messages)
    local validation_output=$(qpdf --check "$pdf_file" 2>&1)
    if echo "$validation_output" | grep -qi "\berror\b\|corrupt\|damaged\|invalid" && ! echo "$validation_output" | grep -qi "No.*errors found"; then
        log_error "PDF validation failed for $pdf_file: $validation_output"
        return 1
    fi
    
    # Mobile compatibility checks
    if [ $file_size_kb -gt 20480 ]; then  # >20MB
        log_error "Large file size (${file_size_kb}KB) may cause mobile issues: $pdf_file"
    fi
    
    return 0
}

# Function to verify price removal
verify_prices_removed() {
    local pdf_file="$1"
    
    local price_count=$(pdftotext "$pdf_file" - 2>/dev/null | grep -i price | wc -l)
    
    if [ $price_count -eq 0 ]; then
        return 0
    else
        log_error "$price_count price references still found in $pdf_file"
        return 1
    fi
}

# Main processing function
process_pdf() {
    local pdf_file="$1"
    local filename=$(basename "$pdf_file")
    
    # Create backup (silent)
    if ! cp "$pdf_file" "$BACKUP_DIR/" 2>/dev/null; then
        log_error "Failed to create backup for $filename"
        return 1
    fi
    
    # Process the PDF
    if remove_prices "$pdf_file"; then
        if test_pdf "$pdf_file"; then
            if verify_prices_removed "$pdf_file"; then
                return 0
            fi
        fi
    fi
    
    log_error "Processing failed for $filename"
    return 1
}

# Main execution
if [ $# -eq 1 ]; then
    # Single file mode
    single_file="$1"
    echo "Processing single file: $single_file"
    
    if [ ! -f "$single_file" ]; then
        echo "❌ File not found: $single_file"
        exit 1
    fi
    
    if process_pdf "$single_file"; then
        echo "✅ Single file processing complete"
    else
        echo "❌ Single file processing failed"
        exit 1
    fi
else
    # Batch mode - scan for all page PDFs
    echo "Scanning for page_*.pdf files in $CATALOG_DIR..."
    
    # Find all page PDFs (excluding index PDFs, backup directories, and test files)
    page_files=($(find "$CATALOG_DIR" -name "page_*.pdf" -not -path "*/backups/*" -not -name "*backup*" -not -name "*no_prices*" -not -name "*test*" | sort))
    
    if [ ${#page_files[@]} -eq 0 ]; then
        echo "No page_*.pdf files found in $CATALOG_DIR"
        exit 1
    fi
    
    echo "Found ${#page_files[@]} page PDF files to process"
    echo "Running in silent mode - errors will be logged to: $ERROR_LOG"
    echo "Backup directory: $BACKUP_DIR"
    echo ""
    
    successful=0
    failed=0
    total=${#page_files[@]}
    current=0
    
    for pdf_file in "${page_files[@]}"; do
        ((current++))
        printf "\rProcessing file %d/%d..." "$current" "$total"
        
        if process_pdf "$pdf_file"; then
            ((successful++))
        else
            ((failed++))
        fi
    done
    
    echo ""
    echo ""
    echo "BATCH PROCESSING COMPLETE"
    echo "========================"
    echo "✅ Successful: $successful"
    echo "❌ Failed: $failed"
    
    if [ $failed -gt 0 ]; then
        echo "📄 Check error log: $ERROR_LOG"
    else
        echo "🎉 All files processed successfully!"
    fi
fi

echo "📁 Backups: $BACKUP_DIR"

if [ "${failed:-0}" -gt 0 ]; then
    echo ""
    echo "Failed files can be restored from backups if needed"
fi