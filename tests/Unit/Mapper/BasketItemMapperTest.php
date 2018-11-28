<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Mapper;

use PHPUnit\Framework\TestCase;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Item;
use WirecardEE\PaymentGateway\Mapper\BasketItemMapper;

class BasketItemMapperTest extends TestCase
{
    public function testBasket()
    {
        $mageItem = new \Mage_Sales_Model_Order_Item();
        $mageItem->setName('foo');
        $mageItem->setPriceInclTax(1000);
        $mageItem->setQtyOrdered(1);
        $mageItem->setSku('A10');
        $mageItem->setDescription('foobar');
        $mageItem->setTaxAmount(200);
        $mageItem->setTaxPercent(20);
        $mapper = new BasketItemMapper($mageItem, 'EUR');

        $item = $mapper->getItem();
        $this->assertInstanceOf(Item::class, $item);
        $this->assertEquals('A10', $item->getArticleNumber());
        $this->assertEquals(new Amount(1000, 'EUR'), $item->getPrice());
        $this->assertEquals(1, $item->getQuantity());

        $this->assertEquals([
            'name'           => 'foo',
            'quantity'       => 1,
            'amount'         => ['currency' => 'EUR', 'value' => 1000.0],
            'description'    => 'foobar',
            'article-number' => 'A10',
            'tax-rate'       => 20,
        ], $item->mappedProperties());
    }

    public function testBasketPrices()
    {
        $mageItem = new \Mage_Sales_Model_Order_Item();
        $mageItem->setName('foo');
        $mageItem->setPriceInclTax(1000.5);
        $mageItem->setQtyOrdered(1);
        $mageItem->setSku('A10');
        $mageItem->setTaxAmount(200);
        $mageItem->setTaxPercent(20);

        $mapper = new BasketItemMapper($mageItem, 'EUR');
        $this->assertEquals([
            'name'           => 'foo',
            'quantity'       => 1,
            'amount'         => ['currency' => 'EUR', 'value' => 1000.5],
            'article-number' => 'A10',
            'tax-rate'       => 20,
        ], $mapper->getItem()->mappedProperties());

        $mageItem->setDescription('foobar');

        $mapper = new BasketItemMapper($mageItem, 'EUR');
        $this->assertEquals([
            'name'           => 'foo',
            'quantity'       => 1,
            'amount'         => ['currency' => 'EUR', 'value' => 1000.5],
            'description'    => 'foobar',
            'article-number' => 'A10',
            'tax-rate'       => 20,
        ], $mapper->getItem()->mappedProperties());

        $mapper = new BasketItemMapper($mageItem, 'USD');
        $this->assertEquals([
            'name'           => 'foo',
            'quantity'       => 1,
            'amount'         => ['currency' => 'USD', 'value' => 1000.5],
            'description'    => 'foobar',
            'article-number' => 'A10',
            'tax-rate'       => 20,
        ], $mapper->getItem()->mappedProperties());
    }
}
