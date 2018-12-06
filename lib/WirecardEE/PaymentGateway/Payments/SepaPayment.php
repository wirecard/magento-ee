<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Payments;

use Wirecard\PaymentSdk\Config\SepaConfig;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\SepaPaymentConfig;
use WirecardEE\PaymentGateway\Payments\Contracts\AdditionalViewAssignmentsInterface;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessPaymentInterface;

class SepaPayment extends Payment implements ProcessPaymentInterface, AdditionalViewAssignmentsInterface
{
    const NAME = SepaDirectDebitTransaction::NAME;

    /**
     * @var SepaDirectDebitTransaction
     */
    private $transactionInstance;

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return SepaDirectDebitTransaction
     *
     * @since 1.0.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new SepaDirectDebitTransaction();
        }
        return $this->transactionInstance;
    }

    /**
     * @param $selectedCurrency
     *
     * @return Config
     *
     * @since 1.0.0
     */
    public function getTransactionConfig($selectedCurrency)
    {
        $config = parent::getTransactionConfig($selectedCurrency);

        $sepaDirectDebitConfig = new SepaConfig(
            SepaDirectDebitTransaction::NAME,
            $this->getPaymentConfig()->getTransactionMAID(),
            $this->getPaymentConfig()->getTransactionSecret()
        );
        
        $sepaDirectDebitConfig->setCreditorId($this->getPaymentConfig()->getCreditorId());
        $config->add($sepaDirectDebitConfig);

        //  $sepaCreditTransferConfig = new SepaConfig(
        //     SepaCreditTransferTransaction::NAME,
        //     $this->getPaymentConfig()->getBackendTransactionMAID(),
        //     $this->getPaymentConfig()->getBackendTransactionSecret()
        // );
        // $sepaCreditTransferConfig->setCreditorId($this->getPaymentConfig()->getBackendCreditorId());
        // $config->add($sepaCreditTransferConfig);

        return $config;
    }

    /**
     * @return PaymentConfig
     *
     * @since 1.0.0
     */
    public function getPaymentConfig()
    {
        $paymentConfig = new SepaPaymentConfig(
            $this->getPluginConfig('api_url'),
            $this->getPluginConfig('api_user'),
            $this->getPluginConfig('api_password')
        );
        $paymentConfig->setTransactionMAID($this->getPluginConfig('api_maid'));
        $paymentConfig->setTransactionSecret($this->getPluginConfig('api_secret'));
        $paymentConfig->setTransactionOperation($this->getPluginConfig('transaction_type'));

        $paymentConfig->setShowBic($this->getPluginConfig('show_bic'));
        $paymentConfig->setCreditorId($this->getPluginConfig('creditor_id'));
        $paymentConfig->setCreditorName($this->getPluginConfig('creditor_name'));
        $paymentConfig->setCreditorAddress($this->getPluginConfig('creditor_address'));
        // $paymentConfig->setBackendTransactionMAID($this->getPluginConfig('SepaBackendMerchantId'));
        // $paymentConfig->setBackendTransactionSecret($this->getPluginConfig('SepaBackendSecret'));
        // $paymentConfig->setBackendCreditorId($this->getPluginConfig('SepaBackendCreditorId'));

        $paymentConfig->setFraudPrevention($this->getPluginConfig('fraud_prevention'));
        return $paymentConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdditionalViewAssignments()
    {
        $paymentConfig = $this->getPaymentConfig();
        return [
            'method'          => $this->getName(),
            'showBic'         => $paymentConfig->showBic(),
            'creditorId'      => $paymentConfig->getCreditorId(),
            'creditorName'    => $paymentConfig->getCreditorName(),
            'creditorAddress' => $paymentConfig->getCreditorAddress(),
        ];
    }

    /**
     * @param OrderSummary $orderSummary
     * @param TransactionService $transactionService
     * @param Redirect $redirect
     *
     * @return null|Action
     *
     * @since 1.0.0
     */
    public function processPayment(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Redirect $redirect
    ) {
        /*
        $additionalPaymentData = $orderSummary->getAdditionalPaymentData();
        if (! isset($additionalPaymentData['sepaConfirmMandate'])
            || $additionalPaymentData['sepaConfirmMandate'] !== 'confirmed'
            || ! isset($additionalPaymentData['sepaIban'])
            || ! isset($additionalPaymentData['sepaFirstName'])
            || ! isset($additionalPaymentData['sepaLastName'])
        ) {
            throw new InsufficientDataException('Insufficient Data for SEPA Direct Debit Transaction');
        }

        $transaction = $this->getTransaction();

        $accountHolder = new AccountHolder();
        $accountHolder->setFirstName($additionalPaymentData['sepaFirstName']);
        $accountHolder->setLastName($additionalPaymentData['sepaLastName']);
        $transaction->setAccountHolder($accountHolder);
        $transaction->setIban($additionalPaymentData['sepaIban']);

        if ($this->getPluginConfig('SepaShowBic') && isset($additionalPaymentData['sepaBic'])) {
            $transaction->setBic($additionalPaymentData['sepaBic']);
        }

        $mandate = new Mandate($this->generateMandateId($orderSummary));
        $transaction->setMandate($mandate);

        return null;
        */
    }
    
    /**
     * Generate sepa mandate id: Format "[creditorId]-[orderNumber]-[timestamp]"
     * [timestamp] is already part of the paymentUniqueId (first 10 characters). The remaining 5 characters of
     * paymentUniqueId can be used as [orderNumber], which has a max length of 5 anyway.
     *
     * @param OrderSummary $orderSummary
     *
     * @return string
     *
     * @since 1.0.0
     */
    private function generateMandateId(OrderSummary $orderSummary)
    {
        return $this->getPluginConfig('SepaCreditorId') . '-' .
               substr($orderSummary->getPaymentUniqueId(), 10, 5) . '-' .
               substr($orderSummary->getPaymentUniqueId(), 0, 10);
    }
}
