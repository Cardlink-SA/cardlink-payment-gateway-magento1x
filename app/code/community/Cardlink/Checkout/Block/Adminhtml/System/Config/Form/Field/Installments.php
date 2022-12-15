<?php

/**
 * Block class that describes the fields of a Adminhtml form field array compound input control.
 * 
 * @author Cardlink S.A.
 */
class Cardlink_Checkout_Block_Adminhtml_System_Config_Form_Field_Installments extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    /**
     * Add custom config field columns, set template, add values.
     */
    public function  __construct()
    {
        $helper = Mage::helper('cardlink_checkout');

        // The start amount of the order amount range.
        $this->addColumn('start_amount', array(
            'name' => 'start_amount',
            'class' => 'required-entry validate-not-negative-number',
            'required' => true,
            'style' => 'width:80px',
            'label' => $helper->__('Start Amount'),
        ));

        // The end amount of the order amount range. If value is zero, then no maximum value is considered (i.e. infinity).
        $this->addColumn('end_amount', array(
            'name' => 'end_amount',
            'class' => 'required-entry validate-not-negative-number',
            'required' => true,
            'style' => 'width:80px',
            'label' => $helper->__('End Amount'),
        ));

        // The maximum number of installments that the order amount range will allow the customer to select on the checkout page.
        $this->addColumn('max_installments', array(
            'name' => 'max_installments',
            'class' => 'required-entry validate-not-negative-number  validate-digits-range digits-range-0-60',
            'required' => true,
            'style' => 'width:80px',
            'label' => $helper->__('Maximum Installments'),
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = $helper->__('Add Range');
        parent::__construct();
    }

    /**
     * Render block HTML. Enclose the actual control's HTML code in a div in order to make field dependencies work 
     * (toggle control visibility according to the value of another control.
     *
     * @return string
     */
    protected function _toHtml()
    {
        // Wrap around a div with the proper id value to make dependencies on other fields work.
        return '<div id="' . $this->getElement()->getId() . '">' . parent::_toHtml() . '</div>';
    }
}
