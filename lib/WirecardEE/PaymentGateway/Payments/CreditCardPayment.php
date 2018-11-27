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
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use WirecardEE\PaymentGateway\Data\CreditCardPaymentConfig;

class CreditCardPayment extends Payment
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
        /** @var \Mage_Directory_Helper_Data $directoryHelper */
        $directoryHelper = \Mage::helper('directory');
        $value           = $directoryHelper->currencyConvert(
            $limitValue,
            $limitCurrency,
            $selectedCurrency
        );

        return new Amount($value, $selectedCurrency);
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
        $paymentConfig->setSslMaxLimit($this->getPluginConfig('non_threeds_max_limit'));
        $paymentConfig->setSslMaxLimitCurrency($this->getPluginConfig('non_threeds_max_limit_currency'));
        $paymentConfig->setThreeDMinLimit($this->getPluginConfig('threeds_min_limit'));
        $paymentConfig->setThreeDMinLimitCurrency($this->getPluginConfig('threeds_min_limit_currency'));

        $paymentConfig->setFraudPrevention($this->getPluginConfig('fraud_prevention'));

        // $paymentConfig->setVaultEnabled($this->getPluginConfig('CreditCardEnableVault'));
        // $paymentConfig->setAllowAddressChanges($this->getPluginConfig('CreditCardAllowAddressChanges'));
        // $paymentConfig->setThreeDUsageOnTokens($this->getPluginConfig('CreditCardThreeDUsageOnTokens'));

        return $paymentConfig;
    }
}
