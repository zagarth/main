#!/bin/bash

echo "=== Family Collection Thumbnail Generator ==="
echo

total_generated=0
total_skipped=0
total_errors=0

# Process Mother category
echo "Processing Mother category..."
cd "/var/www/html/homesite/family_php/images/Mother"
for file in *; do
    if [[ -f "$file" ]]; then
        case "${file,,}" in
            *.jpg|*.jpeg|*.png|*.gif|*.webp|*.tif|*.tiff)
                thumb_name="$file"
                # Convert .tif to .png for thumbnails
                if [[ "${file,,}" == *.tif || "${file,,}" == *.tiff ]]; then
                    thumb_name="${file%.*}.png"
                fi
                
                thumb_path="/var/www/html/homesite/family_php/thumbs/images/Mother/$thumb_name"
                
                if [[ -f "$thumb_path" ]]; then
                    echo "  ✓ Thumbnail exists: $thumb_name"
                    ((total_skipped++))
                else
                    echo -n "  → Generating: $thumb_name"
                    if convert "$file" -resize 300x300^ -gravity center -extent 300x300 -quality 85 "$thumb_path" 2>/dev/null; then
                        size=$(du -h "$thumb_path" | cut -f1)
                        echo " ✓ ($size)"
                        ((total_generated++))
                    else
                        echo " ✗ FAILED"
                        ((total_errors++))
                    fi
                fi
                ;;
        esac
    fi
done
echo

# Process Father category
echo "Processing Father category..."
cd "/var/www/html/homesite/family_php/images/Father"
for file in *; do
    if [[ -f "$file" ]]; then
        case "${file,,}" in
            *.jpg|*.jpeg|*.png|*.gif|*.webp|*.tif|*.tiff)
                thumb_name="$file"
                if [[ "${file,,}" == *.tif || "${file,,}" == *.tiff ]]; then
                    thumb_name="${file%.*}.png"
                fi
                
                thumb_path="/var/www/html/homesite/family_php/thumbs/images/Father/$thumb_name"
                
                if [[ -f "$thumb_path" ]]; then
                    echo "  ✓ Thumbnail exists: $thumb_name"
                    ((total_skipped++))
                else
                    echo -n "  → Generating: $thumb_name"
                    if convert "$file" -resize 300x300^ -gravity center -extent 300x300 -quality 85 "$thumb_path" 2>/dev/null; then
                        size=$(du -h "$thumb_path" | cut -f1)
                        echo " ✓ ($size)"
                        ((total_generated++))
                    else
                        echo " ✗ FAILED"
                        ((total_errors++))
                    fi
                fi
                ;;
        esac
    fi
done
echo

# Process Daughter category
echo "Processing Daughter category..."
cd "/var/www/html/homesite/family_php/images/Daughter"
for file in *; do
    if [[ -f "$file" ]]; then
        case "${file,,}" in
            *.jpg|*.jpeg|*.png|*.gif|*.webp|*.tif|*.tiff)
                thumb_name="$file"
                if [[ "${file,,}" == *.tif || "${file,,}" == *.tiff ]]; then
                    thumb_name="${file%.*}.png"
                fi
                
                thumb_path="/var/www/html/homesite/family_php/thumbs/images/Daughter/$thumb_name"
                
                if [[ -f "$thumb_path" ]]; then
                    echo "  ✓ Thumbnail exists: $thumb_name"
                    ((total_skipped++))
                else
                    echo -n "  → Generating: $thumb_name"
                    if convert "$file" -resize 300x300^ -gravity center -extent 300x300 -quality 85 "$thumb_path" 2>/dev/null; then
                        size=$(du -h "$thumb_path" | cut -f1)
                        echo " ✓ ($size)"
                        ((total_generated++))
                    else
                        echo " ✗ FAILED"
                        ((total_errors++))
                    fi
                fi
                ;;
        esac
    fi
done
echo

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
