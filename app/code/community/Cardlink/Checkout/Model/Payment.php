<?php

/**
 * Enumeration class for the payment gateway's transaction status response.
 * 
 * @author Cardlink S.A.
 */
class Cardlink_Checkout_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'cardlink_checkout';

    protected $_isInitializeNeeded      = true;
    protected $_canUseInternal          = false;
    protected $_canUseForMultishipping  = false;
    protected $_formBlockType = 'cardlink_checkout/form_payment';
    protected $_infoBlockType = 'cardlink_checkout/info_paymentSummary';

    /**
     * Method to assign data from the quote entity to the order payment entity.
     */
    public function assignData($data)
    {
        $grandTotal = Mage::getModel('checkout/cart')->getQuote()->getGrandTotal();

        $info = $this->getInfoInstance();

        $storedToken = 0;
        $tokenize = false;
        $installments = 0;

        // Require that the customer is logged in order to save new card tokens or use existing ones.
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $storedToken = $data->getCardlinkStoredToken();
            $tokenize = ($storedToken == 0 && $data->getCardlinkTokenize()) ? true : false;
        }

        // Retrieve number of installments requested by the customer.
        if ($data->getCardlinkInstallments()) {
            $maxInstallments = Mage::helper('cardlink_checkout/payment')->getMaxInstallments($grandTotal);
            $installments = max(0, min($maxInstallments, $data->getCardlinkInstallments()));
        }

        $info->setCardlinkStoredToken($storedToken);
        $info->setCardlinkTokenize($tokenize);

        // Only set installments if selected value is more than 1.
        $info->setCardlinkInstallments($installments > 1 ? $installments : 0);

        return $this;
    }

    /**
     * Method to validate payment method data.
     */
    public function validate()
    {
        parent::validate();
        $info = $this->getInfoInstance();

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
        return Mage::getUrl('cardlink_checkout/payment/redirect', array('_secure' => true));
    }
}
