<?php

/**
 * Helper class containing methods to handle the configured settings of the payment module.
 * 
 * @author Cardlink S.A.
 */
class Cardlink_Checkout_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_CONFIG_ENABLED = 'payment/cardlink_checkout/active';
    const XML_PATH_CONFIG_ORDER_STATUS = 'payment/cardlink_checkout/order_status';
    const XML_PATH_CONFIG_SHORT_DESCRIPTION = 'payment/cardlink_checkout/description';
    const XML_PATH_CONFIG_BUSINESS_PARTNER = 'payment/cardlink_checkout/business_partner';
    const XML_PATH_CONFIG_TRANSACTION_ENVIRONMENT = 'payment/cardlink_checkout/transaction_environment';
    const XML_PATH_CONFIG_MERCHANT_ID = 'payment/cardlink_checkout/merchant_id';
    const XML_PATH_CONFIG_SHARED_SECRET = 'payment/cardlink_checkout/shared_secret';
    const XML_PATH_CONFIG_TRANSACTION_TYPE = 'payment/cardlink_checkout/transaction_type';
    const XML_PATH_CONFIG_ACCEPT_INSTALLMENTS = 'payment/cardlink_checkout/accept_installments';
    const XML_PATH_CONFIG_MAX_INSTALLMENTS = 'payment/cardlink_checkout/max_installments';
    const XML_PATH_CONFIG_INSTALLMENTS_CONFIGURATION = 'payment/cardlink_checkout/installments_configuration';
    const XML_PATH_CONFIG_ALLOW_TOKENIZATION = 'payment/cardlink_checkout/allow_tokenization';
    const XML_PATH_CONFIG_FORCE_STORE_LANGUAGE = 'payment/cardlink_checkout/force_store_language';
    const XML_PATH_CONFIG_DISPLAY_PAYMENT_METHOD_LOGO = 'payment/cardlink_checkout/display_payment_method_logo';
    const XML_PATH_CONFIG_CHECKOUT_IN_IFRAME = 'payment/cardlink_checkout/checkout_in_iframe';
    const XML_PATH_CONFIG_CSS_URL = 'payment/cardlink_checkout/css_url';
    const XML_PATH_CONFIG_LOG_DEBUG_INFO = 'payment/cardlink_checkout/log_debug_info';

    const XML_PATH_CONFIG_IRIS_ENABLED = 'payment/cardlink_checkout_iris/active';
    const XML_PATH_CONFIG_IRIS_DIAS_CODE = 'payment/cardlink_checkout_iris/dias_code';
    const XML_PATH_CONFIG_IRIS_MERCHANT_ID = 'payment/cardlink_checkout_iris/merchant_id';
    const XML_PATH_CONFIG_IRIS_SHARED_SECRET = 'payment/cardlink_checkout_iris/shared_secret';
    const XML_PATH_CONFIG_IRIS_BUSINESS_PARTNER = 'payment/cardlink_checkout_iris/business_partner';
    const XML_PATH_CONFIG_IRIS_TRANSACTION_ENVIRONMENT = 'payment/cardlink_checkout_iris/transaction_environment';
    const XML_PATH_CONFIG_IRIS_SHORT_DESCRIPTION = 'payment/cardlink_checkout_iris/description';
    const XML_PATH_CONFIG_IRIS_DISPLAY_PAYMENT_METHOD_LOGO = 'payment/cardlink_checkout_iris/display_payment_method_logo';
    const XML_PATH_CONFIG_IRIS_CSS_URL = 'payment/cardlink_checkout_iris/css_url';
    /**
     * Returns the configured business partner.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_CONFIG_ENABLED);
    }

    /**
     * Returns the configured short description.
     *
     * @return string
     */
    public function getDescription()
    {
        return Mage::getStoreConfig(self::XML_PATH_CONFIG_SHORT_DESCRIPTION);
    }

    /**
     * Returns the configured business partner.
     *
     * @return string
     */
    public function getBusinessPartner()
    {
        $config = Mage::getStoreConfig(self::XML_PATH_CONFIG_BUSINESS_PARTNER);

        if (!$config) {
            return Cardlink_Checkout_Model_System_Config_Source_BusinessPartners::BUSINESS_PARTNER_CARDLINK;
        }

        return $config;
    }

    /**
     * Returns the configured order status after successful payment.
     *
     * @return string
     */
    public function getOrderStatus()
    {
        $config = Mage::getStoreConfig(self::XML_PATH_CONFIG_ORDER_STATUS);

        if (!$config) {
            return Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        }

        return $config;
    }

    /**
     * Returns the configured transaction environment (production/test).
     *
     * @return string
     */
    public function getTransactionEnvironment()
    {
        $config = Mage::getStoreConfig(self::XML_PATH_CONFIG_TRANSACTION_ENVIRONMENT);

        if (!$config) {
            return Cardlink_Checkout_Model_System_Config_Source_TransactionEnvironments::PRODUCTION_ENVIRONMENT;
        }

        return $config;
    }

    /**
     * Returns the configured transaction environment (production/test).
     *
     * @return string
     */
    public function getTransactionType()
    {
        $config = Mage::getStoreConfig(self::XML_PATH_CONFIG_TRANSACTION_TYPE);

        if (!$config) {
            return Cardlink_Checkout_Model_System_Config_Source_TransactionTypes::TRANSACTION_TYPE_CAPTURE;
        }

        return $config;
    }

    /**
     * Returns the configured merchant ID.
     *
     * @return string
     */
    public function getMerchantId()
    {
        return Mage::getStoreConfig(self::XML_PATH_CONFIG_MERCHANT_ID);
    }

    /**
     * Returns the configured shared secret code.
     *
     * @return string
     */
    public function getSharedSecret()
    {
        return Mage::getStoreConfig(self::XML_PATH_CONFIG_SHARED_SECRET);
    }

    /**
     * Determines whether the payment method will accept installments.
     *
     * @return bool
     */
    public function acceptsInstallments()
    {
        $config = Mage::getStoreConfig(self::XML_PATH_CONFIG_ACCEPT_INSTALLMENTS);

        if (
            !$config
            || $config == Cardlink_Checkout_Model_System_Config_Source_AcceptInstallments::NO_INSTALLMENTS
        ) {
            return false;
        }

        return true;
    }

    /**
     * Returns an array of configured amount ranges and maximum number of installments. 
     *
     * @return array
     */
    public function getInstallmentsConfiguration()
    {
        $ret = array();

        $config = Mage::getStoreConfig(self::XML_PATH_CONFIG_ACCEPT_INSTALLMENTS);

        if (!$config || $config == Cardlink_Checkout_Model_System_Config_Source_AcceptInstallments::NO_INSTALLMENTS) {
            // Return empty array to signify "no installments".
            return $ret;
        } else if ($config == Cardlink_Checkout_Model_System_Config_Source_AcceptInstallments::FIXED_INSTALLMENTS) {
            $maxInstallments = Mage::getStoreConfig(self::XML_PATH_CONFIG_MAX_INSTALLMENTS);

            $ret = array(
                array(
                    'start_amount' => 0,
                    'end_amount' => 0,
                    'max_installments' => max(1, $maxInstallments)
                )
            );
        } else if ($config == Cardlink_Checkout_Model_System_Config_Source_AcceptInstallments::BY_ORDER_AMOUNT) {
            // Retrieve and unserialize the configuration settings on the number of installments determined by order amount range.
            $config = Mage::getStoreConfig(self::XML_PATH_CONFIG_INSTALLMENTS_CONFIGURATION);

            if (isset($config)) {
                try {
                    $ret = Mage::helper('core/unserializeArray')->unserialize($config);
                } catch (Exception $exception) {
                    Mage::logException($exception);
                    $config = array(); // Return an array if failed to un-serialize data
                }
            }
        }

        return $ret;
    }

    /**
     * Determines whether the payment method allows tokenization of customer payment information.
     *
     * @return bool
     */
    public function allowsTokenization()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_CONFIG_ALLOW_TOKENIZATION);
    }

    /**
     * Determines whether the payment gateway must use the language of the store that the order was placed in.
     *
     * @return bool
     */
    public function getForceStoreLanguage()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_CONFIG_FORCE_STORE_LANGUAGE);
    }

    /**
     * Determines that the payment flow will be executed inside an IFRAME at the checkout page.
     *
     * @return bool
     */
    public function doCheckoutInIframe()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_CONFIG_CHECKOUT_IN_IFRAME);
    }

    /**
     * Determines that the Cardlink logo will be displayed next to the payment method title at the checkout page.
     *
     * @return bool
     */
    public function displayLogoInTitle()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_CONFIG_DISPLAY_PAYMENT_METHOD_LOGO);
    }

    /**
     * Returns the configured custom CSS URL for use in the Cardlink payment gateway's pages.
     *
     * @return string
     */
    public function getCssUrl()
    {
        return Mage::getStoreConfig(self::XML_PATH_CONFIG_CSS_URL);
    }

    /**
     * Identifies that the payment module should log debugging information. Use sparingly.
     *
     * @return bool
     */
    public function logDebugInfoEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_CONFIG_LOG_DEBUG_INFO);
    }

    /**
     * Adds data to the Cardlink log file.
     */
    public function logMessage($data, $level = null)
    {
        if (is_array($data) || is_object($data)) {
            $logMessage = json_encode($data, JSON_PRETTY_PRINT);
        } else {
            $logMessage = $data;
        }
        Mage::log($logMessage, $level, 'cardlink.log', true);
    }

    /**
     * Returns the configured DIAS code.
     * 
     * @return string
     */
    public function getDiasCode()
    {
        return trim(Mage::getStoreConfig(self::XML_PATH_CONFIG_IRIS_DIAS_CODE));
    }

    /**
     * Returns whether IRIS payment method is enabled.
     * 
     * @return string
     */
    public function isIrisEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_CONFIG_IRIS_ENABLED);
    }

    /**
     * Returns the configured business partner.
     *
     * @return string
     */
    public function getIrisBusinessPartner()
    {
        $config = Mage::getStoreConfig(self::XML_PATH_CONFIG_IRIS_BUSINESS_PARTNER);

        if (!$config) {
            return Cardlink_Checkout_Model_System_Config_Source_BusinessPartners::BUSINESS_PARTNER_CARDLINK;
        }

        return $config;
    }

    /**
     * Returns the configured transaction environment (production/test).
     *
     * @return string
     */
    public function getIrisTransactionEnvironment()
    {
        $config = Mage::getStoreConfig(self::XML_PATH_CONFIG_IRIS_TRANSACTION_ENVIRONMENT);

        if (!$config) {
            return Cardlink_Checkout_Model_System_Config_Source_TransactionEnvironments::PRODUCTION_ENVIRONMENT;
        }

        return $config;
    }

    /**
     * Returns the configured merchant ID.
     *
     * @return string
     */
    public function getIrisMerchantId()
    {
        return Mage::getStoreConfig(self::XML_PATH_CONFIG_IRIS_MERCHANT_ID);
    }

    /**
     * Returns the configured shared secret code.
     *
     * @return string
     */
    public function getIrisSharedSecret()
    {
        return Mage::getStoreConfig(self::XML_PATH_CONFIG_IRIS_SHARED_SECRET);
    }

    /**
     * Returns the configured shared secret code.
     *
     * @return string
     */
    public function getIrisDescription()
    {
        return Mage::getStoreConfig(self::XML_PATH_CONFIG_IRIS_SHORT_DESCRIPTION);
    }

    /**
     * Determines that the IRIS logo will be displayed next to the payment method title at the checkout page.
     *
     * @return bool
     */
    public function displayIrisLogoInTitle()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_CONFIG_IRIS_DISPLAY_PAYMENT_METHOD_LOGO);
    }

    /**
     * Returns the configured custom CSS URL for use in the Cardlink payment gateway's pages.
     *
     * @return string
     */
    public function getIrisCssUrl()
    {
        return Mage::getStoreConfig(self::XML_PATH_CONFIG_IRIS_CSS_URL);
    }

}