<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Service;

use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use WirecardEE\PaymentGateway\Payments\PaymentInterface;
use WirecardEE\PaymentGateway\Payments\PaypalPayment;
use WirecardEE\PaymentGateway\UnknownPaymentException;

/**
 * Responsible for creating payment objects based on their name.
 *
 * @since 1.0.0
 */
class PaymentFactory
{
    /**
     * @param string $paymentName
     *
     * @return PaymentInterface
     * @throws UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function create($paymentName)
    {
        $mapping = $this->getMappedPayments();
        if (! isset($mapping[$paymentName])) {
            throw new UnknownPaymentException($paymentName);
        }

        return new $mapping[$paymentName]();
    }

    /**
     * Contains a list of actual supported payments by the plugin.
     *
     * @return array
     *
     * @since 1.0.0
     */
    private function getMappedPayments()
    {
        return [
            PayPalTransaction::NAME => PaypalPayment::class,
        ];
    }

    /**
     * Return true, if payment identifier matches a supported Wirecard payment
     *
     * @param string $identifier
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function isSupportedPayment($identifier)
    {
        $payments = $this->getMappedPayments();
        return isset($payments[$identifier]);
    }
}
