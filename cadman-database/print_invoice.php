<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice - Print</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .pdf-container {
            width: 100%;
            height: 100%;
        }
        
        .pdf-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .print-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            z-index: 9999;
        }
        
        .print-button:hover {
            background: #0056b3;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Print</button>
    
    <div class="pdf-container">
        <iframe id="pdfFrame" src=""></iframe>
    </div>
    
    <script>
        // Get invoice file from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const invoiceFile = urlParams.get('file');
        
        if (invoiceFile) {
            // Display the PDF file directly
            document.getElementById('pdfFrame').src = invoiceFile;
        } else {
            alert('No invoice file specified');
        }
    </script>
</body>
</html>
