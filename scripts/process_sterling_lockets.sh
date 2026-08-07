#!/bin/bash
# Process Sterling Lockets index and add all products to database

echo "Processing Sterling Lockets Index Products"
echo "========================================"

# Extract product mappings and create SQL
cat temp_sterling_lockets.txt | grep -E "^[A-Z0-9].*\.\s*(22[ABC]|22)$" | while read line; do
    # Parse product ID and page reference
    product_id=$(echo "$line" | sed 's/\s*\..*//g' | xargs)
    page_ref=$(echo "$line" | grep -o "22[ABC]*$" | head -1)
    
    # Convert page reference to our format
    case $page_ref in
        "22A") page_reference="page_22a" ;;
        "22B") page_reference="page_22b" ;;
        "22C") page_reference="page_22c" ;;
        "22") page_reference="page_22" ;;
        *) page_reference="page_22" ;;
    esac
    
    # Skip empty product IDs
    if [ -n "$product_id" ] && [ -n "$page_reference" ]; then
        echo "INSERT INTO catalog_products (product_id, product_name, page_reference, category, subcategory, source) VALUES ('$product_id', 'Sterling Locket', '$page_reference', 'lockets', 'sterling', 'index_page_STER-LOCKETS_01.pdf') ON DUPLICATE KEY UPDATE page_reference = VALUES(page_reference);"
    fi
done > sterling_lockets_inserts.sql

echo "Generated SQL file with $(wc -l < sterling_lockets_inserts.sql) insert statements"
echo ""
echo "Preview of SQL statements:"
head -10 sterling_lockets_inserts.sql