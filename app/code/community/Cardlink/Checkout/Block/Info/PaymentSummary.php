<?php

/**
 * Block class that will provide the payment summary in the checkout page.
 * 
 * @author Cardlink S.A.
 */
class Cardlink_Checkout_Block_Info_PaymentSummary extends Mage_Payment_Block_Info
{
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }

        // Identify that the summary is displayed in the admin pages.
        $inAdminhtml = get_class($this->getParentBlock()) == 'Mage_Adminhtml_Block_Sales_Order_Payment';

        $helper = Mage::helper('cardlink_checkout');
        $data = array();
        $info = $this->getInfo();

        $customerId = 0;
        $order = $info->getOrder();
        $storedTokenId = $info->getCardlinkStoredToken();

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            $customerId = $customerData->getId();
        } else if ($order != null) {
            $customerId = $order->getCustomerId();
        }

        // If the payment transaction was executed, append the payment reference ID information in the payment summary.
        if ($inAdminhtml && $info->getCardlinkPayStatus()) {
            $data[$helper->__('Payment Status')] = $info->getCardlinkPayStatus();
        }

        // If the payment transaction was executed, append the payment method (card type) information in the payment summary.
        if ($inAdminhtml && $info->getCardlinkPayMethod()) {
            $data[$helper->__('Payment Method')] = strtoupper($info->getCardlinkPayMethod());
        }

        // If a valid customer was retrieved.
        if ($customerId != 0) {
            // If a card token was selected.
            if ($storedTokenId != 0) {
                // Retrieve the customer's stored card token.
                $storedTokenInfo = Mage::helper('cardlink_checkout/tokenization')->getCustomerStoredToken(
                    Mage::helper('cardlink_checkout')->getMerchantId(),
                    $customerId,
                    $storedTokenId
                );

                // If the token was successfully retrieved, append its base information for display in the payment summary.
                if ($storedTokenInfo != null) {
                    if ($inAdminhtml) {
                        $data[$helper->__('Card')] = $helper->__('xxxx-%s', $storedTokenInfo->lastDigits);
                    } else {
                        $data[$helper->__('Card')] = $helper->__('xxxx-%s (%s)', $storedTokenInfo->lastDigits, $storedTokenInfo->getFormattedExpiryDate());
                    }
                }
            }

            // If card tokenization was requested by the customer, append it in the payment summary.
            if (!$inAdminhtml && $info->getCardlinkTokenize()) {
                $data[$helper->__('Securely store card')] = $helper->__('Yes');
            }
        }

        // If the customer requested installments, append this information in the payment summary.
        if ($info->getCardlinkInstallments() > 1) {
            $data[$helper->__('Installments')] = $info->getCardlinkInstallments();
        }

        // If the payment transaction was executed, append the transaction ID information in the payment summary.
        if ($inAdminhtml && $info->getCardlinkTxId()) {
            $data[$helper->__('Transaction ID')] = $info->getCardlinkTxId();
        }

        // If the payment transaction was executed, append the payment reference ID information in the payment summary.
        if ($inAdminhtml && $info->getCardlinkPayRef()) {
            $data[$helper->__('Payment Reference')] = $info->getCardlinkPayRef();
        }

        $transport = parent::_prepareSpecificInformation($transport);

        return $transport->setData(array_merge($data, $transport->getData()));
    }
}
