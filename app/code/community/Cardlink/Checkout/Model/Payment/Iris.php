<?php

/**
 * Enumeration class for the payment gateway's transaction status response.
 * 
 * @author Cardlink S.A.
 */
class Cardlink_Checkout_Model_Payment_Iris extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'cardlink_checkout_iris';

    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;
    protected $_formBlockType = 'cardlink_checkout/form_payment_iris';
    protected $_infoBlockType = 'cardlink_checkout/info_paymentSummary';

    /**
     * Method to assign data from the quote entity to the order payment entity.
     */
    public function assignData($data)
    {
        return $this;
    }

    /**
     * Method to validate payment method data.
     */
    public function validate()
    {
        parent::validate();
        return $this;
    }

    /**
     * Return the Order Place redirect URL.
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        // When customers click on the place order button they will be redirected on this URL that will handle all payment gateway transaction request functions.
        return Mage::getUrl('cardlink_checkout/payment/redirect', array('_secure' => true, '_query' => 'payment_method=IRIS'));
    }

    private function isNullOrWhitespace($str)
    {
        return is_null($str) || trim($str) === '';
    }

    public function isAvailable($quote = null)
    {
        $isActive = $this->_code && Mage::getStoreConfigFlag('payment/' . $this->_code . '/active', $quote ? $quote->getStoreId() : null);

        if ($isActive) {
            $mid = Mage::getStoreConfig('payment/' . $this->_code . '/merchant_id', $quote ? $quote->getStoreId() : null);
            $sharedSecret = Mage::getStoreConfig('payment/' . $this->_code . '/shared_secret', $quote ? $quote->getStoreId() : null);
            $sellerId = Mage::getStoreConfig('payment/' . $this->_code . '/dias_code', $quote ? $quote->getStoreId() : null);

            if (!self::isNullOrWhitespace($mid) && !self::isNullOrWhitespace($sharedSecret) && !self::isNullOrWhitespace($sellerId)) {
                return true;
            }
        }
        return false;
    }
}