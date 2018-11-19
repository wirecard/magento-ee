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

    public function validateBasket()
    {
        $checkoutOrder = $this->getCheckoutSession()->getLastRealOrder();
        $order         = Mage::getModel('sales/order')->load($checkoutOrder->getId());

        if (json_encode($checkoutOrder->getAllItems()) !== json_encode($order->getAllItems())) {
            $this->getLogger()->warning("Basket validation failed for order id: " . $checkoutOrder->getId());
            $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW);
        }
    }

    public function getClientIp()
    {
        $server = Mage::app()->getRequest()->getServer();
        if (! empty($server['HTTP_CLIENT_IP'])) {
            return $server['HTTP_CLIENT_IP'];
        }

        if (! empty($server['HTTP_X_FORWARDED_FOR'])) {
            $ips = $server['HTTP_X_FORWARDED_FOR'];
            return trim($ips[count($ips) - 1]);
        }

        return $server['REMOTE_ADDR'];
    }

    public function getPluginName()
    {
        return 'WirecardEE_PaymentGateway';
    }

    public function getPluginVersion()
    {
        $moduleConfig = $this->getModuleConfig()->asArray();
        if (! empty($moduleConfig['version'])) {
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

    /**
     * @return \Psr\Log\LoggerInterface
     */
    protected function getLogger()
    {
        return Mage::registry('logger');
    }

    protected function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }
}
