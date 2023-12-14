<?php

/**
 * Helper class containing methods to handle payment related functionalities.
 * 
 * @author Cardlink S.A.
 */
class Cardlink_Checkout_Helper_Payment extends Mage_Core_Helper_Abstract
{
    /**
     * Gets the URL of the Cardlink payment gateway according to the configured business partner and transaction environment.
     * 
     * @return string
     */
    function getPaymentGatewayUrl()
    {
        return Mage::getUrl('cardlink_checkout/payment/gateway', array('_secure' => true));
    }

    /**
     * Returns the payment gateway redirection URL the configured Business Partner and the transactions environment.
     *
     * @return string The URL of the payment gateway.
     */
    public function getPaymentGatewayDataPostUrl()
    {
        $businessPartner = Mage::helper('cardlink_checkout')->getBusinessPartner();
        $transactionEnvironment = Mage::helper('cardlink_checkout')->getTransactionEnvironment();

        if ($transactionEnvironment == Cardlink_Checkout_Model_System_Config_Source_TransactionEnvironments::PRODUCTION_ENVIRONMENT) {
            switch ($businessPartner) {
                case Cardlink_Checkout_Model_System_Config_Source_BusinessPartners::BUSINESS_PARTNER_CARDLINK:
                    return 'https://ecommerce.cardlink.gr/vpos/shophandlermpi';

                case Cardlink_Checkout_Model_System_Config_Source_BusinessPartners::BUSINESS_PARTNER_NEXI:
                    return 'https://www.alphaecommerce.gr/vpos/shophandlermpi';

                case Cardlink_Checkout_Model_System_Config_Source_BusinessPartners::BUSINESS_PARTNER_WORLDLINE:
                    return 'https://vpos.eurocommerce.gr/vpos/shophandlermpi';

                default:
            }
        } else {
            switch ($businessPartner) {
                case Cardlink_Checkout_Model_System_Config_Source_BusinessPartners::BUSINESS_PARTNER_CARDLINK:
                    return 'https://ecommerce-test.cardlink.gr/vpos/shophandlermpi';

                case Cardlink_Checkout_Model_System_Config_Source_BusinessPartners::BUSINESS_PARTNER_NEXI:
                    return 'https://alphaecommerce-test.cardlink.gr/vpos/shophandlermpi';

                case Cardlink_Checkout_Model_System_Config_Source_BusinessPartners::BUSINESS_PARTNER_WORLDLINE:
                    return 'https://eurocommerce-test.cardlink.gr/vpos/shophandlermpi';

                default:
            }
        }
        return NULL;
    }

    /**
     * Returns the maximum number of installments according to the order amount.
     * 
     * @param float|string $orderAmount The total amount of the order to be used for calculating the maximum number of installments.
     * @return int The maximum number of installments.
     */
    public function getMaxInstallments($orderAmount)
    {
        $helper = Mage::helper('cardlink_checkout');

        $maxInstallments = 1;
        $installmentsConfiguration = $helper->getInstallmentsConfiguration();

        if (!empty($installmentsConfiguration)) {

            foreach ($installmentsConfiguration as $range) {
                if (
                    $range['start_amount'] <= $orderAmount
                    && (
                        ($range['end_amount'] > 0 && $range['end_amount'] >= $orderAmount)
                        || $range['end_amount'] == 0
                    )
                ) {
                    $maxInstallments = $range['max_installments'];
                }
            }
        }

        return max(0, min(60, $maxInstallments));
    }

    /**
     * Returns the URL that the customer will be redirected after a successful payment transaction.
     * 
     * @return string The URL of the checkout payment success page.
     */
    private function getTransactionSuccessUrl()
    {
        return Mage::getUrl('cardlink_checkout/payment/response', array('_secure' => true));
    }

    /**
     * Returns the URL that the customer will be redirected after a failed or canceled payment transaction.
     * 
     * @return string The URL of the store's checkout payment failure/cancelation page.
     */
    private function getTransactionCancelUrl()
    {
        return Mage::getUrl('cardlink_checkout/payment/response', array('_secure' => true));
    }

    /**
     * Returns the required payment gateway's API value for the transaction type (trType) property.
     * 
     * @return string '1' for Sale/Capture, '2' for Authorize.
     */
    private function getTransactionTypeValue()
    {
        switch (Mage::helper('cardlink_checkout')->getTransactionType()) {
            case Cardlink_Checkout_Model_System_Config_Source_TransactionTypes::TRANSACTION_TYPE_CAPTURE:
                return '1';

            case Cardlink_Checkout_Model_System_Config_Source_TransactionTypes::TRANSACTION_TYPE_AUTHORIZE:
                return '2';
        }
    }

    /**
     * Loads the order information for
     * 
     * @param int|string $orderId The entity ID of the order.
     * @return array An associative array containing the data that will be sent to the payment gateway's API endpoint to perform the requested transaction.
     */
    public function getFormDataForOrder($orderId)
    {
        $helper = Mage::helper('cardlink_checkout');

        $order = new Mage_Sales_Model_Order();
        $order->loadByIncrementId($orderId);
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        $payment = $order->getPayment();
        $payment_method_code = $payment->getMethodInstance()->getCode();

        if ($billingAddress == false || $shippingAddress == false) {
            if ($helper->logDebugInfoEnabled()) {
                $helper->logMessage("Invalid billing/shipping address for order {$orderId}.");
            }
            return false;
        }

        // Version number - must be '2'
        $formData[Cardlink_Checkout_Model_ApiFields::Version] = '2';
        // Device category - always '0'
        $formData[Cardlink_Checkout_Model_ApiFields::DeviceCategory] = '0';
        //// Maximum number of payment retries - set to 10
        //$formData[Cardlink_Checkout_Model_ApiFields::MaxPayRetries] = '10';

        // The Merchant ID
        $formData[Cardlink_Checkout_Model_ApiFields::MerchantId] = $helper->getMerchantId();


        // Transaction success/failure return URLs
        $formData[Cardlink_Checkout_Model_ApiFields::ConfirmUrl] = $this->getTransactionSuccessUrl();
        $formData[Cardlink_Checkout_Model_ApiFields::CancelUrl] = $this->getTransactionCancelUrl();

        // Order information
        $formData[Cardlink_Checkout_Model_ApiFields::OrderId] = $orderId;
        $formData[Cardlink_Checkout_Model_ApiFields::OrderAmount] = floatval($order->getGrandTotal()); // Get order total amount
        $formData[Cardlink_Checkout_Model_ApiFields::Currency] = $order->getOrderCurrencyCode(); // Get order currency code

        $diasCode = $helper->getDiasCode();
        $enableIrisPayments = $helper->isIrisEnabled() && $diasCode != '';

        if ($payment_method_code == 'cardlink_checkout_iris' && $enableIrisPayments) {
            $formData[Cardlink_Checkout_Model_ApiFields::TransactionType] = '1';
            $formData[Cardlink_Checkout_Model_ApiFields::PaymentMethod] = 'IRIS';
            $formData[Cardlink_Checkout_Model_ApiFields::OrderDescription] = self::generateIrisRFCode($diasCode, $formData[Cardlink_Checkout_Model_ApiFields::OrderId], $formData[Cardlink_Checkout_Model_ApiFields::OrderAmount]);
        } else {
            $formData[Cardlink_Checkout_Model_ApiFields::OrderDescription] = 'ORDER ' . $orderId;
            // The type of transaction to perform (Sale/Authorize).
            $formData[Cardlink_Checkout_Model_ApiFields::TransactionType] = $this->getTransactionTypeValue();
        }

        // Payer/customer information
        $formData[Cardlink_Checkout_Model_ApiFields::PayerEmail] = $billingAddress->getEmail();
        $formData[Cardlink_Checkout_Model_ApiFields::PayerPhone] = $billingAddress->getTelephone();

        // Billing information
        $formData[Cardlink_Checkout_Model_ApiFields::BillCountry] = $billingAddress->getCountryId();
        //$formData[Cardlink_Checkout_Model_ApiFields::BillState] = $billingAddress->getRegionCode();
        $formData[Cardlink_Checkout_Model_ApiFields::BillZip] = $billingAddress->getPostcode();
        $formData[Cardlink_Checkout_Model_ApiFields::BillCity] = $billingAddress->getCity();
        $formData[Cardlink_Checkout_Model_ApiFields::BillAddress] = $billingAddress->getStreet(1);

        // Shipping information
        $formData[Cardlink_Checkout_Model_ApiFields::ShipCountry] = $shippingAddress->getCountryId();
        //$formData[Cardlink_Checkout_Model_ApiFields::ShipState] = $shippingAddress->getRegionCode();
        $formData[Cardlink_Checkout_Model_ApiFields::ShipZip] = $shippingAddress->getPostcode();
        $formData[Cardlink_Checkout_Model_ApiFields::ShipCity] = $shippingAddress->getCity();
        $formData[Cardlink_Checkout_Model_ApiFields::ShipAddress] = $shippingAddress->getStreet(1);

        // The optional URL of a CSS file to be included in the pages of the payment gateway for custom formatting.
        $cssUrl = trim($helper->getCssUrl());
        if ($cssUrl != '') {
            $formData[Cardlink_Checkout_Model_ApiFields::CssUrl] = $cssUrl;
        }

        // Instruct the payment gateway to use the store language for its UI.
        if ($helper->getForceStoreLanguage()) {
            $formData[Cardlink_Checkout_Model_ApiFields::Language] = explode('_', Mage::getStoreConfig('general/locale/code'))[0];
        }
        // Installments information.
        if ($helper->acceptsInstallments()) {
            $maxInstallments = $this->getMaxInstallments($formData[Cardlink_Checkout_Model_ApiFields::OrderAmount]);
            $installments = max(0, min($maxInstallments, $order->getPayment()->getCardlinkInstallments() + 0));

            if ($installments > 1) {
                $formData[Cardlink_Checkout_Model_ApiFields::ExtInstallmentoffset] = 0;
                $formData[Cardlink_Checkout_Model_ApiFields::ExtInstallmentperiod] = $installments;
            }
        }

        // Tokenization
        if ($helper->allowsTokenization()) {
            if ($payment->getCardlinkStoredToken()) {
                $storedToken = Mage::helper('cardlink_checkout/tokenization')->getCustomerStoredToken(
                    Mage::helper('cardlink_checkout')->getMerchantId(),
                    $order->getCustomerId(),
                    $payment->getCardlinkStoredToken()
                );

                if ($storedToken != null && $storedToken->token != null) {
                    $formData[Cardlink_Checkout_Model_ApiFields::ExtTokenOptions] = 100;
                    $formData[Cardlink_Checkout_Model_ApiFields::ExtToken] = $storedToken->token;
                }
            } else if ($payment->getCardlinkTokenize()) {
                $formData[Cardlink_Checkout_Model_ApiFields::ExtTokenOptions] = 100;
            }
        }

        // Calculate the digest of the transaction request data and append it.
        $signedFormData = self::signRequestFormData($formData, $helper->getSharedSecret());

        if ($helper->logDebugInfoEnabled()) {
            $helper->logMessage("Valid payment request created for order {$signedFormData[Cardlink_Checkout_Model_ApiFields::OrderId]}.");
            $helper->logMessage($signedFormData);
        }

        return $signedFormData;
    }

    /**
     * Generate the Request Fund (RF) code for IRIS payments.
     * @param string $diasCustomerCode The DIAS customer code of the merchant.
     * @param mixed $orderId The ID of the order.
     * @param mixed $amount The amount due.
     * @return string The generated RF code.
     */
    public static function generateIrisRFCode(string $diasCustomerCode, $orderId, $amount)
    {
        /* calculate payment check code */
        $paymentSum = 0;

        if ($amount > 0) {
            $ordertotal = str_replace([','], '.', (string) $amount);
            $ordertotal = number_format($ordertotal, 2, '', '');
            $ordertotal = strrev($ordertotal);
            $factor = [1, 7, 3];
            $idx = 0;
            for ($i = 0; $i < strlen($ordertotal); $i++) {
                $idx = $idx <= 2 ? $idx : 0;
                $paymentSum += $ordertotal[$i] * $factor[$idx];
                $idx++;
            }
        }

        $orderIdNum = (int) filter_var($orderId, FILTER_SANITIZE_NUMBER_INT);

        $randomNumber = str_pad($orderIdNum, 13, '0', STR_PAD_LEFT);
        $paymentCode = $paymentSum ? ($paymentSum % 8) : '8';
        $systemCode = '12';
        $tempCode = $diasCustomerCode . $paymentCode . $systemCode . $randomNumber . '271500';
        $mod97 = bcmod($tempCode, '97');

        $cd = 98 - (int) $mod97;
        $cd = str_pad((string) $cd, 2, '0', STR_PAD_LEFT);
        $rf_payment_code = 'RF' . $cd . $diasCustomerCode . $paymentCode . $systemCode . $randomNumber;

        return $rf_payment_code;
    }

    /**
     * Sign a bank request with the merchant's shared key and insert the digest in the data.
     * 
     * @param array $formData The payment request data.
     * @param string $sharedSecret The shared secret code of the merchant.
     * 
     * @return array The original request data put in proper order including the calculated data digest.
     */
    public function signRequestFormData($formData, $sharedSecret)
    {
        $ret = [];
        $concatenatedData = '';

        foreach (Cardlink_Checkout_Model_ApiFields::TRANSACTION_REQUEST_DIGEST_CALCULATION_FIELD_ORDER as $field) {
            if (array_key_exists($field, $formData)) {
                $ret[$field] = trim($formData[$field]);
                $concatenatedData .= $ret[$field];
            }
        }

        $concatenatedData .= $sharedSecret;
        $ret[Cardlink_Checkout_Model_ApiFields::Digest] = self::generateDigest($concatenatedData);

        return $ret;
    }

    /**
     * Validate the response data of the payment gateway by recalculating and comparing the data digests in order to identify legitimate incoming request.
     * 
     * @param array $formData The payment gateway response data.
     * @param string $sharedSecret The shared secret code of the merchant.
     * 
     * @return bool Identifies that the incoming data were sent by the payment gateway.
     */
    public function validateResponseData($formData, $sharedSecret)
    {
        $concatenatedData = '';

        foreach (Cardlink_Checkout_Model_ApiFields::TRANSACTION_RESPONSE_DIGEST_CALCULATION_FIELD_ORDER as $field) {
            if ($field != Cardlink_Checkout_Model_ApiFields::Digest) {
                if (array_key_exists($field, $formData)) {
                    $concatenatedData .= $formData[$field];
                }
            }
        }

        $concatenatedData .= $sharedSecret;
        $generatedDigest = $this->GenerateDigest($concatenatedData);

        return $formData[Cardlink_Checkout_Model_ApiFields::Digest] == $generatedDigest;
    }

    /**
     * Validate the response data of the payment gateway for Alpha Bonus transactions 
     * by recalculating and comparing the data digests in order to identify legitimate incoming request.
     * 
     * @param array $formData The payment gateway response data.
     * @param string $sharedSecret The shared secret code of the merchant.
     * 
     * @return bool Identifies that the incoming data were sent by the payment gateway.
     */
    public function validateXlsBonusResponseData($formData, $sharedSecret)
    {
        $concatenatedData = '';

        foreach (Cardlink_Checkout_Model_ApiFields::TRANSACTION_RESPONSE_XLSBONUS_DIGEST_CALCULATION_FIELD_ORDER as $field) {
            if ($field != Cardlink_Checkout_Model_ApiFields::XlsBonusDigest) {
                if (array_key_exists($field, $formData)) {
                    $concatenatedData .= $formData[$field];
                }
            }
        }

        $concatenatedData .= $sharedSecret;
        $generatedDigest = $this->GenerateDigest($concatenatedData);

        return $formData[Cardlink_Checkout_Model_ApiFields::XlsBonusDigest] == $generatedDigest;
    }

    /**
     * Generate the message digest from a concatenated data string.
     * 
     * @param string $concatenatedData The data to calculate the digest for.
     */
    public function generateDigest($concatenatedData)
    {
        return base64_encode(hash('sha256', $concatenatedData, true));
    }

    /**
     * Restore last active quote based on checkout session
     *
     * @return bool True if quote restored successfully, false otherwise
     */
    public function restoreQuote($order)
    {
        $helper = Mage::helper('cardlink_checkout');
        $logEnabled = $helper->logDebugInfoEnabled();

        if ($order->getId()) {
            $quote = $this->_getQuote($order->getQuoteId());

            if ($quote->getId()) {
                $quote->setIsActive(1)
                    ->setReservedOrderId(null)
                    ->save();
                $this->_getCheckoutSession()
                    ->replaceQuote($quote)
                    ->unsLastRealOrderId();

                if ($logEnabled) {
                    $helper->logMessage("Quote {$quote->getId()} of order {$order->getIncrementId()} was restored.");
                }

                return true;
            } else if ($logEnabled) {
                $helper->logMessage("Failed to retrieve the quote of order {$order->getIncrementId()}.");
            }
        } else if ($logEnabled) {
            $helper->logMessage("Failed to retrieve order to restore quote.");
        }
        return false;
    }

    /**
     * Return checkout session instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Return sales quote instance for specified ID
     *
     * @param int $quoteId Quote identifier
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote($quoteId)
    {
        return Mage::getModel('sales/quote')->load($quoteId);
    }


    /**
     * Mark an order as canceled, store additional payment information and restore the user's cart.
     * 
     * @param object The order object.
     * @param array The data from the payment gateway's response.
     */
    public function markCanceledPayment($order, $responseData)
    {
        if ($order->getId()) {

            $helper = Mage::helper('cardlink_checkout');

            if ($helper->logDebugInfoEnabled()) {
                $helper->logMessage("Order {$order->getIncrementId()} was canceled.");
            }

            $payment = $order->getPayment();

            if ($responseData) {
                $payment->setCardlinkPayStatus($responseData[Cardlink_Checkout_Model_ApiFields::Status]);
                $payment->setCardlinkTxId($responseData[Cardlink_Checkout_Model_ApiFields::TransactionId]);
                $payment->setCardlinkPayMethod($responseData[Cardlink_Checkout_Model_ApiFields::PaymentMethod]);
                $payment->setCardlinkPayRef($responseData[Cardlink_Checkout_Model_ApiFields::PaymentReferenceId]);
            }
            $payment->save();

            $this->restoreQuote($order);
            $order->cancel()->save();
        }
    }
}