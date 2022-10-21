<?php

/**
 * Class used to describe the available options of a select box Adminhtml field.
 * The described select box manages the configuration of the way that the module will handle installments.
 * 
 * @author Cardlink S.A.
 */
class Cardlink_Checkout_Model_System_Config_Source_AcceptInstallments extends Cardlink_Checkout_Model_System_Config_Source_SelectBoxOptions_Abstract
{
    /**
     * Installments are disabled.
     */
    const NO_INSTALLMENTS = 'no';
    /**
     * Installments are enabled and a fixed maximum number is provided by another configuration setting regardless of the order amount.
     */
    const FIXED_INSTALLMENTS = 'fixed';
    /**
     * Installments are enabled and a maximum number of installments is provided by another configuration setting respective to a set of order amount ranges.
     */
    const BY_ORDER_AMOUNT = 'order_amount';

    protected $options = array(
        self::NO_INSTALLMENTS => 'No Installments',
        self::FIXED_INSTALLMENTS => 'Fixed Maximum Number',
        self::BY_ORDER_AMOUNT  => 'Based on Order Amount'
    );
}
