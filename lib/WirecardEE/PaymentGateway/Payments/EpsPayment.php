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
use Wirecard\PaymentSdk\Entity\BankAccount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\EpsTransaction;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\Action;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\SepaCreditTransferPaymentConfig;
use WirecardEE\PaymentGateway\Payments\Contracts\CustomFormTemplate;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessPaymentInterface;

class EpsPayment extends Payment implements ProcessPaymentInterface, CustomFormTemplate
{
    const NAME = EpsTransaction::NAME;

    /**
     * @var EpsTransaction
     */
    private $transactionInstance;

    /**
     * @return string
     *
     * @since 1.1.0
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return EpsTransaction
     *
     * @since 1.1.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new EpsTransaction();
        }
        return $this->transactionInstance;
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
            EpsTransaction::NAME,
            $this->getPaymentConfig()->getTransactionMAID(),
            $this->getPaymentConfig()->getTransactionSecret()
        ));
        return $config;
    }

    /**
     * @return SepaCreditTransferPaymentConfig
     *
     * @since 1.1.0
     */
    public function getPaymentConfig()
    {
        $paymentConfig = new SepaCreditTransferPaymentConfig(
            $this->getPluginConfig('api_url'),
            $this->getPluginConfig('api_user'),
            $this->getPluginConfig('api_password')
        );

        $paymentConfig->setTransactionMAID($this->getPluginConfig('api_maid'));
        $paymentConfig->setTransactionSecret($this->getPluginConfig('api_secret'));
        $paymentConfig->setTransactionOperation(Operation::PAY);
        $paymentConfig->setOrderIdentification($this->getPluginConfig('order_identification'));
        $paymentConfig->setFraudPrevention($this->getPluginConfig('fraud_prevention'));

        $paymentConfig->setBackendTransactionMAID(
            $this->getPluginConfig(
                'api_maid',
                Payment::CONFIG_PREFIX . SepaCreditTransferPaymentConfig::BACKEND_NAME
            )
        );
        $paymentConfig->setBackendTransactionSecret(
            $this->getPluginConfig(
                'api_secret',
                Payment::CONFIG_PREFIX . SepaCreditTransferPaymentConfig::BACKEND_NAME
            )
        );
        $paymentConfig->setBackendCreditorId(
            $this->getPluginConfig(
                'creditor_id',
                Payment::CONFIG_PREFIX . SepaCreditTransferPaymentConfig::BACKEND_NAME
            )
        );

        return $paymentConfig;
    }

    /**
     * @param OrderSummary       $orderSummary
     * @param TransactionService $transactionService
     * @param Redirect           $redirect
     *
     * @return null|Action
     *
     * @since 1.1.0
     */
    public function processPayment(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Redirect $redirect
    ) {
        $additionalPaymentData = $orderSummary->getAdditionalPaymentData();
        $transaction           = $this->getTransaction();

        if (array_key_exists('epsBic', $additionalPaymentData)) {
            $bankAccount = new BankAccount();
            $bankAccount->setBic($additionalPaymentData['epsBic']);
            $transaction->setBankAccount($bankAccount);
        }

        return null;
    }

    /**
     * @return string
     *
     * @since 1.1.0
     */
    public function getFormTemplateName()
    {
        return 'WirecardEE/form/eps.phtml';
    }

    /**
     * @param \Mage_Sales_Model_Order                     $order
     * @param                                             $operation
     * @param \Mage_Sales_Model_Order_Payment_Transaction $parentTransaction
     *
     * @return EpsTransaction|SepaCreditTransferTransaction
     *
     * @since 1.1.0
     */
    public function getBackendTransaction(
        \Mage_Sales_Model_Order $order,
        $operation,
        \Mage_Sales_Model_Order_Payment_Transaction $parentTransaction
    ) {
        if ($operation === Operation::CREDIT) {
            return new SepaCreditTransferTransaction();
        }
        return new EpsTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function getRefundOperation()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelOperation()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCaptureOperation()
    {
        return null;
    }
}
