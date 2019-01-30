<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Payments;

use Wirecard\PaymentSdk\Config\MaestroConfig;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\MaestroTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\Action;
use WirecardEE\PaymentGateway\Actions\ViewAction;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\PaymentConfig;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessPaymentInterface;
use WirecardEE\PaymentGateway\Service\TransactionManager;

class MaestroPayment extends Payment implements ProcessPaymentInterface
{
    const NAME = MaestroTransaction::NAME;

    /**
     * @var MaestroTransaction
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
     * @return MaestroTransaction
     *
     * @since 1.1.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new MaestroTransaction();
        }
        return $this->transactionInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionConfig($selectedCurrency)
    {
        $transactionConfig = parent::getTransactionConfig($selectedCurrency);
        $paymentConfig     = $this->getPaymentConfig();
        $maestroConfig     = new MaestroConfig();

        $maestroConfig->setThreeDCredentials(
            $paymentConfig->getTransactionMAID(),
            $paymentConfig->getTransactionSecret()
        );

        $transactionConfig->add($maestroConfig);
        $this->getTransaction()->setConfig($maestroConfig);

        return $transactionConfig;
    }

    /**
     * @return PaymentConfig
     *
     * @since 1.1.0
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
     * @param OrderSummary       $orderSummary
     * @param TransactionService $transactionService
     * @param Redirect           $redirect
     *
     * @return Action|null
     * @throws \Exception
     *
     * @since 1.1.0
     */
    public function processPayment(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Redirect $redirect
    ) {
        $transaction = $this->getTransaction();
        $transaction->setTermUrl($redirect);

        $requestData = $transactionService->getCreditCardUiWithData(
            $transaction,
            $orderSummary->getPayment()->getTransactionType(),
            \Mage::app()->getLocale()->getLocaleCode()
        );
        $requestDataArray = json_decode($requestData, true);

        /** @var \Mage_Sales_Model_Order_Payment_Transaction $transaction */
        $transaction = \Mage::getModel('sales/order_payment_transaction');
        $transaction->setTxnType(
            TransactionManager::getMageTransactionType($requestDataArray['transaction_type'])
        );
        $transaction->setOrder($orderSummary->getOrder());
        $transaction->setOrderPaymentObject($orderSummary->getOrder()->getPayment());
        $transaction->setAdditionalInformation(
            \Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
            array_merge($requestDataArray, [
                TransactionManager::TYPE_KEY => TransactionManager::TYPE_INITIAL_REQUEST
            ])
        );
        $transaction->save();

        return new ViewAction('paymentgateway/seamless', [
            'wirecardUrl'         => $orderSummary->getPayment()->getPaymentConfig()->getBaseUrl(),
            'wirecardRequestData' => $requestData,
            'url'                 => \Mage::getUrl('paymentgateway/gateway/return', ['method' => self::NAME]),
        ]);
    }
}
