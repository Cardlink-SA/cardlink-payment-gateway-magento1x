<?php

/**
 * Class used to describe the available options of a select box Adminhtml field.
 * The described select box manages the configuration of the type of transaction to be performed by the Cardlink payment gateway.
 * 
 * @author Cardlink S.A.
 */
class Cardlink_Checkout_Model_System_Config_Source_TransactionTypes extends Cardlink_Checkout_Model_System_Config_Source_SelectBoxOptions_Abstract
{
    /**
     * Transactions are final. Funds are transferred to the merchant upon success.
     */
    const TRANSACTION_TYPE_CAPTURE = 'capture';
    /**
     * Transactions are authorized but funds are only transferred to the merchant using the payment gateway's merchant administration panel.
     */
    const TRANSACTION_TYPE_AUTHORIZE = 'authorize';

    protected $options = array(

        self::TRANSACTION_TYPE_CAPTURE => 'Finalize Payment',
        self::TRANSACTION_TYPE_AUTHORIZE => 'Authorize'
    );
}
