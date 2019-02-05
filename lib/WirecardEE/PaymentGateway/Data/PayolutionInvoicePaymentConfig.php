<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Data;

/**
 * Guaranteed Invoice by payolution specific payment configuration.
 *
 * @since   1.2.0
 */
class PayolutionInvoicePaymentConfig extends PaymentConfig
{
    /**
     * @var PaymentConfig[]
     */
    protected $currencyConfigs = [];

    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @var string
     */
    protected $merchantId;

    /**
     * @var string
     */
    protected $termsUrl;

    /**
     * @var bool
     */
    protected $requiresConsent;

    /**
     * @var float
     */
    protected $minAmount;

    /**
     * @var float
     */
    protected $maxAmount;

    /**
     * @var array
     */
    protected $shippingCountries;

    /**
     * @var array
     */
    protected $billingCountries;

    /**
     * @var bool
     */
    protected $allowDifferentBillingShipping;

    /**
     * @param string $currency
     * @param PaymentConfig $paymentConfig
     *
     * @since 1.2.0
     */
    public function addCurrencyConfig($currency, PaymentConfig $paymentConfig)
    {
        if ($this->hasCurrencyConfig($currency)) {
            throw new \InvalidArgumentException("Currency configuration for $currency already exists");
        }
        $this->currencyConfigs[$currency] = $paymentConfig;
    }

    /**
     * @param $currency
     *
     * @return PaymentConfig
     *
     * @since 1.2.0
     */
    public function getCurrencyConfig($currency)
    {
        if (! $this->hasCurrencyConfig($currency)) {
            throw new \InvalidArgumentException("No configuration for currency $currency");
        }
        return $this->currencyConfigs[$currency];
    }

    /**
     * @param $currency
     *
     * @return bool
     *
     * @since 1.2.0
     */
    public function hasCurrencyConfig($currency)
    {
        return isset($this->currencyConfigs[$currency]);
    }

    /**
     * @return bool
     *
     * @since 1.2.0
     */
    public function isEnabled()
    {
        return count($this->currencyConfigs) > 0;
    }

    /**
     * @return string
     *
     * @since 1.2.0
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * @param $merchantId
     *
     * @since 1.2.0
     */
    public function setMerchantId($merchantId)
    {
        $this->merchantId = $merchantId;
    }

    /**
     * @return string
     *
     * @since 1.2.0
     */
    public function getTermsUrl()
    {
        return $this->termsUrl;
    }

    /**
     * @param $termsUrl
     *
     * @since 1.2.0
     */
    public function setTermsUrl($termsUrl)
    {
        $this->termsUrl = $termsUrl;
    }

    /**
     * @return bool
     *
     * @since 1.2.0
     */
    public function requiresConsent()
    {
        return $this->requiresConsent;
    }

    /**
     * @param $requiresConsent
     *
     * @since 1.2.0
     */
    public function setRequiresConsent($requiresConsent)
    {
        $this->requiresConsent = $requiresConsent;
    }

    /**
     * @return string|float
     *
     * @since 1.2.0
     */
    public function getMinAmount()
    {
        return $this->minAmount;
    }

    /**
     * @param string|float $minAmount
     *
     * @since 1.2.0
     */
    public function setMinAmount($minAmount)
    {
        $this->minAmount = $minAmount;
    }

    /**
     * @return string|float
     *
     * @since 1.2.0
     */
    public function getMaxAmount()
    {
        return $this->maxAmount;
    }

    /**
     * @param string|float $maxAmount
     *
     * @since 1.2.0
     */
    public function setMaxAmount($maxAmount)
    {
        $this->maxAmount = $maxAmount;
    }

    /**
     * @return array
     *
     * @since 1.2.0
     */
    public function getShippingCountries()
    {
        return $this->shippingCountries;
    }

    /**
     * @param array $shippingCountries
     *
     * @since 1.2.0
     */
    public function setShippingCountries($shippingCountries)
    {
        if (! is_array($shippingCountries)) {
            $shippingCountries = [];
        }
        $this->shippingCountries = $shippingCountries;
    }

    /**
     * @return array
     *
     * @since 1.2.0
     */
    public function getBillingCountries()
    {
        return $this->billingCountries;
    }

    /**
     * @param array $billingCountries
     *
     * @since 1.2.0
     */
    public function setBillingCountries($billingCountries)
    {
        if (! is_array($billingCountries)) {
            $billingCountries = [];
        }
        $this->billingCountries = $billingCountries;
    }

    /**
     * @return bool
     *
     * @since 1.2.0
     */
    public function isAllowedDifferentBillingShipping()
    {
        return $this->allowDifferentBillingShipping;
    }

    /**
     * @param bool $allowDifferentBillingShipping
     *
     * @since 1.2.0
     */
    public function setAllowDifferentBillingShipping($allowDifferentBillingShipping)
    {
        $this->allowDifferentBillingShipping = $allowDifferentBillingShipping;
    }

    /**
     * @since 1.2.0
     */
    public function toArray()
    {
        return [
            'minAmount'                     => $this->getMinAmount(),
            'maxAmount'                     => $this->getMaxAmount(),
            'shippingCountries'             => $this->getShippingCountries(),
            'billingCountries'              => $this->getBillingCountries(),
            'allowDifferentBillingShipping' => $this->isAllowedDifferentBillingShipping(),
        ];
    }
}
