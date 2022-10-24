<?php

/**
 * Observer class to execute a module version update check.
 * 
 * @author Cardlink S.A.
 */
class Cardlink_Checkout_Model_Observer
{

    public function checkVersionUpdate()
    {
        $versionHelper = Mage::helper("cardlink_checkout/version");
        $previouslyFoundVersion = $versionHelper->getLastSeenVersion();

        $publishedVersionData = $versionHelper->getLatestPublishedVersion();

        if ($publishedVersionData != null) {
            $hasNewVersion = version_compare($publishedVersionData['version'], $previouslyFoundVersion, '>');

            if ($hasNewVersion) {
                $severity = Mage_AdminNotification_Model_Inbox::SEVERITY_MAJOR;

                Mage::getModel('adminnotification/inbox')->add(
                    $severity,
                    "Cardlink Payment Gateway - New version {$publishedVersionData['version']} now available for download!",
                    $publishedVersionData['comment'],
                    Cardlink_Checkout_Helper_Version::VERSION_DOWNLOAD_URL,
                    false
                );
                $versionHelper->setLastSeenVersion($publishedVersionData['version']);
            }
        }
    }
}
