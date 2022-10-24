<?php

/**
 * Helper class containing methods to handle version management functions of the payment module.
 * 
 * @author Cardlink S.A.
 */
class Cardlink_Checkout_Helper_Version extends Mage_Core_Helper_Abstract
{
    /**
     * The URL of the payment module's Git repository.
     */
    const VERSION_DOWNLOAD_URL = 'https://github.com/Cardlink-SA/cardlink-checkout-magento-v1';

    /**
     * The URL of the payment module's config.xml file in the Git repository.
     */
    const PUBLISHED_MODULE_CONFIGURATION_XML_URL = 'https://raw.githubusercontent.com/Cardlink-SA/cardlink-checkout-magento-v1/main/app/code/community/Cardlink/Checkout/etc/config.xml';
    const XML_PATH_LAST_SEEN_VERSION = 'payment/cardlink_checkout/last_seen_version';

    /**
     * Get the last seen module version stored in the database.
     * 
     * @return array
     */
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

    /**
     * Sets the last seen module version stored in the database.
     * 
     * @param string $version The string representation of the module's version.
     * @return void
     */
    public function setLastSeenVersion($version)
    {
        Mage::getConfig()
            ->saveConfig(self::XML_PATH_LAST_SEEN_VERSION, $version)
            ->cleanCache();
        Mage::app()->reinitStores();
    }

    /**
     * Gets the currently installed module version.
     * 
     * @return string $version The string representation of the module's version.
     */
    public function getCurrentlyInstalledVersion()
    {
        $modules = (array)  Mage::getConfig()->getNode('modules')->children();
        return $modules['Cardlink_Checkout']->version;
    }

    /**
     * Retrieve the currently published version of the module from the Git repository.
     * 
     * @return array|null
     */
    public function getLatestPublishedVersion()
    {
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
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            $contentConfigXmlFile = curl_exec($ch);

            if ($contentConfigXmlFile != false) {
                $xml = simplexml_load_string($contentConfigXmlFile);
                $modulesConfig = json_decode(json_encode($xml), TRUE);

                return array(
                    'version' => $modulesConfig['modules']['Cardlink_Checkout']['version'],
                    'comment' => $modulesConfig['modules']['Cardlink_Checkout']['comment']
                );
            }
        } catch (Exception $exception) {
            Mage::logException($exception);
        }
        return null;
    }
}
