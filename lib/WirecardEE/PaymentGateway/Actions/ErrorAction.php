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
