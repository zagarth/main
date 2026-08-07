#!/bin/bash
# Fix Problematic PDFs Script
# For PDFs that fail with Ghostscript PostScript conversion due to complex images/structure
# Uses qpdf optimization instead of PostScript conversion

echo "PROBLEMATIC PDF FIXER"
echo "===================="
echo "For PDFs that fail standard Ghostscript conversion"
echo ""

# Configuration
BACKUP_DIR="problematic_pdf_backups_$(date +%Y%m%d_%H%M%S)"
CATALOG_DIR="Cadman_catalog"
ERROR_LOG="$CATALOG_DIR/problematic_pdf_fixes_$(date +%Y%m%d_%H%M%S).log"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Log function
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S'): $1" | tee -a "$ERROR_LOG"
}

# Function to fix problematic PDF using qpdf
fix_problematic_pdf() {
    local input_file="$1"
    local filename=$(basename "$input_file")
    local temp_file="${input_file}.temp_qpdf"
    
    echo "  Processing: $filename"
    
    # Get original size
    local orig_size=$(stat -c%s "$input_file")
    echo "    Original size: $orig_size bytes"
    
    # Try qpdf optimization (preserves images and structure)
    if qpdf --linearize \
            --optimize-images \
            --object-streams=generate \
            --compress-streams=y \
            --decode-level=specialized \
            "$input_file" \
            "$temp_file" 2>/dev/null; then
        
        echo "    ✅ qpdf optimization completed"
        
        # Check optimized file size
        local new_size=$(stat -c%s "$temp_file")
        echo "    Optimized size: $new_size bytes"
        
        # Calculate size change
        local size_change=$((new_size - orig_size))
        local size_percent=0
        if [ $orig_size -ne 0 ]; then
            size_percent=$((size_change * 100 / orig_size))
        fi
        
        # Validate optimized PDF
        if qpdf --check "$temp_file" 2>/dev/null; then
            echo "    ✅ PDF validation passed"
            
        # Test that content is preserved and check for prices
        local orig_text_lines=$(pdftotext "$input_file" - 2>/dev/null | wc -l)
        local new_text_lines=$(pdftotext "$temp_file" - 2>/dev/null | wc -l)
        local price_count=$(pdftotext "$temp_file" - 2>/dev/null | grep -i price | wc -l)
        
        if [ $new_text_lines -gt 5 ] && [ $new_text_lines -ge $((orig_text_lines / 2)) ]; then
            echo "    ✅ Content verification passed"
            
            if [ $price_count -gt 0 ]; then
                echo "    ⚠️  WARNING: $price_count price references found - manual removal may be needed"
                log_message "WARNING: $filename still contains $price_count price references"
            else
                echo "    ✅ No price references found"
            fi                # Replace original with optimized version
                mv "$temp_file" "$input_file"
                log_message "SUCCESS: $filename optimized - size change: $size_percent%"
                return 0
            else
                echo "    ❌ Content verification failed (text lines: $orig_text_lines → $new_text_lines)"
                rm -f "$temp_file"
                log_message "ERROR: Content lost during optimization of $filename"
                return 1
            fi
        else
            echo "    ❌ Optimized PDF failed validation"
            rm -f "$temp_file"
            log_message "ERROR: PDF validation failed for $filename"
            return 1
        fi
    else
        echo "    ❌ qpdf optimization failed"
        log_message "ERROR: qpdf optimization failed for $filename"
        return 1
    fi
}

# Main processing function
process_problematic_pdf() {
    local pdf_file="$1"
    local filename=$(basename "$pdf_file")
    
    echo ""
    echo "Processing: $filename"
    echo "========================"
    
    # Create backup
    if cp "$pdf_file" "$BACKUP_DIR/"; then
        echo "  ✅ Backup created: $BACKUP_DIR/$filename"
    else
        echo "  ❌ Failed to create backup for $filename"
        return 1
    fi
    
    # Process the PDF
    if fix_problematic_pdf "$pdf_file"; then
        echo "  ✅ $filename processed successfully"
        return 0
    else
        echo "  ❌ $filename processing failed"
        return 1
    fi
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
    
    if process_problematic_pdf "$single_file"; then
        echo "✅ Single file processing complete"
    else
        echo "❌ Single file processing failed"
        exit 1
    fi
else
    # Interactive mode - ask which files to process
    echo "Known problematic files that may need fixing:"
    echo "- page_22a.pdf (Ghostscript image conversion errors)"
    echo "- Any PDFs created with old Acrobat versions"
    echo "- PDFs with complex image structures"
    echo ""
    echo "Enter PDF filename to fix (or 'all' to scan for all problematic files):"
    read -r target_file
    
    if [ "$target_file" = "all" ]; then
        echo "Scanning for potentially problematic PDFs..."
        # This would scan for PDFs and test them - for now just list known issue
        echo "Manual scan not implemented yet. Please specify individual files."
        exit 1
    else
        pdf_path="$CATALOG_DIR/$target_file"
        if [ -f "$pdf_path" ]; then
            if process_problematic_pdf "$pdf_path"; then
                echo "✅ Processing complete"
            else
                echo "❌ Processing failed"
                exit 1
            fi
        else
            echo "❌ File not found: $pdf_path"
            exit 1
        fi
    fi
fi

echo "📁 Backups: $BACKUP_DIR"
echo "📄 Log: $ERROR_LOG"

if [ -f "$ERROR_LOG" ]; then
    echo ""
    echo "Check log for any issues or details"
fi