<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use WirecardEE\PaymentGateway\Service\SessionManager;

/**
 * @since 1.0.0
 * @codingStandardsIgnoreStart
 */
class WirecardEE_PaymentGateway_Model_Observer
{
    // @codingStandardsIgnoreEnd

    /**
     * @since 1.0.0
     */
    public function controllerFrontInitBefore()
    {
        $this->registerComposerAutoloader();
    }

    /**
     * Registers the composer autoloader.
     *
     * @since 1.0.0
     */
    private function registerComposerAutoloader()
    {
        $vendorPath = Mage::getBaseDir('lib') . '/WirecardEE/vendor/';

        if (! file_exists($vendorPath . 'autoload.php')) {
            throw new RuntimeException('Unable to include the Composer autoloader.');
        }

        /** @noinspection PhpIncludeInspection */
        require_once($vendorPath . 'autoload.php');
    }

    public function checkoutTypeOnepageSaveOrder(Varien_Event_Observer $observer)
    {
        //        var_dump(get_class(Mage::getSingleton("core/session",  array("name"=>"frontend"))));
        /** @var Mage_Sales_Model_Order $order */
        $additionalData = Mage::app()->getRequest()->getParam('wirecardElasticEngine');
        $sessionManager = new SessionManager(Mage::getSingleton("core/session",  array("name"=>"frontend")));
        $sessionManager->storePaymentData($additionalData);
        //        $session->setData("wirecardEEAdditionalData", $additionalData);
    }
}
