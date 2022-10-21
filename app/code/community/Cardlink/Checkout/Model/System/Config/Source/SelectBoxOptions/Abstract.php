<?php

/**
 * Abstract class used to provide methods that describe the available options of a select box Adminhtml field.
 * The actual options are provided by the classes that extend this class.
 * 
 * @author Cardlink S.A.
 */
abstract class Cardlink_Checkout_Model_System_Config_Source_SelectBoxOptions_Abstract
{
    protected $options;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $optionsArray = array();

        foreach ($this->options as $key => $value) {
            $optionsArray[] = array('value' => $key, 'label' => Mage::helper('cardlink_checkout')->__($value));
        }

        return $optionsArray;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $optionsArray = array();

        foreach ($this->options as $key => $value) {
            $optionsArray[$key] = Mage::helper('cardlink_checkout')->__($value);
        }

        return $optionsArray;
    }
}
