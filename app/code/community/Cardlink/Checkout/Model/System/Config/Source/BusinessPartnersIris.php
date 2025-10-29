<?php

/**
 * Class used to describe the available options of a select box Adminhtml field.
 * The described select box manages the configuration of the business partner that will perform the actual financial transactions.
 * 
 * @author Cardlink S.A.
 */
class Cardlink_Checkout_Model_System_Config_Source_BusinessPartnersIris extends Cardlink_Checkout_Model_System_Config_Source_SelectBoxOptions_Abstract
{
    const BUSINESS_PARTNER_CARDLINK = 'cardlink';
    const BUSINESS_PARTNER_NEXI = 'nexi';
    const BUSINESS_PARTNER_WORLDLINE = 'worldline';

    protected $options = array(
        self::BUSINESS_PARTNER_CARDLINK => 'Cardlink',
        self::BUSINESS_PARTNER_NEXI  => 'Nexi',
        self::BUSINESS_PARTNER_WORLDLINE => 'Worldline'
    );
}
