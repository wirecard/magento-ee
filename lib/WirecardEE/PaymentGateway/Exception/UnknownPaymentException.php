<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway;

class UnknownPaymentException extends \Exception
{
    /**
     * @param string $paymentName
     *
     * @since 1.0.0
     */
    public function __construct($paymentName)
    {
        parent::__construct("Unknown payment '$paymentName'");
    }
}
