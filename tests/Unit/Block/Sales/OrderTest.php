<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Block\Sales;

use WirecardEE\Tests\Test\MagentoTestCase;

class OrderTest extends MagentoTestCase
{
    public function setUp()
    {
        $this->requireFile('Block/Sales/Order.php');
    }

    public function testWirecardOrder()
    {
        $method = new \WirecardEE_PaymentGateway_Model_Creditcard();

        $payment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);
        $payment->method('getMethodInstance')->willReturn($method);

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getPayment')->willReturn($payment);

        $block     = new \WirecardEE_PaymentGateway_Block_Sales_Order();
        $tempOrder = \Mage::registry('current_order');
        if ($tempOrder) {
            \Mage::unregister('current_order');
        }

        \Mage::register('current_order', $order);

        $this->assertEquals($order, $block->getOrder());
        $this->assertTrue($block->isWirecardOrder());

        \Mage::unregister('current_order');
        if ($tempOrder) {
            \Mage::register('current_order', $tempOrder);
        }
    }

    public function testNonWirecardOrder()
    {
        $method = new \Mage_Payment_Model_Method_Free();

        $payment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);
        $payment->method('getMethodInstance')->willReturn($method);

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getPayment')->willReturn($payment);

        $block     = new \WirecardEE_PaymentGateway_Block_Sales_Order();
        $tempOrder = \Mage::registry('current_order');
        if ($tempOrder) {
            \Mage::unregister('current_order');
        }

        \Mage::register('current_order', $order);

        $this->assertEquals($order, $block->getOrder());
        $this->assertFalse($block->isWirecardOrder());

        \Mage::unregister('current_order');
        if ($tempOrder) {
            \Mage::register('current_order', $tempOrder);
        }
    }
}
