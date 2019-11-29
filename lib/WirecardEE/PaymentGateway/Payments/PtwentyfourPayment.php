<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Payments;

use Exception;
use InvalidArgumentException;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\PtwentyfourTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\Action;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\PaymentConfig;
use WirecardEE\PaymentGateway\Exception\InsufficientDataException;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessPaymentInterface;
use WirecardEE\PaymentGateway\Payments\Contracts\MandatoryMailTrait;

class PtwentyfourPayment extends Payment implements ProcessPaymentInterface
{
    use MandatoryMailTrait;
    /**
     * Payment method name in mage
     *
     * @since 2.0.0
     */
    const NAME = 'ptwentyfour';

    /**
     * Accepted currency code for p24
     *
     * @since 2.0.0
     */
    const ACCEPTED_CURRENCY_CODE = 'PLN';

    /**
     * @var PtwentyfourTransaction
     *
     * @since 2.0.0
     */
    private $transactionInstance;

    /**
     * @var OrderSummary
     *
     * @since 2.0.0
     */
    protected $orderSummary;

    /**
     * @return string
     *
     * @since 2.0.0
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return PtwentyfourTransaction
     *
     * @since 2.0.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new PtwentyfourTransaction();
        }
        return $this->transactionInstance;
    }

    /**
     * @param string $selectedCurrency
     *
     * @return Config
     *
     * @since 2.0.0
     */
    public function getTransactionConfig($selectedCurrency)
    {
        $config = parent::getTransactionConfig($selectedCurrency);
        $config->add(new PaymentMethodConfig(
            PtwentyfourTransaction::NAME,
            $this->getPaymentConfig()->getTransactionMAID(),
            $this->getPaymentConfig()->getTransactionSecret()
        ));

        return $config;
    }

    /**
     * @return PaymentConfig
     *
     * @since 2.0.0
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
        $paymentConfig->setFraudPrevention($this->getPluginConfig('fraud_prevention'));
        $paymentConfig->setOrderIdentification($this->getPluginConfig('order_identification'));

        return $paymentConfig;
    }

    /**
     * @param OrderSummary $orderSummary
     * @param TransactionService $transactionService
     * @param Redirect $redirect
     *
     * @throws InvalidArgumentException if currency is not set to PLN
     * @throws InsufficientDataException if mail address is missing
     * @throws Exception if account holder can not be set
     *
     * @return null|Action
     *
     * @since 2.0.0
     */
    public function processPayment(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Redirect $redirect
    ) {
        $transaction        = $this->getTransaction();
        $this->orderSummary = $orderSummary;
        $this->setMail();
        $this->validateCurrency();
        $this->addAccountHolder($transaction);

        return null;
    }

    /**
     * @return string
     *
     * @since 2.0.0
     */
    public function getRefundOperation()
    {
        return Operation::CANCEL;
    }

    /**
     * @param Transaction $transaction
     *
     * @throws Exception|InsufficientDataException if account holder can not be set
     *         Or if email is not set
     *
     * @since 2.0.0
     */
    protected function addAccountHolder($transaction)
    {
        $accountHolder = new AccountHolder();
        $accountHolder->setEmail($this->getValidatedMail());
        $transaction->setAccountHolder($accountHolder);
    }

    /**
     * Get billing address mail from order
     *
     * @return string
     *
     * @since 2.0.0
     */
    protected function fetchBillingAddressMail()
    {
        $order         = $this->orderSummary->getOrder();
        $addressData   = $order->getBillingAddress();

        return $addressData->getEmail();
    }

    /**
     *
     * Checks if currency used is PLN
     *
     * @throws InvalidArgumentException if currency code is not set to PLN
     *
     * @since 2.0.0
     */
    protected function validateCurrency()
    {
        $fetchedCurrencyCode = (string)$this->orderSummary->getOrder()->getBaseCurrencyCode();

        if (self::ACCEPTED_CURRENCY_CODE !== $fetchedCurrencyCode) {
            throw new InvalidArgumentException('Currency is not set to PLN');
        }
    }

    /**
     * Set mail
     *
     * @since 2.0.0
     */
    protected function setMail()
    {
        $this->mail = $this->fetchBillingAddressMail();
    }
}
