<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

class Wirecard_ElasticEngine_Model_Observer
{
    public function controllerFrontInitBefore()
    {
        $this->registerComposerAutoloader();
    }

    /**
     * Registers the composer autoloader.
     */
    private function registerComposerAutoloader()
    {
        $vendorPath = Mage::getBaseDir('lib') . '/Wirecard/ElasticEngine/vendor/';

        set_include_path(get_include_path() . PATH_SEPARATOR . $vendorPath);

        require_once($vendorPath . 'autoload.php');
    }
}
