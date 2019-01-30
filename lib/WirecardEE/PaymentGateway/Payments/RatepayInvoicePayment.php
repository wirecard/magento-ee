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
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\RatepayInvoicePaymentConfig;
use WirecardEE\PaymentGateway\Payments\Contracts\CustomFormTemplate;
use WirecardEE\PaymentGateway\Payments\Contracts\DisplayRestrictionInterface;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessPaymentInterface;
use WirecardEE\PaymentGateway\Service\SessionManager;

class RatepayInvoicePayment extends Payment implements
    ProcessPaymentInterface,
    DisplayRestrictionInterface,
    CustomFormTemplate
{
    const NAME = RatepayInvoiceTransaction::NAME;
    const MINIMUM_CONSUMER_AGE = 18;

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
            RatepayInvoiceTransaction::NAME,
            $this->getPaymentConfig()->getTransactionMAID(),
            $this->getPaymentConfig()->getTransactionSecret()
        ));

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getBackendTransaction(
        \Mage_Sales_Model_Order $order,
        $operation,
        \Mage_Sales_Model_Order_Payment_Transaction $parentTransaction
    ) {
        $transaction = new RatepayInvoiceTransaction();
        $transaction->setOrderNumber($order->getRealOrderId());

        return $transaction;
    }

    /**
     * @return RatepayInvoicePaymentConfig
     *
     * @since 1.1.0
     */
    public function getPaymentConfig()
    {
        $paymentConfig = new RatepayInvoicePaymentConfig(
            $this->getPluginConfig('api_url'),
            $this->getPluginConfig('api_user'),
            $this->getPluginConfig('api_password')
        );

        $paymentConfig->setTransactionMAID($this->getPluginConfig('api_maid'));
        $paymentConfig->setTransactionSecret($this->getPluginConfig('api_secret'));
        $paymentConfig->setTransactionOperation(Operation::RESERVE);
        $paymentConfig->setOrderIdentification(true);
        $paymentConfig->setMinAmount($this->getPluginConfig('min_amount'));
        $paymentConfig->setMaxAmount($this->getPluginConfig('max_amount'));
        $paymentConfig->setAcceptedCurrencies(
            explode(',', $this->getPluginConfig('allowed_currencies'))
        );
        $paymentConfig->setBillingCountries(
            explode(',', $this->getPluginConfig('allowed_billing_countries'))
        );
        $paymentConfig->setShippingCountries(
            explode(',', $this->getPluginConfig('allowed_shipping_countries'))
        );
        $paymentConfig->setAllowDifferentBillingShipping(
            ! $this->getPluginConfig('identical_billing_shipping_address')
        );

        $paymentConfig->setFraudPrevention($this->getPluginConfig('fraud_prevention'));

        return $paymentConfig;
    }

    /**
     * @param OrderSummary       $orderSummary
     * @param TransactionService $transactionService
     * @param Redirect           $redirect
     *
     * @throws \Exception
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
        $transaction   = $this->getTransaction();
        $paymentConfig = $this->getPaymentConfig();

        if (! $paymentConfig->hasFraudPrevention()) {
            $transaction->setDevice($orderSummary->getWirecardDevice());
            $transaction->setOrderNumber($orderSummary->getOrder()->getRealOrderId());
            $transaction->setAccountHolder($orderSummary->getUserMapper()->getBillingAccountHolder());
            $transaction->setShipping($orderSummary->getUserMapper()->getShippingAccountHolder());
        }

        if (! $this->isAmountInRange($orderSummary->getOrder()->getBaseGrandTotal())) {
            return new ErrorAction(
                ErrorAction::PROCESSING_FAILED,
                'Basket total amount not within set range'
            );
        }

        return $this->validateConsumerDateOfBirth($orderSummary, $transaction->getAccountHolder());
    }

    /**
     * {@inheritdoc}
     */
    public function checkDisplayRestrictions(\Mage_Checkout_Model_Session $checkoutSession)
    {
        $paymentConfig = $this->getPaymentConfig();
        $quote         = $checkoutSession->getQuote();

        // Check if merchant disallows different billing/shipping address and compare both
        if (! $paymentConfig->isAllowedDifferentBillingShipping()
            && ! $quote->getShippingAddress()->getSameAsBilling()) {
            return false;
        }

        // Check if currency is allowed
        if (! in_array($quote->getQuoteCurrencyCode(), $paymentConfig->getAcceptedCurrencies())) {
            return false;
        }

        // Check if amount is in range
        if (! $this->isAmountInRange($quote->getBaseGrandTotal())) {
            return false;
        }

        // Check customer age
        /** @var \Mage_Core_Model_Session $session */
        $session  = \Mage::getSingleton("core/session", ["name" => "frontend"]);
        $birthday = $quote->getCustomerDob()
            ? new \DateTime($quote->getCustomerDob())
            : $this->getBirthdayFromPaymentData((new SessionManager($session))->getPaymentData());

        if ($birthday && $this->isBelowAgeRestriction($birthday)) {
            return false;
        }

        // Check shipping and billing addresses are allowed
        if (! in_array($quote->getBillingAddress()->getCountry(), $paymentConfig->getBillingCountries())
            || ! in_array($quote->getShippingAddress()->getCountry(), $paymentConfig->getShippingCountries())) {
            return false;
        }

        // Check for virtual products
        if ($quote->hasVirtualItems()) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormTemplateName()
    {
        return 'WirecardEE/form/ratepay_invoice.phtml';
    }

    public function getRefundOperation()
    {
        return Operation::CANCEL;
    }

    /**
     * @param \DateTime $birthDay
     *
     * @return bool
     *
     * @throws \Exception
     *
     * @since 1.1.0
     */
    private function isBelowAgeRestriction(\DateTime $birthDay)
    {
        $age = $birthDay->diff(new \DateTime());
        return $age->y < self::MINIMUM_CONSUMER_AGE;
    }

    /**
     * @param $amount
     *
     * @return bool
     *
     * @since 1.1.0
     */
    private function isAmountInRange($amount)
    {
        $minAmount = floatval($this->getPaymentConfig()->getMinAmount());
        $maxAmount = floatval($this->getPaymentConfig()->getMaxAmount());
        return ($amount >= $minAmount && $amount <= $maxAmount);
    }

    /**
     * @param array|null $paymentData
     *
     * @return \DateTime|null
     *
     * @throws \Exception
     *
     * @since 1.1.0
     */
    private function getBirthdayFromPaymentData($paymentData)
    {
        if (! isset($paymentData['birthday']['year'])
            || ! isset($paymentData['birthday']['month'])
            || ! isset($paymentData['birthday']['day'])
        ) {
            return null;
        }
        $birthDay = new \DateTime();
        $birthDay->setDate(
            intval($paymentData['birthday']['year']),
            intval($paymentData['birthday']['month']),
            intval($paymentData['birthday']['day'])
        );
        return $birthDay;
    }

    /**
     * @param OrderSummary  $orderSummary
     * @param AccountHolder $accountHolder
     *
     * @throws \Exception
     *
     * @return ErrorAction|null
     *
     * @since 1.1.0
     */
    private function validateConsumerDateOfBirth(OrderSummary $orderSummary, AccountHolder $accountHolder)
    {
        /** @var \Mage_Core_Model_Session $session */
        $session  = \Mage::getSingleton("core/session", ["name" => "frontend"]);
        $birthday = $orderSummary->getOrder()->getCustomerDob()
            ? new \DateTime($orderSummary->getOrder()->getCustomerDob())
            : $this->getBirthdayFromPaymentData((new SessionManager($session))->getPaymentData());

        if ($birthday && $this->isBelowAgeRestriction($birthday)) {
            return new ErrorAction(ErrorAction::PROCESSING_FAILED,
                $birthday
                    ? 'Consumer must be at least ' . self::MINIMUM_CONSUMER_AGE . ' years old'
                    : 'Consumer birthday missing');
        }

        $accountHolder->setDateOfBirth($birthday);
        return null;
    }
}
