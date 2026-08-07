#!/bin/bash
# Enhanced Sterling Lockets processor - handles all product patterns

echo "Processing ALL Sterling Lockets Products"
echo "======================================"

# Clear previous SQL file
> sterling_lockets_complete.sql

# Process each line that contains a product ID and page reference
cat temp_sterling_lockets.txt | while read line; do
    # Skip header lines and navigation
    if [[ "$line" =~ ^(GF|Back|Next|Prev|Index|^$) ]]; then
        continue
    fi
    
    # Extract lines that have a page reference pattern (22, 22A, 22B, 22C)
    if echo "$line" | grep -q "22[ABC]*"; then
        # Extract product ID (everything before the dots)
        product_id=$(echo "$line" | sed 's/\s*\..*//g' | xargs)
        
        # Extract page reference (22, 22A, 22B, or 22C at end of line)
        page_ref=$(echo "$line" | grep -o "22[ABC]*\$")
        
        # Convert to our page reference format
        case $page_ref in
            "22A") page_reference="page_22a" ;;
            "22B") page_reference="page_22b" ;;
            "22C") page_reference="page_22c" ;;
            "22") page_reference="page_22" ;;
            *) continue ;;
        esac
        
        # Skip empty or invalid product IDs
        if [[ -n "$product_id" && ! "$product_id" =~ ^[[:space:]]*$ && ${#product_id} -le 20 ]]; then
            echo "INSERT INTO catalog_products (product_id, product_name, page_reference, category, subcategory, source) VALUES ('$product_id', 'Sterling Locket', '$page_reference', 'lockets', 'sterling', 'index_page_STER-LOCKETS_01.pdf') ON DUPLICATE KEY UPDATE page_reference = VALUES(page_reference);" >> sterling_lockets_complete.sql
        fi
    fi
done

echo "Generated complete SQL file with $(wc -l < sterling_lockets_complete.sql) insert statements"
echo ""
echo "Summary by page:"
echo "Page 22A: $(grep 'page_22a' sterling_lockets_complete.sql | wc -l) products"
echo "Page 22B: $(grep 'page_22b' sterling_lockets_complete.sql | wc -l) products"  
echo "Page 22C: $(grep 'page_22c' sterling_lockets_complete.sql | wc -l) products"
echo "Page 22:  $(grep 'page_22\"' sterling_lockets_complete.sql | wc -l) products"
echo ""
echo "Sample products:"
head -5 sterling_lockets_complete.sql