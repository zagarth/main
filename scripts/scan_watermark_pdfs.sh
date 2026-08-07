#!/bin/bash

echo "=== SCANNING FOR WATERMARK-ONLY PDFs ==="
echo ""

cd /var/www/html/homesite/Cadman_catalog

# Find all PDFs and check them for the watermark pattern
watermark_files=()
good_files=()
total_files=0

for pdf_file in page_*.pdf; do
    if [ -f "$pdf_file" ]; then
        total_files=$((total_files + 1))
        
        # Use our analyzer to check content
        echo "Checking: $pdf_file"
        
        cd /var/www/html/homesite
        python3 pdf_image_analyzer.py "/var/www/html/homesite/Cadman_catalog/$pdf_file" black > /tmp/analysis_output.txt 2>&1
        
        # Check if it detected 0 products and has the error pattern
        if grep -q "Unique Products Detected: 0" /tmp/analysis_output.txt; then
            # Check the analysis JSON for the error pattern
            analysis_file="/var/www/html/homesite/Cadman_catalog/${pdf_file%.*}_analysis.json"
            if [ -f "$analysis_file" ]; then
                if grep -q "ioerror\|OFFENDING COMMAND\|mark" "$analysis_file"; then
                    echo "  ❌ WATERMARK ONLY: $pdf_file"
                    watermark_files+=("$pdf_file")
                else
                    echo "  ⚠️  Empty but no error pattern: $pdf_file"
                fi
            fi
        else
            echo "  ✅ Has content: $pdf_file"
            good_files+=("$pdf_file")
        fi
        
        cd /var/www/html/homesite/Cadman_catalog
    fi
done

echo ""
echo "=== SUMMARY ==="
echo "Total PDF files checked: $total_files"
echo "Files with content: ${#good_files[@]}"
echo "Files with only watermarks: ${#watermark_files[@]}"

if [ ${#watermark_files[@]} -gt 0 ]; then
    echo ""
    echo "=== WATERMARK-ONLY FILES ==="
    for file in "${watermark_files[@]}"; do
        echo "  - $file"
    done
fi