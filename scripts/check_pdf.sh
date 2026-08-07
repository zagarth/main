#!/bin/bash
# PDF Validation and Error Checking Tool

echo "PDF VALIDATION AND ERROR CHECK"
echo "==============================="

if [ $# -eq 0 ]; then
    echo "Usage: $0 <pdf_file>"
    echo "Example: $0 Cadman_catalog/page_21a.pdf"
    exit 1
fi

PDF_FILE="$1"
BASENAME=$(basename "$PDF_FILE" .pdf)

if [ ! -f "$PDF_FILE" ]; then
    echo "❌ Error: File $PDF_FILE not found!"
    exit 1
fi

echo "Checking: $PDF_FILE"
echo "File size: $(du -h "$PDF_FILE" | cut -f1)"
echo ""

echo "1. BASIC PDF INFO CHECK"
echo "======================="
pdfinfo "$PDF_FILE" 2>&1 | head -20

echo ""
echo "2. QPDF VALIDATION CHECK"
echo "========================"
qpdf --check "$PDF_FILE" 2>&1
echo "qpdf exit code: $?"

echo ""
echo "3. QPDF JSON INFO (errors and warnings)"
echo "========================================"
qpdf --json --show-object=trailer "$PDF_FILE" 2>&1 | head -50

echo ""
echo "4. GHOSTSCRIPT VALIDATION"
echo "========================="
gs -q -dNOPAUSE -dBATCH -sDEVICE=nullpage "$PDF_FILE" 2>&1
echo "Ghostscript exit code: $?"

echo ""
echo "5. PDF STRUCTURE CHECK"
echo "======================"
echo "Checking for common mobile browser issues..."

# Check for PDF version
PDF_VERSION=$(pdfinfo "$PDF_FILE" 2>/dev/null | grep "PDF version" || echo "Unknown")
echo "PDF Version: $PDF_VERSION"

# Check file size (mobile browsers may have issues with very large files)
FILE_SIZE_KB=$(du -k "$PDF_FILE" | cut -f1)
echo "File size: ${FILE_SIZE_KB}KB"

if [ $FILE_SIZE_KB -gt 20480 ]; then  # 20MB
    echo "⚠️  WARNING: File is quite large (>20MB) - may cause issues on mobile"
fi

# Check for corruption signs
echo ""
echo "6. CORRUPTION DETECTION"
echo "======================="
if qpdf --check "$PDF_FILE" 2>&1 | grep -i "error\|corrupt\|damaged" > /dev/null; then
    echo "❌ Potential corruption detected!"
    qpdf --check "$PDF_FILE" 2>&1 | grep -i "error\|corrupt\|damaged"
else
    echo "✅ No obvious corruption detected"
fi

echo ""
echo "7. MOBILE COMPATIBILITY CHECK"
echo "============================="

# Check PDF version compatibility
if echo "$PDF_VERSION" | grep -q "1\.[0-3]"; then
    echo "✅ PDF version is mobile-friendly (1.0-1.3)"
elif echo "$PDF_VERSION" | grep -q "1\.[4-7]"; then
    echo "⚠️  PDF version may have limited mobile support (1.4-1.7)"
else
    echo "❌ PDF version may not be mobile compatible"
fi

# Check for embedded fonts (can cause mobile issues)
FONTS=$(pdfinfo "$PDF_FILE" 2>/dev/null | grep -c "Font" || echo "0")
echo "Embedded fonts: $FONTS"

# Try to repair if issues found
echo ""
echo "8. REPAIR OPTION"
echo "================"
echo "If issues found, would you like to create a repaired version? (y/n)"
echo "This will create: ${BASENAME}_repaired.pdf"

# Uncomment to auto-repair:
# qpdf --qdf --object-streams=disable "$PDF_FILE" "${BASENAME}_repaired.pdf"
# echo "Repaired version created: ${BASENAME}_repaired.pdf"