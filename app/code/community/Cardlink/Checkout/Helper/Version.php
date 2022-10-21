<?php

/**
 * Helper class containing methods to handle the configured settings of the payment module.
 * 
 * @author Cardlink S.A.
 */
class Cardlink_Checkout_Helper_Version extends Mage_Core_Helper_Abstract
{
    const VERSION_DOWNLOAD_URL = 'https://github.com/Cardlink-SA/magento1-cardlink-payment-gateway/';
    const PUBLISHED_MODULE_CONFIGURATION_XML_URL = '';
    const XML_PATH_LAST_SEEN_VERSION = 'payment/cardlink_checkout/last_seen_version';

    public function getLastSeenVersion()
    {
        // Retrieve last seen module version from the config data store.
        $version = Mage::getStoreConfig(self::XML_PATH_LAST_SEEN_VERSION);

        // If no version already stored, return current module version.
        if (!isset($version) || $version == '') {
            $version = self::getCurrentlyInstalledVersion();
        }

        return $version;
    }

    public function setLastSeenVersion($version)
    {
        Mage::getConfig()
            ->saveConfig(self::XML_PATH_LAST_SEEN_VERSION, $version)
            ->cleanCache();
        Mage::app()->reinitStores();
    }

    public function getCurrentlyInstalledVersion()
    {
        $modules = (array)  Mage::getConfig()->getNode('modules')->children();
        return $modules['Cardlink_Checkout']->version;
    }

    public function getLatestPublishedVersion()
    {
		return null;
		
        try {
            if (self::PUBLISHED_MODULE_CONFIGURATION_XML_URL == '') {
                return array(
                    'version' => self::getCurrentlyInstalledVersion(),
                    'comment' => ''
                );
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::PUBLISHED_MODULE_CONFIGURATION_XML_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);

            $contentConfigXmlFile = curl_exec($ch);

            $xml = simplexml_load_string($contentConfigXmlFile);
            $modulesConfig = json_decode(json_encode($xml), TRUE);

            return array(
                'version' => $modulesConfig['modules']['Cardlink_Checkout']['version'],
                'comment' => $modulesConfig['modules']['Cardlink_Checkout']['comment']
            );
        } catch (Exception $exception) {
            Mage::logException($exception);
            return null;
        }
    }
}
