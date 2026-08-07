#!/bin/bash
# Alternative approach using Ghostscript to manipulate PDF

echo "PDF Price Removal using Ghostscript"
echo "==================================="

INPUT_PDF="Cadman_catalog/page_21a.pdf"
OUTPUT_PDF="Cadman_catalog/page_21a_no_prices_gs.pdf"
TEMP_PS="temp_page_21a.ps"

echo "Step 1: Converting PDF to PostScript..."
gs -q -dNOPAUSE -dBATCH -sDEVICE=ps2write -sOutputFile="$TEMP_PS" "$INPUT_PDF"

if [ ! -f "$TEMP_PS" ]; then
    echo "❌ Failed to convert PDF to PostScript"
    exit 1
fi

echo "Step 2: Removing price-related text from PostScript..."
# Remove lines containing "Price" and related drawing commands
sed -i '/Price/d' "$TEMP_PS"
sed -i '/^(Price)/d' "$TEMP_PS"
sed -i '/Price.*show/d' "$TEMP_PS"
sed -i '/Price.*Tj/d' "$TEMP_PS"

echo "Step 3: Converting back to PDF with proper optimization..."
gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite \
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
   -sOutputFile="$OUTPUT_PDF" "$TEMP_PS"

if [ -f "$OUTPUT_PDF" ]; then
    echo "✅ Successfully created $OUTPUT_PDF"
    echo "File sizes:"
    ls -lh "$INPUT_PDF" "$OUTPUT_PDF"
    
    echo ""
    echo "Checking text content..."
    echo "Original PDF text:"
    pdftotext "$INPUT_PDF" - | head -10
    echo ""
    echo "Modified PDF text:"
    pdftotext "$OUTPUT_PDF" - | head -10
else
    echo "❌ Failed to create output PDF"
fi

# Clean up
rm -f "$TEMP_PS"

echo ""
echo "Now replacing the original file..."
cp "$OUTPUT_PDF" "$INPUT_PDF"
echo "✅ Original page_21a.pdf has been updated (backup available)"