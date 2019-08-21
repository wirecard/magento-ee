<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Mapper;

use Mage_Sales_Model_Order;
use Wirecard\PaymentSdk\Constant\RiskInfoDeliveryTimeFrame;
use Wirecard\PaymentSdk\Constant\RiskInfoReorder;
use Wirecard\PaymentSdk\Entity\RiskInfo;

/**
 * @since 1.2.5
 */
class RiskInfoMapper
{
    /**
     * @var Mage_Sales_Model_Order
     */
    protected $order;

    /**
     * @var bool
     */
    protected $hasReorderedItems;

    /**
     * @var bool
     */
    protected $hasVirtualItems;

    public function __construct(
        Mage_Sales_Model_Order $order,
        $hasReorderedItems,
        $hasVirtualItems
    ) {
        $this->order             = $order;
        $this->hasReorderedItems = $hasReorderedItems;
        $this->hasVirtualItems   = $hasVirtualItems;
    }

    /**
     * @return RiskInfo
     */
    public function getRiskInfo()
    {
        $riskInfo = new RiskInfo();
        $address  = $this->order->getBillingAddress();
        $riskInfo->setDeliveryEmailAddress($address->getEmail());
        $riskInfo->setReorderItems($this->hasReorderedItems ?
            RiskInfoReorder::REORDERED : RiskInfoReorder::FIRST_TIME_ORDERED);
        if ($this->hasVirtualItems) {
            $riskInfo->setDeliveryTimeFrame(RiskInfoDeliveryTimeFrame::ELECTRONIC_DELIVERY);
        }

        return $riskInfo;
    }
}
