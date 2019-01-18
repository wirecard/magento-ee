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
use Wirecard\PaymentSdk\Transaction\EpsTransaction;
use Wirecard\PaymentSdk\Transaction\GiropayTransaction;
use Wirecard\PaymentSdk\Transaction\IdealTransaction;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction;
use Wirecard\PaymentSdk\Transaction\SofortTransaction;
use WirecardEE\PaymentGateway\Exception\UnknownPaymentException;
use WirecardEE\PaymentGateway\Payments\CreditCardPayment;
use WirecardEE\PaymentGateway\Payments\EpsPayment;
use WirecardEE\PaymentGateway\Payments\GiropayPayment;
use WirecardEE\PaymentGateway\Payments\IdealPayment;
use WirecardEE\PaymentGateway\Payments\PaymentInterface;
use WirecardEE\PaymentGateway\Payments\PaypalPayment;
use WirecardEE\PaymentGateway\Payments\PoiPayment;
use WirecardEE\PaymentGateway\Payments\PiaPayment;
use WirecardEE\PaymentGateway\Payments\SepaPayment;
use WirecardEE\PaymentGateway\Payments\SofortPayment;

/**
 * Responsible for creating payment objects based on their name.
 *
 * @since 1.0.0
 */
class PaymentFactory
{
    /**
     * Prefix for the payments provided by this plugin
     */
    const PAYMENT_PREFIX = 'wirecardee_paymentgateway_';

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
     * Create and return all supported payments
     *
     * @return PaymentInterface[]
     * @throws UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function getSupportedPayments()
    {
        $payments = [];

        foreach (array_keys($this->getMappedPayments()) as $identifier) {
            $payments[] = $this->create($identifier);
        }

        return $payments;
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
            PayPalTransaction::NAME          => PaypalPayment::class,
            CreditCardTransaction::NAME      => CreditCardPayment::class,
            SepaDirectDebitTransaction::NAME => SepaPayment::class,
            SofortTransaction::NAME          => SofortPayment::class,
            EpsTransaction::NAME             => EpsPayment::class,
            GiropayTransaction::NAME         => GiropayPayment::class,
            EpsTransaction::NAME             => EpsPayment::class,
            IdealPayment::NAME               => IdealPayment::class,
            'poi'                            => PoiPayment::class,
            'pia'                            => PiaPayment::class,
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
        if (strpos($magePayment->getData('method'), self::PAYMENT_PREFIX) !== 0) {
            return false;
        }

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
        return str_replace(self::PAYMENT_PREFIX, '', $magePayment->getData('method'));
    }
}
