<?php

/**
 * Controller class to handle payment and token related functions.
 * 
 * @author Cardlink S.A.
 */
class Cardlink_Checkout_PaymentController extends Mage_Core_Controller_Front_Action
{
    /**
     * Only handle actions if the Cardlink payment method is enabled.
     */
    public function preDispatch()
    {
        parent::preDispatch();

        if (!Mage::helper('cardlink_checkout')->isEnabled()) {
            $this->norouteAction();
        }
    }

    /**
     * Action that will perform data processing and redirect the customer to the payment gateway according to Cardlink's Redirect API documentation.
     */
    public function redirectAction()
    {
        $helper = Mage::helper('cardlink_checkout');

        // Retrieve the order ID.
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();

        // Retrieve the order and compile the API request data array.
        $formData = Mage::helper('cardlink_checkout/payment')->getFormDataForOrder($orderId);

        if ($formData !== false) {
            $this->loadLayout();

            // Load the template that will render the payment gateway redirect form.
            $block = Mage::app()->getLayout()->getBlock('root');
            if ($block) {
                //check if block actually exists

                $block->setPaymentGatewayUrl($formData['postUrl']);
                unset($formData['postUrl']);
                $block->setFormData($formData);
            }
            $this->renderLayout();
        } else {
            // Problem found with the data. Redirect the user to the checkout failure page.
            Mage::getSingleton('core/session')->addError('Invalid payment gateway data');
            @session_write_close();

            if ($helper->logDebugInfoEnabled()) {
                $helper->logMessage("Invalid payment gateway data for order {$orderId}");
            }

            $this->_redirect('checkout/onepage/failure', array('_secure' => true));
        }
    }

    /**
     * Action to handle payment gateway's response data after a successful or failed transaction.
     */
    public function responseAction()
    {
        $orderId = 0;
        $success = false;
        $responseData = $this->getRequest()->getParams();

        // Verify that the response is coming from the payment gateway.
        $isValidPaymentGatewayResponse = Mage::helper('cardlink_checkout/payment')->validateResponseData(
            $responseData,
            Mage::helper('cardlink_checkout')->getSharedSecret()
        );

        $isValidXlsBonusPaymentGatewayResponse = true;

        // If performing a Bonus transaction, validate the xlsbonusdigest field
        if (array_key_exists(Cardlink_Checkout_Model_ApiFields::XlsBonusDigest, $responseData)) {
            $isValidXlsBonusPaymentGatewayResponse = Mage::helper('cardlink_checkout/payment')->validateXlsBonusResponseData(
                $responseData,
                Mage::helper('cardlink_checkout')->getSharedSecret()
            );
        }

        $message = null;

        if ($isValidPaymentGatewayResponse && $isValidXlsBonusPaymentGatewayResponse) {

            if (Mage::helper('cardlink_checkout')->logDebugInfoEnabled()) {
                Mage::log("Received valid payment gateway response", null, 'cardlink.log', true);
                Mage::log(json_encode($responseData, JSON_PRETTY_PRINT), null, 'cardlink.log', true);
            }

            // If the response identifies the transaction as either AUTHORIZED or CAPTURED.
            if (
                $responseData[Cardlink_Checkout_Model_ApiFields::Status] === Cardlink_Checkout_Model_PaymentStatus::AUTHORIZED
                || $responseData[Cardlink_Checkout_Model_ApiFields::Status] === Cardlink_Checkout_Model_PaymentStatus::CAPTURED
            ) {
                // Mark the payment as successful.
                $orderId = $responseData[Cardlink_Checkout_Model_ApiFields::OrderId];
                $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

                self::markSuccessfulPayment($order, $responseData);
                Mage::getSingleton('checkout/session')->unsQuoteId();

                if (array_key_exists(Cardlink_Checkout_Model_ApiFields::Message, $responseData)) {
                    $message = $responseData[Cardlink_Checkout_Model_ApiFields::Message];
                }
                $success = true;
            } else if (
                $responseData[Cardlink_Checkout_Model_ApiFields::Status] === Cardlink_Checkout_Model_PaymentStatus::CANCELED
                || $responseData[Cardlink_Checkout_Model_ApiFields::Status] === Cardlink_Checkout_Model_PaymentStatus::REFUSED
                || $responseData[Cardlink_Checkout_Model_ApiFields::Status] === Cardlink_Checkout_Model_PaymentStatus::ERROR
            ) {
                // Cancel order and revert cart contents.
                $session = Mage::getSingleton('checkout/session');
                $orderId = $responseData[Cardlink_Checkout_Model_ApiFields::OrderId];

                if ($orderId) {
                    $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
                    $session->setQuoteId($order->getQuoteId());

                    self::markCanceledPayment($order, $responseData);
                }

                // If the response identifies the transaction as either CANCELED, REFUSED or ERROR add an error message.
                if (array_key_exists(Cardlink_Checkout_Model_ApiFields::Message, $responseData)) {
                    $message = $responseData[Cardlink_Checkout_Model_ApiFields::Message];
                } else {
                    $message = 'The payment was canceled by you or declined by the bank. Your order has been canceled.';
                }
                Mage::getSingleton('core/session')->addError(__($message));
                @session_write_close();
            }
        } else {
            $this->_redirect('checkout/cart', array('_secure' => true));
            return;
        }

        // If the payment flow executed inside the IFRAME, send out a redirection form page to force open the final response page in the parent frame (store window/tab).
        if (Mage::helper('cardlink_checkout')->doCheckoutInIframe()) {
            $this->loadLayout();

            $block = Mage::app()->getLayout()->getBlock('root');
            if ($block) {
                $redirectUrl = $success
                    ? Mage::getUrl('checkout/onepage/success', array('_secure' => true))
                    : Mage::getUrl('checkout/onepage/failure', array('_secure' => true));

                $block->setRedirectUrl($redirectUrl);
                if (isset($message)) {
                    $block->setMessage(__($message));
                }
                $block->setOrderId($orderId);
            }
            $this->renderLayout();
        } else {
            if ($success) {
                $this->_redirect('checkout/onepage/success', array('_secure' => true));
                return;
            } else {
                $this->_redirect('checkout/onepage/failure', array('_secure' => true));
            }
        }
    }

    /**
     * Mark an order as paid, store additional payment information and handle customer's card tokenization request.
     * 
     * @param object The order object.
     * @param array The data from the payment gateway's response.
     */
    public function markSuccessfulPayment($order, $responseData)
    {
        if ($order->getId()) {
            $helper = Mage::helper('cardlink_checkout');

            $charge = $responseData[Cardlink_Checkout_Model_ApiFields::OrderAmount];

            if ($helper->logDebugInfoEnabled()) {
                $helper->logMessage("Setting state of order {$order->getIncrementId()} to 'Payment Review'.");
            }

            $order->setState(
                Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW,
                true,
                "Payment Success - " . strtoupper($responseData[Cardlink_Checkout_Model_ApiFields::PaymentMethod]) . " " . $responseData[Cardlink_Checkout_Model_ApiFields::Status]
            );
            $order->save();

            $payment = $order->getPayment();
            $payment->setCardlinkPayStatus($responseData[Cardlink_Checkout_Model_ApiFields::Status]);
            $payment->setCardlinkTxId($responseData[Cardlink_Checkout_Model_ApiFields::TransactionId]);
            $payment->setCardlinkPayMethod($responseData[Cardlink_Checkout_Model_ApiFields::PaymentMethod]);
            $payment->setCardlinkPayRef($responseData[Cardlink_Checkout_Model_ApiFields::PaymentReferenceId]);
            $payment->save();

            if ($helper->logDebugInfoEnabled()) {
                $helper->logMessage("Setting payment gateway information to payment object {$payment->getId()} (order {$order->getIncrementId()}).");
            }

            // Create invoice for order payment if transaction status was CAPTURED.
            if ($responseData[Cardlink_Checkout_Model_ApiFields::Status] == Cardlink_Checkout_Model_PaymentStatus::CAPTURED) {

                if ($helper->logDebugInfoEnabled()) {
                    $helper->logMessage("Payment was captured for order {$order->getIncrementId()}.");
                    $helper->logMessage("Setting state of order {$order->getIncrementId()} to 'Processing'.");
                }

                $order->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING)
                    ->setState(
                        Mage_Sales_Model_Order::STATE_PROCESSING,
                        true,
                        "Captured Transaction ID: " . $responseData[Cardlink_Checkout_Model_ApiFields::TransactionId]
                    );
                $order->save();

                if ($order->canInvoice()) {
                    $order->getPayment()->setSkipTransactionCreation(false);
                    $invoice = $order->prepareInvoice();
                    $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                    $invoice->register();
                    $invoice->save();

                    if ($helper->logDebugInfoEnabled()) {
                        $helper->logMessage("Created invoice {$invoice->getIncrementId()} for order {$order->getIncrementId()}.");
                    }
                }

                if ($order->hasInvoices()) {
                    $order->setBaseTotalInvoiced($charge);
                    $order->setTotalInvoiced($charge);
                    //$order->setBaseTotalPaid($charge);
                    $order->setTotalPaid($charge);

                    if ($helper->logDebugInfoEnabled()) {
                        $helper->logMessage("Setting total invoiced amount for order {$order->getIncrementId()} to {$charge}.");
                    }

                    foreach ($order->getInvoiceCollection() as $orderInvoice) {
                        $orderInvoice->setState(Mage_Sales_Model_Order_Invoice::STATE_PAID)
                            ->setTransactionId($responseData[Cardlink_Checkout_Model_ApiFields::TransactionId])
                            ->setBaseGrandTotal($charge)
                            ->setGrandTotal($charge)
                            ->save();
                    }
                }
            }

            try {
                $order->sendNewOrderEmail();
            } catch (Exception $e) {

            }

            // If the user asked for card tokenization.
            if (
                Mage::helper('cardlink_checkout')->allowsTokenization()
                && $payment->getCardlinkTokenize()
                && $payment->getCardlinkStoredToken() == '0'
            ) {
                if (array_key_exists(Cardlink_Checkout_Model_ApiFields::ExtToken, $responseData)) {
                    $merchantId = Mage::helper('cardlink_checkout')->getMerchantId();
                    $customerId = $order->getCustomerId();

                    if ($helper->logDebugInfoEnabled()) {
                        $helper->logMessage("Storing token {$responseData[Cardlink_Checkout_Model_ApiFields::PaymentMethod]}/{$responseData[Cardlink_Checkout_Model_ApiFields::ExtTokenPanEnd]} for customer {$customerId} of merchant {$merchantId}.");
                    }

                    // Store the tokenized card information.
                    Mage::helper('cardlink_checkout/tokenization')->storeTokenForCustomer(
                        $merchantId,
                        $customerId,
                        $responseData[Cardlink_Checkout_Model_ApiFields::ExtToken],
                        $responseData[Cardlink_Checkout_Model_ApiFields::PaymentMethod],
                        $responseData[Cardlink_Checkout_Model_ApiFields::ExtTokenPanEnd],
                        $responseData[Cardlink_Checkout_Model_ApiFields::ExtTokenExpiration]
                    );
                }
            }
        }
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

            $order->cancel()->save();

            if ($helper->logDebugInfoEnabled()) {
                $helper->logMessage("Order {$order->getIncrementId()} was canceled.");
            }

            $payment = $order->getPayment();
            $payment->setCardlinkPayStatus($responseData[Cardlink_Checkout_Model_ApiFields::Status]);
            $payment->setCardlinkTxId($responseData[Cardlink_Checkout_Model_ApiFields::TransactionId]);
            $payment->setCardlinkPayMethod($responseData[Cardlink_Checkout_Model_ApiFields::PaymentMethod]);
            $payment->setCardlinkPayRef($responseData[Cardlink_Checkout_Model_ApiFields::PaymentReferenceId]);
            $payment->save();

            Mage::helper('cardlink_checkout/payment')->restoreQuote($order);
        }
    }

    /**
     * Action that will handle the Ajax requests of customers wishing to remove/invalidate one of their stored card tokens.
     */
    public function removeTokenAction()
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $helper = Mage::helper('cardlink_checkout');

            $customer_data = Mage::getSingleton('customer/session')->getCustomer();

            // Retrieve the token's entity ID from the HTTP request query.
            if ($this->getRequest()->get('tokenId')) {
                $tokenId = $this->getRequest()->get('tokenId');
                $customerId = $customer_data->getEntityId();
                $merchantId = Mage::helper('cardlink_checkout')->getMerchantId();

                // Call the token invalidation helper method.
                $result = Mage::helper('cardlink_checkout/tokenization')->invalidateCustomerStoredToken(
                    $merchantId,
                    $customerId,
                    $tokenId
                );

                // If the customer's token was found and successfully invalidated.
                if ($result) {

                    if ($helper->logDebugInfoEnabled()) {
                        $helper->logMessage("Stored token {$tokenId} for customer {$customerId} was successfully invalidated.");
                    }

                    $this->getResponse()
                        ->clearHeaders()
                        ->setHttpResponseCode(200)
                        ->setHeader('Content-type', 'application/json', true)
                        ->setBody(json_encode(array('status' => 'Ok')));
                } else {

                    if ($helper->logDebugInfoEnabled()) {
                        $helper->logMessage("Stored token {$tokenId} for customer {$customerId} failed to be invalidated.");
                    }

                    // If no token was found by that token ID or it didn't belong to the logged in customer.
                    $this->getResponse()
                        ->clearHeaders()
                        ->setHttpResponseCode(403)
                        ->setHeader('Content-type', 'application/json', true)
                        ->setBody(json_encode(array('status' => 'Forbidden')));
                }
            } else {

                if ($helper->logDebugInfoEnabled()) {
                    $helper->logMessage("Failed to retrieve token to invalidate. No valid token ID was received.");
                }

                // If no token ID was successfully retrieved, return a 404 error.
                $this->getResponse()
                    ->clearHeaders()
                    ->setHttpResponseCode(404)
                    ->setHeader('Content-type', 'application/json', true)
                    ->setBody(json_encode(array('status' => 'Invalid data')));
            }
        }
    }
}