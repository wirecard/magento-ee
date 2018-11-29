<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Payments;

use Wirecard\PaymentSdk\Config\CreditCardConfig;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\ViewAction;
use WirecardEE\PaymentGateway\Data\CreditCardPaymentConfig;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessPaymentInterface;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessReturnInterface;
use WirecardEE\PaymentGateway\Service\Logger;

class CreditCardPayment extends Payment implements ProcessPaymentInterface, ProcessReturnInterface
{
    const NAME = CreditCardTransaction::NAME;

    /**
     * @var CreditCardTransaction
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
     * @return CreditCardTransaction
     *
     * @since 1.0.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new CreditCardTransaction();
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
        $creditCardConfig  = new CreditCardConfig();

        if ($paymentConfig->getTransactionMAID() && strtolower($paymentConfig->getTransactionMAID()) !== '') {
            $creditCardConfig->setSSLCredentials(
                $paymentConfig->getTransactionMAID(),
                $paymentConfig->getTransactionSecret()
            );
        }

        if ($paymentConfig->getThreeDMAID() && strtolower($paymentConfig->getThreeDMAID()) !== '') {
            $creditCardConfig->setThreeDCredentials(
                $paymentConfig->getThreeDMAID(),
                $paymentConfig->getThreeDSecret()
            );
        }

        if (strtolower($paymentConfig->getSslMaxLimit()) !== '') {
            $creditCardConfig->addSslMaxLimit(
                $this->getLimit(
                    $selectedCurrency,
                    $paymentConfig->getSslMaxLimit(),
                    $paymentConfig->getSslMaxLimitCurrency()
                )
            );
        }
        if (strtolower($paymentConfig->getThreeDMinLimit()) !== '') {
            $creditCardConfig->addThreeDMinLimit(
                $this->getLimit(
                    $selectedCurrency,
                    $paymentConfig->getThreeDMinLimit(),
                    $paymentConfig->getThreeDMinLimitCurrency()
                )
            );
        }

        $transactionConfig->add($creditCardConfig);
        $this->getTransaction()->setConfig($creditCardConfig);

        return $transactionConfig;
    }

    /**
     * @param string       $selectedCurrency
     * @param float|string $limitValue
     * @param string       $limitCurrency
     *
     * @return Amount
     *
     * @since 1.0.0
     */
    private function getLimit($selectedCurrency, $limitValue, $limitCurrency)
    {
        $factor = $this->getCurrencyConversionFactor(strtoupper($selectedCurrency), strtoupper($limitCurrency));
        return new Amount($limitValue * $factor, $selectedCurrency);
    }

    private function getCurrencyConversionFactor($selectedCurrency, $limitCurrency)
    {
        if ($selectedCurrency === $limitCurrency) {
            return 1.0;
        }

        try {
            \Mage::app()->getLocale()->currency($selectedCurrency);
            \Mage::app()->getLocale()->currency($limitCurrency);
        } catch (\Exception $e) {
            (new Logger())->error("Failed to load currency for converting currency: " . $e->getMessage());
            return 1.0;
        }

        /** @var \Mage_Directory_Helper_Data $directoryHelper */
        $directoryHelper = \Mage::helper('directory');
        $selectedFactor  = $directoryHelper->currencyConvert(
            1,
            \Mage::app()->getStore()->getBaseCurrencyCode(),
            $selectedCurrency
        );
        $limitFactor     = $directoryHelper->currencyConvert(
            1,
            \Mage::app()->getStore()->getBaseCurrencyCode(),
            $limitCurrency
        );
        if (! $selectedFactor) {
            $selectedFactor = 1.0;
        }
        if (! $limitFactor) {
            $limitFactor = 1.0;
        }
        return $selectedFactor / $limitFactor;
    }

    /**
     * @return CreditCardPaymentConfig
     *
     * @since 1.0.0
     */
    public function getPaymentConfig()
    {
        $paymentConfig = new CreditCardPaymentConfig(
            $this->getPluginConfig('api_url'),
            $this->getPluginConfig('api_user'),
            $this->getPluginConfig('api_password')
        );

        $paymentConfig->setTransactionMAID($this->getPluginConfig('api_maid'));
        $paymentConfig->setTransactionSecret($this->getPluginConfig('api_secret'));
        $paymentConfig->setTransactionOperation($this->getPluginConfig('transaction_type'));

        $paymentConfig->setThreeDMAID($this->getPluginConfig('threeds_maid'));
        $paymentConfig->setThreeDSecret($this->getPluginConfig('threeds_secret'));
        $paymentConfig->setSslMaxLimit($this->getPluginConfig('ssl_max_limit'));
        $paymentConfig->setSslMaxLimitCurrency($this->getPluginConfig('ssl_max_limit_currency'));
        $paymentConfig->setThreeDMinLimit($this->getPluginConfig('three_d_min_limit'));
        $paymentConfig->setThreeDMinLimitCurrency($this->getPluginConfig('three_d_min_limit_currency'));

        $paymentConfig->setFraudPrevention($this->getPluginConfig('fraud_prevention'));

        // $paymentConfig->setVaultEnabled($this->getPluginConfig('CreditCardEnableVault'));
        // $paymentConfig->setAllowAddressChanges($this->getPluginConfig('CreditCardAllowAddressChanges'));
        // $paymentConfig->setThreeDUsageOnTokens($this->getPluginConfig('CreditCardThreeDUsageOnTokens'));

        return $paymentConfig;
    }

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

        return new ViewAction('paymentgateway/seamless', [
            'wirecardUrl'         => $orderSummary->getPayment()->getPaymentConfig()->getBaseUrl(),
            'wirecardRequestData' => $requestData,
            'url'                 => \Mage::getUrl('paymentgateway/gateway/return', ['method' => self::NAME]),
        ]);
    }

    public function processReturn(TransactionService $transactionService, \Mage_Core_Controller_Request_Http $request)
    {
        return null;
    }
}
