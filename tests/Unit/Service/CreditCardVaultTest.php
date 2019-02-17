<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Service;

use WirecardEE\PaymentGateway\Service\CreditCardVault;
use WirecardEE\Tests\Test\MagentoTestCase;

class CreditCardVaultTest extends MagentoTestCase
{
    public function testSaveToken()
    {
        $billingAddress  = $this->createMock(\Mage_Sales_Model_Order_Address::class);
        $shippingAddress = $this->createMock(\Mage_Sales_Model_Order_Address::class);

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('__call')->willReturnMap([
            ['getCustomerId', [], 1],
        ]);
        $order->method('getShippingAddress')->willReturn($shippingAddress);
        $order->method('getBillingAddress')->willReturn($billingAddress);

        $connection = $this->createMock(\Varien_Db_Adapter_Interface::class);
        $connection->expects($this->once())->method('insertMultiple');

        $resource = $this->createMock(\Mage_Core_Model_Resource::class);
        $resource->method('getConnection')->with('core_write')->willReturn($connection);

        $creditCardVault = new CreditCardVault($resource);
        $creditCardVault->saveToken(
            $order,
            'abc123',
            '123***321',
            [
                'data' => 'test',
            ]
        );
    }
}
