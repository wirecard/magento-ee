<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Exception;

/**
 * @since   1.0.0
 */
class UnknownTransactionTypeException extends \Exception
{
    /**
     * @param string $operation
     *
     * @since 1.0.0
     */
    public function __construct($operation)
    {
        parent::__construct("Unknown transaction type for operation ($operation)");
    }
}
