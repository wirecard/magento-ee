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
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\UpiTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\Action;
use WirecardEE\PaymentGateway\Actions\ViewAction;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\PaymentConfig;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessPaymentInterface;
use WirecardEE\PaymentGateway\Service\TransactionManager;

class UnionpayPayment extends Payment implements ProcessPaymentInterface
{
    const NAME = UpiTransaction::NAME;

    /**
     * @var UpiTransaction
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
     * @return UpiTransaction
     *
     * @since 1.2.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new UpiTransaction();
        }
        return $this->transactionInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionConfig($selectedCurrency)
    {
        $config = parent::getTransactionConfig($selectedCurrency);
        $config->add(new PaymentMethodConfig(
            UpiTransaction::NAME,
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
     * @param OrderSummary       $orderSummary
     * @param TransactionService $transactionService
     * @param Redirect           $redirect
     *
     * @return Action
     * @throws \Exception
     *
     * @since 1.2.0
     */
    public function processPayment(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Redirect $redirect
    ) {
        $transaction = $this->getTransaction();
        $transaction->setTermUrl($redirect);

        $requestData      = $transactionService->getCreditCardUiWithData(
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
                TransactionManager::TYPE_KEY => TransactionManager::TYPE_INITIAL_REQUEST,
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
