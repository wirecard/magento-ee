<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Mapper;

use Mage_Sales_Model_Order;
use Mage_Sales_Model_Order_Address;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Wirecard\PaymentSdk\Constant\RiskInfoDeliveryTimeFrame;
use Wirecard\PaymentSdk\Constant\RiskInfoReorder;
use WirecardEE\PaymentGateway\Mapper\RiskInfoMapper;

class RiskInfoMapperTest extends TestCase
{
    /**
     * @var Mage_Sales_Model_Order|PHPUnit_Framework_MockObject_MockObject
     */
    protected $order;

    protected function setUp()
    {
        // we need to explicitely specify the getEmail method, because it is implemented via __call
        $billingAddress = $this->getMockBuilder(Mage_Sales_Model_Order_Address::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods(['getEmail'])
            ->getMock();

        $billingAddress->method('getEmail')->willReturn('foo@bar.com');

        $this->order = $this->createMock(Mage_Sales_Model_Order::class);

        $this->order->method('getBillingAddress')->willReturn($billingAddress);
    }

    public function testGetRiskInfo()
    {
        $mapper = new RiskInfoMapper($this->order, false, false);

        $riskInfo = $mapper->getRiskInfo();
        $mapped   = $riskInfo->mappedProperties();

        $this->assertEquals([
            'delivery-mail' => 'foo@bar.com',
            'reorder-items' => RiskInfoReorder::FIRST_TIME_ORDERED
        ], $mapped);
    }

    public function testGetRiskInfoWithReorderedItems()
    {
        $mapper = new RiskInfoMapper($this->order, true, false);

        $riskInfo = $mapper->getRiskInfo();
        $mapped   = $riskInfo->mappedProperties();

        $this->assertEquals([
            'delivery-mail' => 'foo@bar.com',
            'reorder-items' => RiskInfoReorder::REORDERED
        ], $mapped);
    }

    public function testGetRiskInfoWithReorderedAndVirtualItems()
    {
        $mapper = new RiskInfoMapper($this->order, true, true);

        $riskInfo = $mapper->getRiskInfo();
        $mapped   = $riskInfo->mappedProperties();

        $this->assertEquals([
            'delivery-mail'      => 'foo@bar.com',
            'reorder-items'      => RiskInfoReorder::REORDERED,
            'delivery-timeframe' => RiskInfoDeliveryTimeFrame::ELECTRONIC_DELIVERY
        ], $mapped);
    }
}
