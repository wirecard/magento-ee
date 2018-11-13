<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Payments;

use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use WirecardEE\PaymentGateway\Data\PaymentConfig;

class PaypalPayment extends Payment
{
    const NAME = PayPalTransaction::NAME;

    /**
     * @var PayPalTransaction
     */
    private $transactionInstance;

    public function getName()
    {
        return PayPalTransaction::NAME;
    }

    /**
     * @return PayPalTransaction
     *
     * @since 1.0.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new PayPalTransaction();
        }
        return $this->transactionInstance;
    }

    public function getTransactionConfig()
    {
        $config = parent::getTransactionConfig();
        $config->add(new PaymentMethodConfig(
            PayPalTransaction::NAME,
            $this->getPaymentConfig()->getTransactionMAID(),
            $this->getPaymentConfig()->getTransactionSecret()
        ));
        return $config;
    }

    public function getPaymentConfig()
    {
        $paymentConfig = new PaymentConfig(
            $this->getPluginConfig('api_url'),
            $this->getPluginConfig('api_user'),
            $this->getPluginConfig('api_password')
        );

        $paymentConfig->setTransactionMAID($this->getPluginConfig('api_maid'));
        $paymentConfig->setTransactionSecret($this->getPluginConfig('api_secret'));
        $paymentConfig->setTransactionOperation($this->getPluginConfig('transaction_type'));
        $paymentConfig->setSendBasket($this->getPluginConfig('send_basket'));
        $paymentConfig->setFraudPrevention($this->getPluginConfig('fraud_prevention'));
        $paymentConfig->setOrderIdentification($this->getPluginConfig('order_identification'));

        return $paymentConfig;
    }
}
