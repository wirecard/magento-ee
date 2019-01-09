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
use WirecardEE\PaymentGateway\Actions\SuccessAction;

class SuccessActionTest extends TestCase
{
    public function testDefault()
    {
        $success = new SuccessAction();
        $this->assertInstanceOf(Action::class, $success);
        $this->assertNull($success->getContext());
        $this->assertNull($success->getContextItem('foo'));
    }

    public function testContext()
    {
        $success = new SuccessAction(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $success->getContext());
        $this->assertEquals('bar', $success->getContextItem('foo'));
        $this->assertNull($success->getContextItem('bar'));
    }
}
