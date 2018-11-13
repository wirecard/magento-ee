<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Payments;

use Wirecard\PaymentSdk\Config\Config;

abstract class Payment implements PaymentInterface
{
    public function getTransactionConfig()
    {
        $config = new Config(
            $this->getPaymentConfig()->getBaseUrl(),
            $this->getPaymentConfig()->getHttpUser(),
            $this->getPaymentConfig()->getHttpPassword()
        );

        $config->setShopInfo(
            'Magento CE',
            \Mage::getVersion()
        );

        $config->setPluginInfo($this->getHelper()->getPluginName(), $this->getHelper()->getPluginName());

        return $config;
    }

    /**
     * @return \Mage_Core_Helper_Abstract|\WirecardEE_PaymentGateway_Helper_Data
     */
    protected function getHelper()
    {
        return \Mage::helper('paymentgateway');
    }

    protected function getPluginConfig($name, $prefix = 'payment/wirecardee_paymentgateway_')
    {
        $config = \Mage::getStoreConfig($prefix . $this->getName());
        return isset($config[$name]) ? $config[$name] : null;
    }
}
