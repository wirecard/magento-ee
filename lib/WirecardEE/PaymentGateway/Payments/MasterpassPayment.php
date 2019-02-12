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
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Transaction\MasterpassTransaction;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardEE\PaymentGateway\Data\PaymentConfig;
use WirecardEE\PaymentGateway\Service\TransactionManager;

class MasterpassPayment extends Payment
{
    const NAME = MasterpassTransaction::NAME;

    /**
     * @var MasterpassTransaction
     */
    private $transactionInstance;

    /**
     * @return string
     *
     * @since 1.2.0
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return MasterpassTransaction
     *
     * @since 1.2.0
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
     * @param \Mage_Sales_Model_Order $order
     * @param string $operation
     * @param \Mage_Sales_Model_Order_Payment_Transaction $parentTransaction
     *
     * @return Transaction|null
     *
     * @since 1.2.0
     */
    public function getBackendTransaction(
        \Mage_Sales_Model_Order $order,
        $operation,
        \Mage_Sales_Model_Order_Payment_Transaction $parentTransaction
    ) {
        $transactionDetails = TransactionManager::getAdditionalInformationFromTransaction($parentTransaction);
        if (empty($transactionDetails[Transaction::PARAM_TRANSACTION_TYPE])
            || empty($transactionDetails['payment-methods.0.name'])) {
            return null;
        }

        if ($transactionDetails['payment-methods.0.name'] === MasterpassTransaction::NAME
            && in_array($transactionDetails[Transaction::PARAM_TRANSACTION_TYPE],
                [Transaction::TYPE_DEBIT, Transaction::TYPE_AUTHORIZATION])) {
            return null;
        }

        return new MasterpassTransaction();
    }

    /**
     * @param string $selectedCurrency
     *
     * @return Config
     *
     * @since 1.2.0
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

    /**
     * {@inheritdoc}
     */
    public function getRefundOperation()
    {
        return Operation::CANCEL;
    }
}
