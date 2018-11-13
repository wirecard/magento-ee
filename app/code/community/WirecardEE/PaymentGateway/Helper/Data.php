<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

class WirecardEE_PaymentGateway_Helper_Data extends Mage_Payment_Helper_Data
{
    public function getPluginName()
    {
        return 'WirecardEE_PaymentGateway';
    }

    public function getPluginVersion()
    {
        $moduleConfig = $this->getModuleConfig()->asArray();
        if (!empty($moduleConfig['version'])) {
            return $moduleConfig['version'];
        }

        throw new \RuntimeException('Unable to determine plugin version');
    }

    /**
     * @return \Varien_Simplexml_Element
     */
    public function getModuleConfig()
    {
        /** @var \Varien_Simplexml_Element $config */
        $config = Mage::getConfig()->getModuleConfig($this->getPluginName());
        return $config;
    }
}
