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
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardEE\PaymentGateway\Exception\UnknownTransactionTypeException;

/**
 * @since 1.0.0
 */
abstract class Payment implements PaymentInterface
{
    /**
     * @param $selectedCurrency
     *
     * @return Config
     *
     * @since 1.0.0
     */
    public function getTransactionConfig($selectedCurrency)
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
     *
     * @since 1.0.0
     */
    protected function getHelper()
    {
        return \Mage::helper('paymentgateway');
    }

    /**
     * @param string $name
     * @param string $prefix
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    protected function getPluginConfig($name, $prefix = 'payment/wirecardee_paymentgateway_')
    {
        $config = \Mage::getStoreConfig($prefix . $this->getName());
        return isset($config[$name]) ? $config[$name] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionType()
    {
        $operation = $this->getPaymentConfig()->getTransactionOperation();
        if ($operation === Operation::PAY) {
            return Transaction::TYPE_PURCHASE;
        }
        if ($operation === Operation::RESERVE) {
            return Transaction::TYPE_AUTHORIZATION;
        }
        throw new UnknownTransactionTypeException($operation);
    }
}