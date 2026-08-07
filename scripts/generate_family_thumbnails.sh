#!/bin/bash

echo "=== Family Collection Thumbnail Generator ==="
echo

# Function to generate thumbnails for a category
generate_thumbnails() {
    local category="$1"
    local source_dir="/var/www/html/homesite/family_php/images/$category"
    local thumb_dir="/var/www/html/homesite/family_php/thumbs/images/$category"
    
    echo "Processing $category category..."
    
    # Ensure thumbnail directory exists
    mkdir -p "$thumb_dir"
    
    local generated=0
    local skipped=0
    local errors=0
    
    # Process all image files
    for file in "$source_dir"/*; do
        if [[ -f "$file" ]]; then
            filename=$(basename "$file")
            extension="${filename##*.}"
            
            # Skip non-image files
            case "${extension,,}" in
                jpg|jpeg|png|gif|webp|tif|tiff)
                    ;;
                *)
                    continue
                    ;;
            esac
            
            # Convert .tif to .png for thumbnails
            thumb_filename="$filename"
            if [[ "${extension,,}" == "tif" || "${extension,,}" == "tiff" ]]; then
                thumb_filename="${filename%.*}.png"
            fi
            
            thumb_path="$thumb_dir/$thumb_filename"
            
            # Skip if thumbnail already exists
            if [[ -f "$thumb_path" ]]; then
                echo "  ✓ Thumbnail exists: $thumb_filename"
                ((skipped++))
                continue
            fi
            
            echo -n "  → Generating: $thumb_filename"
            
            # Generate thumbnail
            if convert "$file" -resize 300x300^ -gravity center -extent 300x300 -quality 85 "$thumb_path" 2>/dev/null; then
                size=$(du -h "$thumb_path" | cut -f1)
                echo " ✓ ($size)"
                ((generated++))
            else
                echo " ✗ FAILED"
                ((errors++))
            fi
        fi
    done
    
    echo "  $category Summary: $generated generated, $skipped skipped, $errors errors"
    echo
    
    # Return counts for global summary
    echo "$generated $skipped $errors"
}

# Initialize counters
total_generated=0
total_skipped=0
total_errors=0

# Process each category
for category in Mother Father Daughter; do
    result=$(generate_thumbnails "$category")
    read generated skipped errors <<< "$result"
    
    ((total_generated += generated))
    ((total_skipped += skipped))
    ((total_errors += errors))
done

echo "=== FINAL SUMMARY ==="
echo "Total thumbnails generated: $total_generated"
echo "Total thumbnails skipped (already exist): $total_skipped"
echo "Total errors: $total_errors"

if [[ $total_generated -gt 0 ]]; then
    echo
    echo "✓ Thumbnail generation completed successfully!"
    echo "You can now refresh Family.php to see faster loading images."
elif [[ $total_skipped -gt 0 && $total_errors -eq 0 ]]; then
    echo
    echo "✓ All thumbnails already exist!"
else
    echo
    echo "⚠ Some errors occurred during thumbnail generation."
fi
