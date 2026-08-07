<?php
/**
 * Authorize.Net API Test
 * Tests actual API connectivity with your sandbox credentials
 */

require_once 'config/Config.php';

try {
    $config = Config::getInstance();
    $authNetConfig = $config->getAuthorizeNetConfig();
    
    echo "<h2>Authorize.Net API Test</h2>\n";
    
    // Create a simple test transaction request (auth only, no capture)
    $xml = '<?xml version="1.0" encoding="utf-8"?>
    <createTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
        <merchantAuthentication>
            <name>' . htmlspecialchars($authNetConfig['api_login_id']) . '</name>
            <transactionKey>' . htmlspecialchars($authNetConfig['transaction_key']) . '</transactionKey>
        </merchantAuthentication>
        <transactionRequest>
            <transactionType>authOnlyTransaction</transactionType>
            <amount>1.00</amount>
            <payment>
                <creditCard>
                    <cardNumber>4111111111111111</cardNumber>
                    <expirationDate>1225</expirationDate>
                    <cardCode>123</cardCode>
                </creditCard>
            </payment>
        </transactionRequest>
    </createTransactionRequest>';
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $authNetConfig['api_url']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: text/xml',
        'Content-Length: ' . strlen($xml)
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "<h3>API Test Results</h3>\n";
    echo "Environment: " . $authNetConfig['environment'] . "<br>\n";
    echo "API URL: " . $authNetConfig['api_url'] . "<br>\n";
    echo "HTTP Response Code: " . $httpCode . "<br>\n";
    
    if ($curlError) {
        echo "<span style='color: red;'>❌ cURL Error: " . htmlspecialchars($curlError) . "</span><br>\n";
    } else if ($httpCode == 200) {
        echo "<span style='color: green;'>✅ API Connection Successful</span><br>\n";
        
        // Parse XML response
        $xml_response = simplexml_load_string($response);
        if ($xml_response) {
            $resultCode = (string)$xml_response->messages->resultCode;
            $messageCode = (string)$xml_response->messages->message->code;
            $messageText = (string)$xml_response->messages->message->text;
            
            echo "Result Code: " . htmlspecialchars($resultCode) . "<br>\n";
            echo "Message Code: " . htmlspecialchars($messageCode) . "<br>\n";
            echo "Message: " . htmlspecialchars($messageText) . "<br>\n";
            
            if ($resultCode === 'Ok') {
                echo "<span style='color: green; font-weight: bold;'>✅ Credentials are valid!</span><br>\n";
            } else {
                echo "<span style='color: orange; font-weight: bold;'>⚠️ API responded but check credentials</span><br>\n";
            }
        }
    } else {
        echo "<span style='color: red;'>❌ HTTP Error: " . $httpCode . "</span><br>\n";
    }
    
    if ($config->isDebugMode()) {
        echo "<h3>Debug Information</h3>\n";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "...</pre>\n";
    }
    
} catch (Exception $e) {
    echo "<span style='color: red; font-weight: bold;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</span><br>\n";
}
?>