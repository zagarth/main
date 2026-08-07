<?php
/**
 * Modern Authorize.Net Payment Processor
 * Implements Accept.js for PCI-compliant payment processing
 * Cadman Manufacturing
 */

require_once __DIR__ . '/../config/authorize_net_config.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // Composer autoload

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class AuthorizeNetProcessor {
    private $merchantAuthentication;
    private $config;
    
    public function __construct() {
        $this->config = getAuthorizeNetConfig();
        validateAuthorizeNetConfig();
        
        $this->merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $this->merchantAuthentication->setName($this->config['login_id']);
        $this->merchantAuthentication->setTransactionKey($this->config['transaction_key']);
    }
    
    /**
     * Process payment using Accept.js token
     * 
     * @param array $opaqueData Array with dataDescriptor and dataValue from Accept.js
     * @param float $amount Transaction amount
     * @param array $orderData Order and customer information
     * @return array Transaction result
     */
    public function processPayment($opaqueData, $amount, $orderData) {
        try {
            // Create opaque data object
            $opaqueDataObject = new AnetAPI\OpaqueDataType();
            $opaqueDataObject->setDataDescriptor($opaqueData['dataDescriptor']);
            $opaqueDataObject->setDataValue($opaqueData['dataValue']);
            
            // Create payment object
            $paymentOne = new AnetAPI\PaymentType();
            $paymentOne->setOpaqueData($opaqueDataObject);
            
            // Create transaction request
            $transactionRequestType = new AnetAPI\TransactionRequestType();
            $transactionRequestType->setTransactionType("authCaptureTransaction");
            $transactionRequestType->setAmount(number_format($amount, 2, '.', ''));
            $transactionRequestType->setPayment($paymentOne);
            
            // Add order information
            $order = new AnetAPI\OrderType();
            $order->setInvoiceNumber($orderData['order_number']);
            $order->setDescription("Cadman Manufacturing - Jewelry Order #{$orderData['order_number']}");
            $transactionRequestType->setOrder($order);
            
            // Add customer information
            if (!empty($orderData['customer_email'])) {
                $customerData = new AnetAPI\CustomerDataType();
                $customerData->setType("individual");
                $customerData->setId($orderData['customer_id'] ?? substr(md5($orderData['customer_email']), 0, 20));
                $customerData->setEmail($orderData['customer_email']);
                $transactionRequestType->setCustomer($customerData);
            }
            
            // Add billing information
            if (!empty($orderData['billing'])) {
                $billTo = new AnetAPI\CustomerAddressType();
                $billTo->setFirstName($orderData['billing']['first_name'] ?? '');
                $billTo->setLastName($orderData['billing']['last_name'] ?? '');
                $billTo->setCompany($orderData['billing']['company'] ?? '');
                $billTo->setAddress($orderData['billing']['address'] ?? '');
                $billTo->setCity($orderData['billing']['city'] ?? '');
                $billTo->setState($orderData['billing']['state'] ?? '');
                $billTo->setZip($orderData['billing']['zip'] ?? '');
                $billTo->setCountry($orderData['billing']['country'] ?? 'US');
                $billTo->setPhoneNumber($orderData['billing']['phone'] ?? '');
                $transactionRequestType->setBillTo($billTo);
            }
            
            // Add shipping information (if different from billing)
            if (!empty($orderData['shipping']) && $orderData['shipping'] !== $orderData['billing']) {
                $shipTo = new AnetAPI\CustomerAddressType();
                $shipTo->setFirstName($orderData['shipping']['first_name'] ?? '');
                $shipTo->setLastName($orderData['shipping']['last_name'] ?? '');
                $shipTo->setCompany($orderData['shipping']['company'] ?? '');
                $shipTo->setAddress($orderData['shipping']['address'] ?? '');
                $shipTo->setCity($orderData['shipping']['city'] ?? '');
                $shipTo->setState($orderData['shipping']['state'] ?? '');
                $shipTo->setZip($orderData['shipping']['zip'] ?? '');
                $shipTo->setCountry($orderData['shipping']['country'] ?? 'US');
                $transactionRequestType->setShipTo($shipTo);
            }
            
            // Add line items for detailed reporting
            if (!empty($orderData['items'])) {
                $lineItems = [];
                foreach ($orderData['items'] as $item) {
                    $lineItem = new AnetAPI\LineItemType();
                    $lineItem->setItemId($item['id']);
                    $lineItem->setName(substr($item['name'], 0, 31)); // Authorize.Net limit
                    $lineItem->setDescription(substr($item['description'] ?? $item['name'], 0, 255));
                    $lineItem->setQuantity($item['quantity']);
                    $lineItem->setUnitPrice(number_format($item['price'], 2, '.', ''));
                    $lineItems[] = $lineItem;
                }
                $transactionRequestType->setLineItems($lineItems);
            }
            
            // Add tax information
            if (!empty($orderData['tax_amount']) && $orderData['tax_amount'] > 0) {
                $tax = new AnetAPI\ExtendedAmountType();
                $tax->setAmount(number_format($orderData['tax_amount'], 2, '.', ''));
                $tax->setName("Sales Tax");
                $tax->setDescription("State Sales Tax");
                $transactionRequestType->setTax($tax);
            }
            
            // Add shipping information
            if (!empty($orderData['shipping_amount']) && $orderData['shipping_amount'] > 0) {
                $shipping = new AnetAPI\ExtendedAmountType();
                $shipping->setAmount(number_format($orderData['shipping_amount'], 2, '.', ''));
                $shipping->setName("Shipping");
                $shipping->setDescription("Standard Shipping");
                $transactionRequestType->setShipping($shipping);
            }
            
            // Add transaction settings for enhanced security
            $transactionSettings = [];
            
            // Duplicate transaction checking
            $duplicateWindowSetting = new AnetAPI\SettingType();
            $duplicateWindowSetting->setSettingName("duplicateWindow");
            $duplicateWindowSetting->setSettingValue("120"); // 2 minutes
            $transactionSettings[] = $duplicateWindowSetting;
            
            // Email receipt
            if (!empty($orderData['customer_email'])) {
                $emailCustomerSetting = new AnetAPI\SettingType();
                $emailCustomerSetting->setSettingName("emailCustomer");
                $emailCustomerSetting->setSettingValue("true");
                $transactionSettings[] = $emailCustomerSetting;
            }
            
            if (!empty($transactionSettings)) {
                $transactionRequestType->setTransactionSettings($transactionSettings);
            }
            
            // Add user fields for tracking
            $userFields = [];
            
            $userField1 = new AnetAPI\UserFieldType();
            $userField1->setName("customer_ip");
            $userField1->setValue($_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $userFields[] = $userField1;
            
            $userField2 = new AnetAPI\UserFieldType();
            $userField2->setName("order_source");
            $userField2->setValue("website");
            $userFields[] = $userField2;
            
            if (!empty($userFields)) {
                $transactionRequestType->setUserFields($userFields);
            }
            
            // Create the request
            $request = new AnetAPI\CreateTransactionRequest();
            $request->setMerchantAuthentication($this->merchantAuthentication);
            $request->setRefId($orderData['order_number']);
            $request->setTransactionRequest($transactionRequestType);
            
            // Execute the request
            $controller = new AnetController\CreateTransactionController($request);
            $response = $controller->executeWithApiResponse($this->config['endpoint']);
            
            return $this->processResponse($response, $orderData);
            
        } catch (Exception $e) {
            error_log("Authorize.Net Payment Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment processing error. Please try again.',
                'error_code' => 'PROCESSING_ERROR',
                'details' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process Authorize.Net response
     */
    private function processResponse($response, $orderData) {
        if ($response != null) {
            if ($response->getMessages()->getResultCode() == "Ok") {
                $tresponse = $response->getTransactionResponse();
                
                if ($tresponse != null && $tresponse->getMessages() != null) {
                    $result = [
                        'success' => true,
                        'transaction_id' => $tresponse->getTransId(),
                        'auth_code' => $tresponse->getAuthCode(),
                        'response_code' => $tresponse->getResponseCode(),
                        'message' => $tresponse->getMessages()[0]->getDescription(),
                        'order_number' => $orderData['order_number']
                    ];
                    
                    // Add fraud detection results
                    if ($tresponse->getAvsResultCode()) {
                        $result['avs_result'] = $tresponse->getAvsResultCode();
                    }
                    
                    if ($tresponse->getCvvResultCode()) {
                        $result['cvv_result'] = $tresponse->getCvvResultCode();
                    }
                    
                    // Add account information
                    if ($tresponse->getAccountNumber()) {
                        $result['last_four'] = substr($tresponse->getAccountNumber(), -4);
                        $result['account_type'] = $tresponse->getAccountType();
                    }
                    
                    return $result;
                } else {
                    // Transaction failed
                    $errorMessages = [];
                    if ($tresponse->getErrors() != null) {
                        foreach ($tresponse->getErrors() as $error) {
                            $errorMessages[] = $error->getErrorText();
                        }
                    }
                    
                    return [
                        'success' => false,
                        'error' => 'Transaction declined: ' . implode(', ', $errorMessages),
                        'error_code' => 'TRANSACTION_DECLINED',
                        'response_code' => $tresponse->getResponseCode()
                    ];
                }
            } else {
                // API error
                $errorMessages = [];
                foreach ($response->getMessages()->getMessage() as $error) {
                    $errorMessages[] = $error->getText();
                }
                
                return [
                    'success' => false,
                    'error' => 'API Error: ' . implode(', ', $errorMessages),
                    'error_code' => 'API_ERROR'
                ];
            }
        } else {
            return [
                'success' => false,
                'error' => 'No response received from payment gateway',
                'error_code' => 'NO_RESPONSE'
            ];
        }
    }
    
    /**
     * Void a transaction (same day only)
     */
    public function voidTransaction($transactionId) {
        try {
            $transactionRequestType = new AnetAPI\TransactionRequestType();
            $transactionRequestType->setTransactionType("voidTransaction");
            $transactionRequestType->setRefTransId($transactionId);
            
            $request = new AnetAPI\CreateTransactionRequest();
            $request->setMerchantAuthentication($this->merchantAuthentication);
            $request->setTransactionRequest($transactionRequestType);
            
            $controller = new AnetController\CreateTransactionController($request);
            $response = $controller->executeWithApiResponse($this->config['endpoint']);
            
            if ($response != null && $response->getMessages()->getResultCode() == "Ok") {
                return ['success' => true, 'message' => 'Transaction voided successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to void transaction'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Refund a settled transaction
     */
    public function refundTransaction($transactionId, $amount, $lastFour) {
        try {
            $creditCard = new AnetAPI\CreditCardType();
            $creditCard->setCardNumber($lastFour);
            $creditCard->setExpirationDate("XXXX");
            
            $paymentOne = new AnetAPI\PaymentType();
            $paymentOne->setCreditCard($creditCard);
            
            $transactionRequestType = new AnetAPI\TransactionRequestType();
            $transactionRequestType->setTransactionType("refundTransaction");
            $transactionRequestType->setAmount($amount);
            $transactionRequestType->setPayment($paymentOne);
            $transactionRequestType->setRefTransId($transactionId);
            
            $request = new AnetAPI\CreateTransactionRequest();
            $request->setMerchantAuthentication($this->merchantAuthentication);
            $request->setTransactionRequest($transactionRequestType);
            
            $controller = new AnetController\CreateTransactionController($request);
            $response = $controller->executeWithApiResponse($this->config['endpoint']);
            
            if ($response != null && $response->getMessages()->getResultCode() == "Ok") {
                return ['success' => true, 'message' => 'Refund processed successfully'];
            } else {
                return ['success' => false, 'error' => 'Failed to process refund'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>