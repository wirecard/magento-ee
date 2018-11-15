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
    const DEVICE_FINGERPRINT_ID = 'WirecardEEDeviceFingerprint';

    public function getDeviceFingerprintId($maid)
    {
        if (! $this->getSession()->getData(self::DEVICE_FINGERPRINT_ID)) {
            $this->getSession()->setData(self::DEVICE_FINGERPRINT_ID, md5($maid . '_' . microtime()));
        }
        return $this->getSession()->getData(self::DEVICE_FINGERPRINT_ID);
    }

    public function destroyDeviceFingerprintId()
    {
        if ($this->getSession()->getData(self::DEVICE_FINGERPRINT_ID)) {
            $this->getSession()->unsetData(self::DEVICE_FINGERPRINT_ID);
        }
    }

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

    protected function getSession()
    {
        return Mage::getSingleton('core/session');
    }
}
