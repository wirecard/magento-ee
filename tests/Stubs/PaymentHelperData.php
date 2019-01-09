<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Stubs;

class PaymentHelperData extends \WirecardEE_PaymentGateway_Helper_Data
{
    public function validateBasket()
    {
    }

    public function getClientIp()
    {
        return '127.0.0.1';
    }
}
