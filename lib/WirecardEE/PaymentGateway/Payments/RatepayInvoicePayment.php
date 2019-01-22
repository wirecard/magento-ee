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
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\RatepayInvoicePaymentConfig;
use WirecardEE\PaymentGateway\Payments\Contracts\CustomFormTemplate;
use WirecardEE\PaymentGateway\Payments\Contracts\DisplayRestrictionInterface;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessPaymentInterface;
use WirecardEE\PaymentGateway\Service\Logger;

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
        return $config;
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
        $paymentConfig->setTransactionOperation(Operation::PAY);
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

    /**
     * {@inheritdoc}
     */
    public function checkDisplayRestrictions(\Mage_Checkout_Model_Session $checkoutSession)
    {
        $logger        = new Logger();
        $paymentConfig = $this->getPaymentConfig();
        $quote         = $checkoutSession->getQuote();

        // Check if merchant disallows different billing/shipping address and compare both
        if (! $paymentConfig->isAllowedDifferentBillingShipping()
            && ! $quote->getShippingAddress()->getSameAsBilling()) {
            $logger->debug('different billing and shipping address');
            return false;
        }

        // Check if currency is allowed
        if (! in_array($quote->getQuoteCurrencyCode(), $paymentConfig->getAcceptedCurrencies())) {
            $logger->debug('currency not allowed' . $quote->getQuoteCurrencyCode());
            return false;
        }

        // Check customer age
        $birthday = $quote->getCustomerDob();

        if ($birthday && $this->isBelowAgeRestriction(new \DateTime($birthday))) {
            return false;
        }

        // Check shipping and billing addresses are allowed
        if (! in_array($quote->getBillingAddress()->getCountry(), $paymentConfig->getBillingCountries())
            || ! in_array($quote->getShippingAddress()->getCountry(), $paymentConfig->getShippingCountries())) {
            $logger->debug('billing or shipping country not allowed');
            return false;
        }

        // Check for virtual products
        if ($quote->hasVirtualItems()) {
            $logger->debug('quote contains virtual items');
            return false;
        }

        return true;
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
     * {@inheritdoc}
     */
    public function getFormTemplateName()
    {
        return 'WirecardEE/form/ratepay_invoice.phtml';
    }
}
