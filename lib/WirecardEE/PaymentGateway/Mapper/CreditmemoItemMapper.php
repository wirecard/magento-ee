<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Mapper;

use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Item;

/**
 * Represents a single item from the Magento basket as object.
 *
 * @since 1.0.0
 */
class CreditmemoItemMapper extends BasketItemMapper
{
    /**
     * @var \Mage_Sales_Model_Order_Creditmemo_Item
     */
    protected $item;

    public function __construct(\Mage_Sales_Model_Order_Creditmemo_Item $item, $currency)
    {
        parent::__construct($item, $currency);
    }

    protected function getPrice()
    {
        return $this->item->getBasePriceInclTax();
    }

    protected function getQuantity()
    {
        return $this->item->getQty();
    }

    protected function getName()
    {
        return $this->item->getName();
    }

    protected function getSku()
    {
        return $this->item->getSku();
    }

    protected function getDescription()
    {
        return $this->item->getDescription();
    }

    protected function getTaxAmount()
    {
        return $this->item->getBaseTaxAmount();
    }

    protected function getTaxPercent()
    {
        return $this->item->getOrderItem()->getTaxPercent();
    }
}
