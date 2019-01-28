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
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Device;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\PayByBankAppTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\Action;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\PaymentConfig;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessPaymentInterface;

class PayByBankAppPayment extends Payment implements ProcessPaymentInterface
{
    const NAME = PayByBankAppTransaction::NAME;

    /**
     * @var PayByBankAppTransaction
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
     * @return PayByBankAppTransaction
     *
     * @since 1.0.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new PayByBankAppTransaction();
            $this->transactionInstance->setCustomFields(new CustomFieldCollection());
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
        $config->add(new PaymentMethodConfig(
            PayByBankAppTransaction::NAME,
            $this->getPaymentConfig()->getTransactionMAID(),
            $this->getPaymentConfig()->getTransactionSecret()
        ));

        return $config;
    }

    /**
     * @return PaymentConfig
     *
     * @since 1.0.0
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
        $paymentConfig->setTransactionOperation(Operation::PAY);
        $paymentConfig->setSendBasket($this->getPluginConfig('send_basket'));
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
        $transaction = $this->getTransaction();

        $customFields = $transaction->getCustomFields();

        $customFields->add($this->makeCustomField('RefundReasonType', 'LATECONFIRMATION'));
        $customFields->add($this->makeCustomField('RefundMethod', 'BACS'));

        return $transaction;
    }

    /**
     * @return string
     */
    public function getRefundOperation()
    {
        return Operation::CANCEL;
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
        $transaction = $this->getTransaction();

        $customFields = $transaction->getCustomFields();

        $customFields->add(
            $this->makeCustomField('MerchantRtnStrng', $this->getPluginConfig('merchant_return_string'))
        );

        $customFields->add($this->makeCustomField('TxType', 'PAYMT'));
        $customFields->add($this->makeCustomField('DeliveryType', 'DELTAD'));

        $device = new Device($orderSummary->getUserMapper()->getUserAgent());

        // fallback to a generic value if detection failed
        if ($device->getType() === null) {
            $device->setType('other');
        }

        if ($device->getOperatingSystem() === null) {
            $device->setOperatingSystem('other');
        }

        $transaction->setDevice($device);

        return null;
    }

    /**
     * make new customfield with my prefix
     *
     * @param $key
     * @param $value
     * @return CustomField
     */
    protected function makeCustomField($key, $value)
    {
        $customField = new CustomField($key, $value);
        $customField->setPrefix('zapp.in.');

        return $customField;
    }
}
