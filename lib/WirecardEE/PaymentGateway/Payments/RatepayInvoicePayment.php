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
use Wirecard\PaymentSdk\Config\SepaConfig;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\IdealTransaction;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\SepaCreditTransferPaymentConfig;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessPaymentInterface;

class RatepayInvoicePayment extends Payment implements ProcessPaymentInterface
{
    const NAME = RatepayInvoiceTransaction::NAME;

    /**
     * @var RatepayInvoiceTransaction
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
     * @return RatepayInvoiceTransaction
     *
     * @since 1.1.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new RatepayInvoiceTransaction();
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
            IdealTransaction::NAME,
            $this->getPaymentConfig()->getTransactionMAID(),
            $this->getPaymentConfig()->getTransactionSecret()
        ));
        $sepaCreditTransferConfig = new SepaConfig(
            SepaCreditTransferTransaction::NAME,
            $this->getPaymentConfig()->getBackendTransactionMAID(),
            $this->getPaymentConfig()->getBackendTransactionSecret()
        );
        $sepaCreditTransferConfig->setCreditorId($this->getPaymentConfig()->getBackendCreditorId());
        $config->add($sepaCreditTransferConfig);
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
        $paymentConfig->setOrderIdentification(true);
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

        $paymentConfig->setFraudPrevention($this->getPluginConfig('fraud_prevention'));

        return $paymentConfig;
    }

    /**
     * @param OrderSummary       $orderSummary
     * @param TransactionService $transactionService
     * @param Redirect           $redirect
     *
     * @return null
     *
     * @since 1.1.0
     */
    public function processPayment(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Redirect $redirect
    ) {

        return null;
    }
}
