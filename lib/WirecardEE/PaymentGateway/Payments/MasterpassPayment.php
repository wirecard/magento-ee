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
use Wirecard\PaymentSdk\Transaction\MasterpassTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardEE\PaymentGateway\Data\PaymentConfig;

class MasterpassPayment extends Payment
{
    const NAME = MasterpassTransaction::NAME;

    /**
     * @var MasterpassTransaction
     */
    private $transactionInstance;

    /**
     * @return string
     * @since 1.1.0
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return MasterpassTransaction
     *
     * @since 1.1.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new MasterpassTransaction();
        }
        return $this->transactionInstance;
    }

    /**
     * If paymentMethod is 'masterpass' and transaction type is 'debit' or 'authorization',
     * no backend operation is allowed.
     *
     * @param \Mage_Sales_Model_Order                     $order
     * @param string                                      $operation
     * @param \Mage_Sales_Model_Order_Payment_Transaction $parentTransaction
     *
     * @return Transaction|null
     *
     * @since 1.1.0
     */
    public function getBackendTransaction(
        \Mage_Sales_Model_Order $order,
        $operation,
        \Mage_Sales_Model_Order_Payment_Transaction $parentTransaction
    ) {
        $transactionDetails = $parentTransaction->getAdditionalInformation(
            \Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS
        );

        if (array_key_exists(Transaction::PARAM_TRANSACTION_TYPE, $transactionType)) {
            $transactionType = $transactionDetails[Transaction::PARAM_TRANSACTION_TYPE];

            if ($transactionType === Transaction::TYPE_DEBIT || $transactionType === Transaction::TYPE_AUTHORIZATION) {
                return null;
            }
        }

        return new MasterpassTransaction();
    }

    /**
     * @param $selectedCurrency
     *
     * @return Config
     *
     * @since 1.1.0
     */
    public function getTransactionConfig($selectedCurrency)
    {
        $config = parent::getTransactionConfig($selectedCurrency);
        $config->add(new PaymentMethodConfig(
            MasterpassTransaction::NAME,
            $this->getPaymentConfig()->getTransactionMAID(),
            $this->getPaymentConfig()->getTransactionSecret()
        ));

        return $config;
    }

    /**
     * {@inheritdoc}
     */
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
        $paymentConfig->setFraudPrevention($this->getPluginConfig('fraud_prevention'));

        return $paymentConfig;
    }
}
