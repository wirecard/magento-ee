<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Service;

use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use WirecardEE\PaymentGateway\Exception\UnknownPaymentException;
use WirecardEE\PaymentGateway\Payments\CreditCardPayment;
use WirecardEE\PaymentGateway\Payments\PaymentInterface;
use WirecardEE\PaymentGateway\Payments\PaypalPayment;

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
     * @param \Mage_Sales_Model_Order_Payment $magePayment
     *
     * @return PaymentInterface
     * @throws UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function createFromMagePayment(\Mage_Sales_Model_Order_Payment $magePayment)
    {
        return $this->create($this->getMagePaymentName($magePayment));
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
            PayPalTransaction::NAME     => PaypalPayment::class,
            CreditCardTransaction::NAME => CreditCardPayment::class,
        ];
    }

    /**
     * Return true, if payment matches a supported Wirecard payment
     *
     * @param \Mage_Sales_Model_Order_Payment $magePayment
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function isSupportedPayment(\Mage_Sales_Model_Order_Payment $magePayment)
    {
        $payments = $this->getMappedPayments();
        return isset($payments[$this->getMagePaymentName($magePayment)]);
    }

    /**
     * @param \Mage_Sales_Model_Order_Payment $magePayment
     *
     * @return mixed
     *
     * @since 1.0.0
     */
    private function getMagePaymentName(\Mage_Sales_Model_Order_Payment $magePayment)
    {
        return str_replace('wirecardee_paymentgateway_', '', $magePayment->getData('method'));
    }
}
