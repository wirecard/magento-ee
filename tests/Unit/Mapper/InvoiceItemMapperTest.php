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
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Item;
use WirecardEE\PaymentGateway\Mapper\InvoiceItemMapper;

class InvoiceItemMapperTest extends TestCase
{
    public function testBasket()
    {
        $orderItem = $this->createMock(\Mage_Sales_Model_Order_Item::class);
        $orderItem->method('isDummy')->willReturn(true);

        $mageInvoiceItem = new \Mage_Sales_Model_Order_Invoice_Item();
        $mageInvoiceItem->setOrderItem($orderItem);
        $mageInvoiceItem->setName('foo');
        $mageInvoiceItem->setPriceInclTax(1000);
        $mageInvoiceItem->setQty(1);
        $mageInvoiceItem->setSku('A10');
        $mageInvoiceItem->setDescription('foobar');
        $mageInvoiceItem->setTaxAmount(200);
        $mapper = new InvoiceItemMapper($mageInvoiceItem, 'EUR');

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
        ], $item->mappedProperties());
    }
}
