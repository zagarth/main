<?php
/**
 * Invoice Generator
 * Overlays order data onto the invoice template image
 */

// Get order data from POST; support CLI stdin for internal subprocess usage.
$rawInput = file_get_contents('php://input');
if (($rawInput === '' || $rawInput === false) && PHP_SAPI === 'cli') {
    $rawInput = file_get_contents('php://stdin');
}
$orderData = json_decode((string)$rawInput, true);

if (!$orderData) {
    http_response_code(400);
    echo json_encode(['error' => 'No order data provided']);
    exit;
}

// Load the invoice template
$templatePath = __DIR__ . '/invoice_template.png';
if (!file_exists($templatePath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Invoice template not found']);
    exit;
}

// Create image from template
$image = imagecreatefrompng($templatePath);
if (!$image) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load template']);
    exit;
}

// Set up colors
$black = imagecolorallocate($image, 0, 0, 0);
$blue = imagecolorallocate($image, 0, 0, 128);

// Set up font - use TrueType font for better quality
$fontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
$fontSize = 32; // Font size in points

// Helper function to write text using TrueType font
function writeText($image, $x, $y, $text, $color, $fontPath, $fontSize) {
    imagettftext($image, $fontSize, 0, $x, $y, $color, $fontPath, $text);
}

function getTextWidth($fontPath, $fontSize, $text) {
    $bbox = imagettfbbox($fontSize, 0, $fontPath, (string)$text);
    if ($bbox === false) {
        return 0;
    }

    $minX = min($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
    $maxX = max($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
    return $maxX - $minX;
}

function wrapTextToWidth($fontPath, $fontSize, $text, $maxWidth, $maxLines = 4) {
    $text = trim((string)$text);
    if ($text === '') {
        return [];
    }

    $lines = [];
    $currentLine = '';
    $textLength = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);

    for ($i = 0; $i < $textLength; $i++) {
        $char = function_exists('mb_substr') ? mb_substr($text, $i, 1) : substr($text, $i, 1);
        $candidate = $currentLine . $char;

        if ($currentLine === '') {
            $currentLine = $char;
            continue;
        }

        if (getTextWidth($fontPath, $fontSize, $candidate) <= $maxWidth) {
            $currentLine = $candidate;
        } else {
            $lines[] = $currentLine;
            $currentLine = $char;

            if (count($lines) >= $maxLines - 1) {
                $remaining = function_exists('mb_substr') ? mb_substr($text, $i + 1) : substr($text, $i + 1);
                if ($remaining !== '') {
                    $lines[] = rtrim($remaining) . '...';
                }
                break;
            }
        }
    }

    if ($currentLine !== '') {
        $lines[] = $currentLine;
    }

    return $lines;
}

// Extract order information
$customerName = $orderData['customerName'] ?? '';
$customerPhone = $orderData['customerPhone'] ?? '';
$customerLocation = $orderData['customerLocation'] ?? '';
$accountNumber = $orderData['accountNumber'] ?? 'N/A'; // Default fallback
$salesRep = $orderData['salesRep'] ?? 'WEB';
$orderNumber = $orderData['orderNumber'] ?? '';
$orderDate = $orderData['orderDate'] ?? date('Y-m-d');
$terms = $orderData['terms'] ?? 'NET30';
$items = $orderData['items'] ?? [];
$subtotal = isset($orderData['subtotal']) ? (float)$orderData['subtotal'] : 0.0;
$discount = isset($orderData['discount']) ? (float)$orderData['discount'] : 0.0;
$taxAmount = isset($orderData['tax']) ? (float)$orderData['tax'] : 0.0;
$total = isset($orderData['total']) ? (float)$orderData['total'] : max(0.0, $subtotal + $taxAmount);
$requestedFilename = $orderData['outputFilename'] ?? $orderData['outputBase'] ?? null;
if ($requestedFilename !== null) {
    $requestedFilename = preg_replace('/[^A-Za-z0-9._-]/', '_', trim((string)$requestedFilename));
}
if ($requestedFilename === '') {
    $requestedFilename = null;
}

// Debug: Log account number for troubleshooting
error_log("Invoice Debug: Account Number = '" . $accountNumber . "'");

// Position coordinates extracted from PDF template (in pixels at 300 DPI)
// Customer information - "Sold to" area (larger font)
$soldToX = 336;
$soldToY = 700; // Below the "Sold to" label
$customerFontSize = 32; // Larger font for customer info
writeText($image, $soldToX, $soldToY, $customerName, $black, $fontPath, $customerFontSize);
writeText($image, $soldToX, $soldToY + 45, $customerPhone, $black, $fontPath, $customerFontSize);
writeText($image, $soldToX, $soldToY + 90, $customerLocation, $black, $fontPath, $customerFontSize);

// Ship to area (same as sold to for now, larger font)
$shipToX = 1464;
$shipToY = 700;
writeText($image, $shipToX, $shipToY, $customerName, $black, $fontPath, $customerFontSize);
writeText($image, $shipToX, $shipToY + 45, $customerLocation, $black, $fontPath, $customerFontSize);

// Invoice Number - top right (large field)
$invoiceNumberX = 2200;
$invoiceNumberY = 230;
$invoiceNumberFontSize = 36; // Large font for invoice number
writeText($image, $invoiceNumberX, $invoiceNumberY, $orderNumber, $blue, $fontPath, $invoiceNumberFontSize);

// Order header fields
$invoiceDateDisplay = date('Y-m-d', strtotime($orderDate));

writeText($image, 115, 1095, $accountNumber, $black, $fontPath, $fontSize); // ACC#
writeText($image, 360, 1095, $salesRep, $black, $fontPath, $fontSize); // SLS
writeText($image, 520, 1095, $orderNumber, $black, $fontPath, $fontSize); // Order#
writeText($image, 950, 1095, 'Ground', $black, $fontPath, $fontSize); // Shipper
writeText($image, 1360, 1095, $invoiceDateDisplay, $black, $fontPath, $fontSize); // Ship Date
writeText($image, 1810, 1095, $terms, $black, $fontPath, $fontSize); // TERMS
writeText($image, 2215, 1095, $invoiceDateDisplay, $black, $fontPath, $fontSize); // Date Invoiced
writeText($image, 2520, 1095, '1', $black, $fontPath, $fontSize); // Page Number

// Line items - coordinates from template
$itemsStartY = 1370; // First line item Y position
$itemsEndY = 2790; // Last possible line item Y position
$maxLines = 21; // Maximum number of line items that fit
$lineHeight = ($itemsEndY - $itemsStartY) / $maxLines; // ~67 pixels per line

$currentY = $itemsStartY;
$lineCount = 0;

foreach ($items as $index => $item) {
    if ($lineCount >= $maxLines) break; // Stop if we run out of space
    
    $qtyOrdered = $item['quantity'] ?? 1;
    $qtyShipped = $item['quantity'] ?? 1; // Same as ordered for now
    $itemCode = $item['itemCode'] ?? '';
    $description = $item['description'] ?? '';
    $price = $item['price'] ?? 0;
    $lineTotal = $price * $qtyOrdered;
    $engravingText = trim((string)($item['engraving_text'] ?? $item['engravingText'] ?? ''));
    $engravingRequested = !empty($item['engraving_requested']) || !empty($item['engravingRequested']);
    $lineNote = trim((string)($item['line_note'] ?? $item['lineNote'] ?? $item['notes'] ?? ''));
    $engravingCost = isset($item['engraving_cost']) ? (float)$item['engraving_cost'] : (isset($item['engravingCost']) ? (float)$item['engravingCost'] : 0.0);

    $displayDescription = $description;
    $secondaryLine = '';
    if ($engravingRequested && $engravingText !== '') {
        $secondaryLine = 'Engraving: "' . $engravingText . '"';
    } elseif ($lineNote !== '') {
        $secondaryLine = 'Note: ' . $lineNote;
    }

    if ($engravingCost > 0) {
        $secondaryLine = ($secondaryLine !== '' ? $secondaryLine . ' | ' : '') . 'Add-on: $' . number_format($engravingCost, 2);
    }

    // Column positions from template
    writeText($image, 90, $currentY, (string)$qtyOrdered, $black, $fontPath, $fontSize); // QTY ordered
    writeText($image, 370, $currentY, (string)$qtyShipped, $black, $fontPath, $fontSize); // QTY shipped
    writeText($image, 620, $currentY, substr($itemCode, 0, 15), $black, $fontPath, $fontSize); // Item#
    writeText($image, 1120, $currentY, substr($displayDescription, 0, 80), $black, $fontPath, $fontSize); // Description
    writeText($image, 1890, $currentY, '$' . number_format($price, 2), $black, $fontPath, $fontSize); // Unit price
    writeText($image, 2220, $currentY, '$' . number_format($lineTotal, 2), $black, $fontPath, $fontSize); // Extended price

    $secondaryTextLines = [];
    if ($secondaryLine !== '') {
        $secondaryTextLines = wrapTextToWidth($fontPath, 24, $secondaryLine, 1100, 4);
        foreach ($secondaryTextLines as $secondaryIndex => $secondaryTextLine) {
            writeText($image, 1120, $currentY + 38 + ($secondaryIndex * 30), $secondaryTextLine, $black, $fontPath, 24);
        }
    }

    $detailLineCount = max(0, count($secondaryTextLines) - 1);
    $rowHeight = $lineHeight + ($detailLineCount * 30);
    $nextY = $currentY + $rowHeight;

    if ($nextY > $itemsEndY) {
        break;
    }

    $currentY = $nextY;
    $lineCount++;
}

// Totals area (bottom right - subtotal and discount)
$totalsY = 2975;
$totalsLabelX = 1900;
$totalsValueX = 2220;
$totalsFontSize = 32; // Larger font for totals

writeText($image, $totalsLabelX, $totalsY, "Subtotal:", $black, $fontPath, $totalsFontSize);
writeText($image, $totalsValueX, $totalsY, '$' . number_format($subtotal, 2), $black, $fontPath, $totalsFontSize);

if ($discount > 0) {
    $totalsY += 60; // More spacing between lines
    writeText($image, $totalsLabelX, $totalsY, "Discount:", $black, $fontPath, $totalsFontSize);
    writeText($image, $totalsValueX, $totalsY, '-$' . number_format($discount, 2), $black, $fontPath, $totalsFontSize);
}

if ($taxAmount > 0) {
    $totalsY += 60;
    writeText($image, $totalsLabelX, $totalsY, "Tax:", $black, $fontPath, $totalsFontSize);
    writeText($image, $totalsValueX, $totalsY, '$' . number_format($taxAmount, 2), $black, $fontPath, $totalsFontSize);
}

// TOTAL box in bottom right corner (separate location)
$totalBoxX = 2255; // 80px to the left
$totalBoxY = 3220; // 75px down
$totalFontSize = 34;
writeText($image, $totalBoxX, $totalBoxY, '$' . number_format($total, 2), $blue, $fontPath, $totalFontSize);

// Save to temp directory - create PNG first, then convert to PDF
$tempDir = __DIR__ . '/temp_invoices';
if (!is_dir($tempDir)) {
    if (!mkdir($tempDir, 0775, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create temp directory']);
        exit;
    }
    chown($tempDir, 'www-data');
    chgrp($tempDir, 'www-data');
}

// Generate filename with timestamp so reprints open a fresh PDF instead of a cached file.
$baseFilename = $requestedFilename ?? 'invoice_' . ($orderNumber ?: 'quote_' . date('md'));
if ($requestedFilename === null) {
    $baseFilename .= '_' . time();
}
$pngFilepath = $tempDir . '/' . $baseFilename . '.png';
$pdfFilepath = $tempDir . '/' . $baseFilename . '.pdf';

// Remove any stale PDF so a prior run cannot mask a failed conversion.
if (file_exists($pdfFilepath)) {
    unlink($pdfFilepath);
}

// Save the PNG first
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, we'll capture
$lastError = null;
set_error_handler(function($errno, $errstr) use (&$lastError) {
    $lastError = $errstr;
});

$saveResult = imagepng($image, $pngFilepath);

restore_error_handler();

if (!$saveResult) {
    imagedestroy($image);
    http_response_code(500);
    $errorMsg = 'Failed to save PNG file';
    if ($lastError) {
        $errorMsg .= ': ' . $lastError;
    }
    echo json_encode(['success' => false, 'error' => $errorMsg]);
    exit;
}

// Clean up image resource
imagedestroy($image);

// Convert PNG to PDF using ImageMagick
// Create PDF with proper Letter page size (8.5x11 inches = 612x792 points at 72 DPI)
// The PNG is 2550x3300 pixels at 300 DPI = 8.5x11 inches
$convertCmd = sprintf(
    'convert %s -units PixelsPerInch -density 300 -compress jpeg -quality 95 -define pdf:pagesize=letter %s 2>&1',
    escapeshellarg($pngFilepath),
    escapeshellarg($pdfFilepath)
);

exec($convertCmd, $output, $returnCode);

clearstatcache(true, $pdfFilepath);
$pdfCreated = file_exists($pdfFilepath) && filesize($pdfFilepath) > 0;

if (!$pdfCreated) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to convert to PDF: ' . implode(' ', $output)
    ]);
    exit;
}

if ($returnCode !== 0) {
    error_log('Invoice generator convert exited non-zero but produced PDF: ' . implode(' ', $output));
}

// Delete the temporary PNG file
unlink($pngFilepath);

// Return JSON with file URL
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'filename' => $baseFilename . '.pdf',
    'url' => 'temp_invoices/' . $baseFilename . '.pdf',
    'filepath' => $pdfFilepath,
    'timestamp' => time()
]);
