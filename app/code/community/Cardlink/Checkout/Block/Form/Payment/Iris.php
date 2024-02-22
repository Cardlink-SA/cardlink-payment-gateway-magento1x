<?php

/**
 * Block class that will provide functionalities for the payment method in the checkout page.
 * 
 * @author Cardlink S.A.
 */
class Cardlink_Checkout_Block_Form_Payment_Iris extends Mage_Payment_Block_Form
{

  /**
   * Set the template that will be rendered for the payment method's options.
   */
  protected function _construct()
  {
    parent::_construct();
    $this->setTemplate('cardlink_checkout/form/payment_iris.phtml');
  }

  /**
   * Method used to provide HTML content that will be appended to the payment method's label.
   * 
   * @param Mage_Payment_Model_Method_Abstract $method
   * @return string Extra HTML content to be appended to the payment method's label.
   */
  public function getMethodLabelAfterHtml()
  {
    $helper = Mage::helper('cardlink_checkout');
    $ret = '';

    // Identify that the Cardlink logo will be appended to the payment method's label.
    if ($helper->displayLogoInTitle()) {
      $ret = '<img class="cardlink_checkout--payment-method-logo" src="' . $this->getSkinUrl('images/cardlink/iris.jpg', array('_secure' => true)) . '" alt="Cardlink Payment Gateway" />';
    }

    return $ret;
  }

  /**
   * Render block HTML
   *
   * @return string
   */
  protected function _toHtml()
  {

    return parent::_toHtml();
  }
}