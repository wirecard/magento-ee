<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Mapper;

use PHPUnit\Framework\TestCase;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardEE\PaymentGateway\Mapper\BasketMapper;
use WirecardEE\PaymentGateway\Mapper\OrderBasketMapper;

class OrderBasketMapperTest extends TestCase
{
    public function testBasket()
    {
        $itemA = new \Mage_Sales_Model_Order_Item();
        $itemA->setName('foo');
        $itemA->setPriceInclTax(1000);
        $itemA->setQtyOrdered(1);
        $itemA->setSku('A10');
        $itemA->setDescription('foobar');
        $itemA->setTaxPercent(15);

        $itemB = new \Mage_Sales_Model_Order_Item();
        $itemB->setName('bar');
        $itemB->setPriceInclTax(500);
        $itemB->setQtyOrdered(2);
        $itemB->setSku('B11');
        $itemB->setTaxPercent(20);

        /** @var \Mage_Sales_Model_Order|\PHPUnit_Framework_MockObject_MockObject $order */
        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->expects($this->atLeastOnce())->method('__call')->willReturnMap([
            ['getBaseCurrencyCode', [], 'EUR'],
            ['getShippingInclTax', [], 0.0],
            ['getCouponCode', [], null],
        ]);
        $order->expects($this->atLeastOnce())->method('getAllVisibleItems')->willReturn([$itemA, $itemB]);
        /** @var Transaction|\PHPUnit_Framework_MockObject_MockObject $transaction */
        $transaction = $this->createMock(Transaction::class);

        $mapper = new OrderBasketMapper($order, $transaction);
        $this->assertSame($order, $mapper->getOrder());
        $basket = $mapper->getBasket();
        $this->assertInstanceOf(Basket::class, $basket);
        $this->assertEquals([
            'order-item' => [
                [
                    'name'           => 'foo',
                    'quantity'       => 1,
                    'amount'         => ['currency' => 'EUR', 'value' => 1000.0],
                    'description'    => 'foobar',
                    'article-number' => 'A10',
                    'tax-rate'       => 15,
                ],
                [
                    'name'           => 'bar',
                    'quantity'       => 2,
                    'amount'         => ['currency' => 'EUR', 'value' => 500.0],
                    'article-number' => 'B11',
                    'tax-rate'       => 20,
                ],
            ],
        ], $basket->mappedProperties());
    }

    public function testBasketWithShipping()
    {
        $itemA = new \Mage_Sales_Model_Order_Item();
        $itemA->setName('foo');
        $itemA->setPriceInclTax(1000);
        $itemA->setQtyOrdered(1);
        $itemA->setSku('A10');
        $itemA->setDescription('foobar');
        $itemA->setTaxPercent(15);

        /** @var \Mage_Sales_Model_Order|\PHPUnit_Framework_MockObject_MockObject $order */
        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->expects($this->atLeastOnce())->method('__call')->willReturnMap([
            ['getBaseCurrencyCode', [], 'EUR'],
            ['getShippingInclTax', [], 10.0],
            ['getShippingDescription', [], 'Shipping Description'],
            ['getShippingTaxAmount', [], 2.0],
            ['getCouponCode', [], null],
        ]);
        $order->expects($this->atLeastOnce())->method('getAllVisibleItems')->willReturn([$itemA]);
        /** @var Transaction|\PHPUnit_Framework_MockObject_MockObject $transaction */
        $transaction = $this->createMock(Transaction::class);

        $mapper = new OrderBasketMapper($order, $transaction);
        $this->assertSame($order, $mapper->getOrder());
        $this->assertEquals([
            'order-item' => [
                [
                    'name'           => 'foo',
                    'quantity'       => 1,
                    'amount'         => ['currency' => 'EUR', 'value' => 1000.0],
                    'description'    => 'foobar',
                    'article-number' => 'A10',
                    'tax-rate'       => 15,
                ],
                [
                    'name'           => 'Shipping',
                    'quantity'       => 1,
                    'amount'         => ['currency' => 'EUR', 'value' => 10.0],
                    'description'    => 'Shipping Description',
                    'article-number' => 'shipping',
                ],
            ],
        ], $mapper->getBasket()->mappedProperties());
    }

    public function testBasketWithCoupon()
    {
        $itemA = new \Mage_Sales_Model_Order_Item();
        $itemA->setName('foo');
        $itemA->setPriceInclTax(1000);
        $itemA->setQtyOrdered(1);
        $itemA->setSku('A10');
        $itemA->setDescription('foobar');
        $itemA->setTaxPercent(15);

        /** @var \Mage_Sales_Model_Order|\PHPUnit_Framework_MockObject_MockObject $order */
        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->expects($this->atLeastOnce())->method('__call')->willReturnMap([
            ['getBaseCurrencyCode', [], 'USD'],
            ['getShippingInclTax', [], 0.0],
            ['getCouponCode', [], 'ABC'],
            ['getBaseDiscountAmount', [], -100.5],
        ]);
        $order->expects($this->atLeastOnce())->method('getAllVisibleItems')->willReturn([$itemA]);
        /** @var Transaction|\PHPUnit_Framework_MockObject_MockObject $transaction */
        $transaction = $this->createMock(Transaction::class);

        $mapper = new OrderBasketMapper($order, $transaction);
        $this->assertSame($order, $mapper->getOrder());
        $this->assertEquals([
            'order-item' => [
                [
                    'name'           => 'foo',
                    'quantity'       => 1,
                    'amount'         => ['currency' => 'USD', 'value' => 1000.0],
                    'description'    => 'foobar',
                    'article-number' => 'A10',
                    'tax-rate'       => 15,
                ],
                [
                    'name'        => 'Discount',
                    'quantity'    => 1,
                    'amount'      => ['currency' => 'USD', 'value' => -100.5],
                    'description' => 'ABC',
                    'tax-rate'    => 0
                ],
            ],
        ], $mapper->getBasket()->mappedProperties());
    }

    /**
     * @dataProvider numberFormatProvider
     *
     * @param string       $expected
     * @param string|float $amount
     */
    public function testNumberFormat($expected, $amount)
    {
        $this->assertSame($expected, BasketMapper::numberFormat($amount));
    }

    public function numberFormatProvider()
    {
        return [
            ['0.00', 0],
            ['1.00', 1],
            ['-1.00', -1],
            ['1.10', 1.10],
            ['-1.10', -1.1],
            ['10000.50', 10000.5],
            ['10.99', 10.99],
            ['11.00', 10.99999999],
            ['11.00', 10.995],
            ['10.99', 10.9949],
            ['10.00', 10.0001],
            ['0.00', '0'],
            ['1.00', '1'],
            ['-1.00', '-1'],
            ['1.10', '1.10'],
            ['-1.10', '-1.1'],
            ['10000.50', '10000.5'],
            ['10.99', '10.99'],
            ['11.00', '10.99999999'],
            ['11.00', '10.995'],
            ['10.99', '10.9949'],
            ['10.00', '10.0001'],
        ];
    }
}
