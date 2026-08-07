<?php
/**
 * Test Invoice Generation
 * Quick test page to generate a sample invoice
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Invoice Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .test-button {
            background: #007bff;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin: 10px 0;
        }
        .test-button:hover {
            background: #0056b3;
        }
        #result {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background: #f9f9f9;
        }
    </style>
</head>
<body>
    <h1>Invoice Generation Test</h1>
    <p>Click the button below to generate a sample PDF invoice:</p>
    
    <button class="test-button" onclick="generateTestInvoice()">Generate Test Invoice (PDF)</button>
    
    <div id="result"></div>
    
    <script>
        function generateTestInvoice() {
            // Sample order data
            const orderData = {
                customerName: "A&A JEWELLERS LTD",
                customerPhone: "(250) 395-2831",
                customerLocation: "100 Mile House, BC",
                accountNumber: "12345",
                salesRep: "JD",
                orderNumber: "TEST-001",
                orderDate: "<?php echo date('Y-m-d'); ?>",
                terms: "Net 30",
                items: [
                    {
                        line: 1,
                        quantity: 2,
                        itemCode: "14WG-RB-001",
                        description: "14K White Gold Ring Band",
                        price: 450.50
                    },
                    {
                        line: 2,
                        quantity: 1,
                        itemCode: "18YG-COIN-050",
                        description: "18K Yellow Gold Coin Pendant",
                        price: 1245.75
                    },
                    {
                        line: 3,
                        quantity: 3,
                        itemCode: "STER-CHAIN-24",
                        description: "Sterling Silver 24in Chain",
                        price: 125.00
                    }
                ],
                subtotal: 2316.75,
                discount: 231.68,  // 10% discount
                total: 2085.07
            };
            
            // Send to invoice generator (print-friendly version)
            fetch('generate_invoice.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(orderData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Open the PDF directly - browser will handle display and printing
                    window.open(data.url, '_blank');
                    
                    document.getElementById('result').innerHTML = 
                        '<p style="color: green;">✓ PDF Invoice generated successfully!<br>File: ' + data.filename + '</p>';
                } else {
                    throw new Error(data.error || 'Unknown error');
                }
            })
            .catch(error => {
                document.getElementById('result').innerHTML = 
                    '<p style="color: red;">✗ Error: ' + error.message + '</p>';
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>
