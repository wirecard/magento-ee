<?php

namespace WirecardEE\PaymentGateway\Exception;

/**
 * Thrown if actions returned by handlers are unknown / not implemented.
 *
 * @package WirecardElasticEngine\Exception
 *
 * @since   1.0.0
 */
class UnknownActionException extends \Exception
{
    /**
     * @param string $actionName
     *
     * @since 1.0.0
     */
    public function __construct($actionName)
    {
        parent::__construct("Action '$actionName' not found");
    }
}
