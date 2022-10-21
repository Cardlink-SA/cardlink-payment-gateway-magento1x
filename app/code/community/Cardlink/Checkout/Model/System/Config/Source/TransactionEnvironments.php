<?php

/**
 * Class used to describe the available options of a select box Adminhtml field.
 * The described select box manages the configuration of the working transaction environment.
 * 
 * @author Cardlink S.A.
 */
class Cardlink_Checkout_Model_System_Config_Source_TransactionEnvironments extends Cardlink_Checkout_Model_System_Config_Source_SelectBoxOptions_Abstract
{
    /**
     * All transactions happen in the real world through actual financial institutes.
     */
    const PRODUCTION_ENVIRONMENT = 'production';
    /**
     * All transaction are performed in a development/sandbox environment for testing purposes.
     */
    const SANDBOX_ENVIRONMENT = 'sandbox';

    protected $options = array(
        self::PRODUCTION_ENVIRONMENT => 'Production',
        self::SANDBOX_ENVIRONMENT  => 'Sandbox'
    );
}
