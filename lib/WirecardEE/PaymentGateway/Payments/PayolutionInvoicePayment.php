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
use Wirecard\PaymentSdk\Transaction\PayolutionInvoiceTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\PaymentConfig;
use WirecardEE\PaymentGateway\Data\PayolutionInvoicePaymentConfig;
use WirecardEE\PaymentGateway\Payments\Contracts\CustomFormTemplateInterface;
use WirecardEE\PaymentGateway\Payments\Contracts\DisplayRestrictionInterface;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessPaymentInterface;
use WirecardEE\PaymentGateway\Service\SessionManager;

class PayolutionInvoicePayment extends Payment implements
    ProcessPaymentInterface,
    DisplayRestrictionInterface,
    CustomFormTemplateInterface
{
    const NAME = 'payolutioninvoice';
    const MINIMUM_CONSUMER_AGE = 18;
    const SUPPORTED_CURRENCIES = ['eur', 'chf'];

    /**
     * @var PayolutionInvoiceTransaction
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
     * @return PayolutionInvoiceTransaction
     *
     * @since 1.1.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new PayolutionInvoiceTransaction();
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
        $paymentConfig  = $this->getPaymentConfig();
        $currencyConfig = $paymentConfig->getCurrencyConfig(strtoupper($selectedCurrency));

        $config = new Config(
            $currencyConfig->getBaseUrl(),
            $currencyConfig->getHttpUser(),
            $currencyConfig->getHttpPassword(),
            $selectedCurrency
        );
        $config->setShopInfo(
            'Magento CE',
            \Mage::getVersion()
        );
        $config->setPluginInfo($this->getHelper()->getPluginName(), $this->getHelper()->getPluginVersion());
        $config->add(new PaymentMethodConfig(
            PayolutionInvoiceTransaction::NAME,
            $currencyConfig->getTransactionMAID(),
            $currencyConfig->getTransactionSecret()
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
        $transaction = new PayolutionInvoiceTransaction();
        $transaction->setOrderNumber($order->getRealOrderId());

        return $transaction;
    }

    /**
     * @return PayolutionInvoicePaymentConfig
     *
     * @since 1.2.0
     */
    public function getPaymentConfig()
    {
        $paymentConfig = new PayolutionInvoicePaymentConfig(
            '',
            '',
            ''
        );

        foreach (self::SUPPORTED_CURRENCIES as $currency) {
            if ($this->getPluginConfig($currency . '_enabled')) {
                $currencyConfig = new PaymentConfig(
                    $this->getPluginConfig($currency . '_api_url'),
                    $this->getPluginConfig($currency . '_api_user'),
                    $this->getPluginConfig($currency . '_api_password')
                );
                $currencyConfig->setTransactionMAID($this->getPluginConfig($currency . '_api_maid'));
                $currencyConfig->setTransactionSecret($this->getPluginConfig($currency . '_api_secret'));

                $paymentConfig->addCurrencyConfig(strtoupper($currency), $currencyConfig);
            }
        }

        $paymentConfig->setFraudPrevention($this->getPluginConfig('fraud_prevention'));
        $paymentConfig->setOrderIdentification(true);
        $paymentConfig->setMinAmount($this->getPluginConfig('min_amount'));
        $paymentConfig->setMaxAmount($this->getPluginConfig('max_amount'));
        $paymentConfig->setBillingCountries(
            explode(',', $this->getPluginConfig('allowed_billing_countries'))
        );
        $paymentConfig->setShippingCountries(
            explode(',', $this->getPluginConfig('allowed_shipping_countries'))
        );
        $paymentConfig->setAllowDifferentBillingShipping(
            ! $this->getPluginConfig('identical_billing_shipping_address')
        );
        $paymentConfig->setRequiresConsent($this->getPluginConfig('require_consent'));
        $paymentConfig->setMerchantId($this->getPluginConfig('mid'));
        $paymentConfig->setTermsUrl($this->getPluginConfig('terms_url'));
        $paymentConfig->setTransactionOperation(Operation::RESERVE);

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

        // If no currency configuration is enabled the payment is disabled in general
        if (! $paymentConfig->isEnabled()) {
            return false;
        }

        // Check if merchant disallows different billing/shipping address and compare both
        if (! $paymentConfig->isAllowedDifferentBillingShipping()
            && ! $quote->getShippingAddress()->getSameAsBilling()) {
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
        return 'WirecardEE/form/payolution_invoice.phtml';
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
