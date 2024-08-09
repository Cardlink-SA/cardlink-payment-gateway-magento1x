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

    public function isAvailable($quote = null)
    {
        return true;
    }
}