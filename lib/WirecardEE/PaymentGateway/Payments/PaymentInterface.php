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

    /**
     * Returns payment specific transaction object for backend operations (always returns a new instance!).
     * Returns null, if no backend operations are allowed on this payment
     *
     * @param \Mage_Sales_Model_Order                     $order
     * @param string                                      $operation
     * @param \Mage_Sales_Model_Order_Payment_Transaction $parentTransaction
     *
     * @return Transaction|null
     *
     * @since 1.0.0
     */
    public function getBackendTransaction(
        \Mage_Sales_Model_Order $order,
        $operation,
        \Mage_Sales_Model_Order_Payment_Transaction $parentTransaction
    );

    /**
     * Returns the backend operation for canceling orders. Returning `null` would disable this backend operation (by
     * not hooking into the Magento cancel operation).
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getCancelOperation();

    /**
     * Returns the backend operation for refunding payments. Returning `null` would disable this backend operation (by
     * not hooking into the Magento cancel operation).
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getRefundOperation();

    /**
     * Returns the backend operation for capturing payments. Returning `null` would disable this backend operation (by
     * not hooking into the Magento cancel operation).
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getCaptureOperation();
}
