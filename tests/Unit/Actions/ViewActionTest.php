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
use WirecardEE\PaymentGateway\Actions\ViewAction;

class ViewActionTest extends TestCase
{
    public function testInstance()
    {
        $view = new ViewAction('block/bar', ['foo' => 'bar']);
        $this->assertInstanceOf(Action::class, $view);
        $this->assertEquals('block/bar', $view->getBlockName());
        $this->assertEquals(['foo' => 'bar'], $view->getAssignments());
    }
}
