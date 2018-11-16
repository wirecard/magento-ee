<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Actions;

class ErrorAction implements Action
{
    /**
     * Payment processing failed (e.g. due to an exception)
     */
    const PROCESSING_FAILED = 1;

    /**
     * The API returned a `FailureResponse`
     */
    const FAILURE_RESPONSE = 2;

    /**
     * Payment was cancelled by the consumer
     */
    const PAYMENT_CANCELED = 3;

    /**
     * @var int
     */
    protected $code;

    /**
     * @var string
     */
    protected $message;

    /**
     * @param int    $code
     * @param string $message
     *
     * @since 1.0.0
     */
    public function __construct($code, $message)
    {
        $this->code    = $code;
        $this->message = $message;
    }

    /**
     * @return int
     *
     * @since 1.0.0
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getMessage()
    {
        return $this->message;
    }
}
