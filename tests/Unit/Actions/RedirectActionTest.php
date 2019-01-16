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
use WirecardEE\PaymentGateway\Actions\RedirectAction;

class RedirectActionTest extends TestCase
{
    public function testInstance()
    {
        $redirect = new RedirectAction('https://localhost/redirect');
        $this->assertInstanceOf(Action::class, $redirect);
        $this->assertEquals('https://localhost/redirect', $redirect->getUrl());
    }
}
