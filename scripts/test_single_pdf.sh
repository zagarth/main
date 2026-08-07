#!/bin/bash
# Test script on a single PDF
echo "Testing process_catalog_pdfs.sh on page_01a.pdf..."

# Create a test copy
cp Cadman_catalog/page_01a.pdf test_page.pdf

# Run processing on test file
./process_catalog_pdfs.sh test_page.pdf

# Check results
if [ -f test_page.pdf ]; then
    echo ""
    echo "Test results:"
    echo "============="
    echo "Original: $(du -h Cadman_catalog/page_01a.pdf | cut -f1)"
    echo "Processed: $(du -h test_page.pdf | cut -f1)"
    echo ""
    echo "Price check (original):"
    pdftotext Cadman_catalog/page_01a.pdf - 2>/dev/null | grep -i price | head -3
    echo ""
    echo "Price check (processed):"
    pdftotext test_page.pdf - 2>/dev/null | grep -i price | head -3
fi