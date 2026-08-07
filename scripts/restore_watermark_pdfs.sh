#!/bin/bash

echo "=== FIXING WATERMARK-ONLY PDFs ==="
echo ""

cd /var/www/html/homesite/Cadman_catalog

# Known watermark-only files based on our analysis
watermark_files=(
    "page_07b.pdf"
    "page_08a.pdf"
    "page_09b.pdf"
    "page_10d.pdf"
)

echo "Checking and restoring watermark-only files..."

for pdf_file in "${watermark_files[@]}"; do
    echo ""
    echo "Processing: $pdf_file"
    
    # Check if backup exists
    backup_file="backups/${pdf_file%.*}_backup_20251201_154643.pdf"
    
    if [ ! -f "$backup_file" ]; then
        # Try alternative backup naming
        backup_file="backups/${pdf_file%.*}_backup_20251201_154642.pdf"
    fi
    
    if [ -f "$backup_file" ]; then
        echo "  ✅ Found backup: $backup_file"
        
        # Create a safety backup of current corrupted file
        mv "$pdf_file" "${pdf_file}.corrupted_$(date +%Y%m%d_%H%M%S)"
        echo "  📦 Moved corrupted file to: ${pdf_file}.corrupted_$(date +%Y%m%d_%H%M%S)"
        
        # Restore from backup
        cp "$backup_file" "$pdf_file"
        echo "  🔄 Restored from backup"
        
        # Test the restored file
        cd /var/www/html/homesite
        echo "  🔍 Testing restored file..."
        python3 pdf_image_analyzer.py "/var/www/html/homesite/Cadman_catalog/$pdf_file" black > /tmp/test_output.txt 2>&1
        
        if grep -q "Unique Products Detected: 0" /tmp/test_output.txt; then
            echo "  ⚠️  Restored file still shows 0 products - may need different approach"
        else
            products_detected=$(grep "Unique Products Detected:" /tmp/test_output.txt | awk '{print $4}')
            echo "  ✅ Restored file shows $products_detected products detected"
        fi
        
        cd /var/www/html/homesite/Cadman_catalog
    else
        echo "  ❌ No backup found for $pdf_file"
    fi
done

echo ""
echo "=== RESTORATION COMPLETE ==="