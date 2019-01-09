<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Actions;

use PHPUnit\Framework\TestCase;
use WirecardEE\PaymentGateway\Actions\Action;
use WirecardEE\PaymentGateway\Actions\ErrorAction;

class ErrorActionTest extends TestCase
{
    public function testInstance()
    {
        $error = new ErrorAction(0, 'foobar');
        $this->assertInstanceOf(Action::class, $error);
        $this->assertEquals(0, $error->getCode());
        $this->assertEquals('foobar', $error->getMessage());

        $error = new ErrorAction(ErrorAction::PAYMENT_CANCELED, 'canceled');
        $this->assertEquals(ErrorAction::PAYMENT_CANCELED, $error->getCode());
        $this->assertEquals('canceled', $error->getMessage());
    }
}
