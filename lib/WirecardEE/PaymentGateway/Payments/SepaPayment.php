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
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Mandate;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\Action;
use WirecardEE\PaymentGateway\Exception\InsufficientDataException;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\SepaPaymentConfig;
use WirecardEE\PaymentGateway\Payments\Contracts\CustomFormTemplate;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessPaymentInterface;

class SepaPayment extends Payment implements ProcessPaymentInterface, CustomFormTemplate
{
    const NAME = SepaDirectDebitTransaction::NAME;
    const BACKEND_NAME = SepaCreditTransferTransaction::NAME;

    /**
     * @var SepaDirectDebitTransaction
     */
    private $transactionInstance;

    /**
     * @return string
     *
     * @since 1.0.0
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
     * {@inheritdoc}
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
     * @return SepaPaymentConfig
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
        $paymentConfig->setCreditorStreet($this->getPluginConfig('creditor_street'));
        $paymentConfig->setCreditorZip($this->getPluginConfig('creditor_zip'));
        $paymentConfig->setCreditorCity($this->getPluginConfig('creditor_city'));
        $paymentConfig->setCreditorCountry($this->getPluginConfig('creditor_country'));
        $paymentConfig->setBackendTransactionMAID(
            $this->getPluginConfig(
                'api_maid',
                Payment::CONFIG_PREFIX . self::BACKEND_NAME
            )
        );
        $paymentConfig->setBackendTransactionSecret(
            $this->getPluginConfig(
                'api_secret',
                Payment::CONFIG_PREFIX . self::BACKEND_NAME
            )
        );
        $paymentConfig->setBackendCreditorId(
            $this->getPluginConfig(
                'creditor_id',
                Payment::CONFIG_PREFIX . self::BACKEND_NAME
            )
        );

        $paymentConfig->setFraudPrevention($this->getPluginConfig('fraud_prevention'));
        return $paymentConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getBackendTransaction(
        \Mage_Sales_Model_Order $order,
        $operation,
        \Mage_Sales_Model_Order_Payment_Transaction $parentTransaction
    ) {
        if ($operation === Operation::CREDIT) {
            return new SepaCreditTransferTransaction();
        }
        return new SepaDirectDebitTransaction();
    }

    /**
     * @param OrderSummary       $orderSummary
     * @param TransactionService $transactionService
     * @param Redirect           $redirect
     *
     * @return null|Action
     * @throws InsufficientDataException
     *
     * @since 1.0.0
     */
    public function processPayment(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Redirect $redirect
    ) {
        $additionalPaymentData = $orderSummary->getAdditionalPaymentData();
        if (! isset($additionalPaymentData['sepaIban'])
            || ! isset($additionalPaymentData['sepaFirstName'])
            || ! isset($additionalPaymentData['sepaLastName'])
        ) {
            throw new InsufficientDataException($this->getName());
        }

        $transaction = $this->getTransaction();

        $accountHolder = new AccountHolder();
        $accountHolder->setFirstName($additionalPaymentData['sepaFirstName']);
        $accountHolder->setLastName($additionalPaymentData['sepaLastName']);
        $transaction->setAccountHolder($accountHolder);
        $transaction->setIban($additionalPaymentData['sepaIban']);

        if ($this->getPluginConfig('show_bic') && isset($additionalPaymentData['sepaBic'])) {
            $transaction->setBic($additionalPaymentData['sepaBic']);
        }

        $mandate = new Mandate($this->generateMandateId($orderSummary));
        $transaction->setMandate($mandate);

        /** @var \WirecardEE_PaymentGateway_Model_Sepadirectdebit $sepaModel */
        $sepaModel = \Mage::getModel('paymentgateway/sepadirectdebit');
        $orderSummary->getOrder()->addStatusHistoryComment(
            "<strong>" . \Mage::helper('catalog')->__('Mandate Text') . "</strong>: <br>" . $sepaModel->getMandateText()
        );
        $orderSummary->getOrder()->save();

        return null;
    }

    /**
     * Generate sepa mandate id: Format "[creditorId]-[orderNumber]-[timestamp]"
     *
     * @param OrderSummary $orderSummary
     *
     * @return string
     *
     * @since 1.0.0
     */
    private function generateMandateId(OrderSummary $orderSummary)
    {
        $creditorId = $this->getPluginConfig('creditor_id');
        $appendix   = '-' . $orderSummary->getOrder()->getRealOrderId() . '-' . time();
        return substr($creditorId, 0, 35 - strlen($appendix)) . $appendix;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getFormTemplateName()
    {
        return 'WirecardEE/form/sepa.phtml';
    }
}
