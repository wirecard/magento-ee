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

interface PaymentInterface
{
    public function getName();

    /**
     * Returns payment specific transaction object (always returns the same instance!).
     *
     * @return Transaction
     */
    public function getTransaction();

    /**
     * Returns transaction config.
     *
     * @return Config
     */
    public function getTransactionConfig();

    /**
     * Returns payment specific configuration.
     *
     * @return PaymentConfig
     */
    public function getPaymentConfig();
}
