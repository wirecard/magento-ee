<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

/**
 * @since 1.0.0
 * @codingStandardsIgnoreStart
 */
class WirecardEE_PaymentGateway_Helper_Data extends Mage_Payment_Helper_Data
{
    // @codingStandardsIgnoreEnd

    const DEVICE_FINGERPRINT_ID = 'WirecardEEDeviceFingerprint';

    /**
     * Returns the device fingerprint id from the session. In case no device fingerprint id was generated so far a new
     * one will get generated and returned instead.
     * Device fingerprint id format: md5 of [maid]_[microtime]
     *
     * @param string $maid
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getDeviceFingerprintId($maid)
    {
        if (! $this->getSession()->getData(self::DEVICE_FINGERPRINT_ID)) {
            $this->getSession()->setData(self::DEVICE_FINGERPRINT_ID, md5($maid . '_' . microtime()));
        }
        return $this->getSession()->getData(self::DEVICE_FINGERPRINT_ID);
    }

    /**
     * Removes the current finger print id from the session.
     *
     * @since 1.0.0
     */
    public function destroyDeviceFingerprintId()
    {
        if ($this->getSession()->getData(self::DEVICE_FINGERPRINT_ID)) {
            $this->getSession()->unsetData(self::DEVICE_FINGERPRINT_ID);
        }
    }

    /**
     * Validates the basket by comparing the order from the session against the order in database.
     *
     * @since 1.0.0
     */
    public function validateBasket()
    {
        $checkoutOrder = $this->getCheckoutSession()->getLastRealOrder();
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($checkoutOrder->getId());

        if (json_encode($checkoutOrder->getAllItems()) !== json_encode($order->getAllItems())) {
            $this->getLogger()->warning("Basket verification failed for order id: " . $checkoutOrder->getId());
            $order->addStatusHistoryComment('Basket verification failed', Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW);
        }
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
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

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getPluginName()
    {
        return 'WirecardEE_PaymentGateway';
    }

    /**
     * @return mixed
     *
     * @since 1.0.0
     */
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
     *
     * @since 1.0.0
     */
    public function getModuleConfig()
    {
        /** @var \Varien_Simplexml_Element $config */
        $config = Mage::getConfig()->getModuleConfig($this->getPluginName());
        return $config;
    }

    /**
     * @return Mage_Core_Model_Abstract|Mage_Core_Model_Session
     *
     * @since 1.0.0
     */
    protected function getSession()
    {
        return Mage::getSingleton('core/session');
    }

    /**
     * @return \Psr\Log\LoggerInterface
     *
     * @since 1.0.0
     */
    protected function getLogger()
    {
        return Mage::registry('logger');
    }

    /**
     * @return Mage_Checkout_Model_Session|Mage_Core_Model_Abstract
     *
     * @since 1.0.0
     */
    protected function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }
}
