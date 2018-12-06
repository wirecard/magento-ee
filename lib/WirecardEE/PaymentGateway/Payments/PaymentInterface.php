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
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardEE\PaymentGateway\Data\PaymentConfig;

/**
 * @since 1.0.0
 */
interface PaymentInterface
{
    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getName();

    /**
     * Returns payment specific transaction object (always returns the same instance!).
     *
     * @return Transaction
     *
     * @since 1.0.0
     */
    public function getTransaction();

    /**
     * Returns transaction config.
     *
     * @param $selectedCurrency
     *
     * @return Config
     *
     * @since 1.0.0
     */
    public function getTransactionConfig($selectedCurrency);

    /**
     * Returns payment specific configuration.
     *
     * @return PaymentConfig
     *
     * @since 1.0.0
     */
    public function getPaymentConfig();

    /**
     * Returns the transaction type from `getPaymentOptions`.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getTransactionType();
}