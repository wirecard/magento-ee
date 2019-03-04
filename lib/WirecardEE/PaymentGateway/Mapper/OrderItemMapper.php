<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Mapper;

/**
 * Represents a single item from the Magento basket as object.
 *
 * @since 1.2.0
 */
class OrderItemMapper extends BasketItemMapper
{
    /**
     * @var \Mage_Sales_Model_Order_Item
     */
    protected $item;

    /**
     * @param \Mage_Sales_Model_Order_Item $item
     * @param string                       $currency
     */
    public function __construct(\Mage_Sales_Model_Order_Item $item, $currency)
    {
        parent::__construct($item, $currency);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPrice()
    {
        return $this->item->getBasePriceInclTax();
    }

    /**
     * {@inheritdoc}
     */
    protected function getQuantity()
    {
        return $this->item->getQtyOrdered();
    }

    /**
     * {@inheritdoc}
     */
    protected function getName()
    {
        return $this->item->getName();
    }

    /**
     * {@inheritdoc}
     */
    protected function getSku()
    {
        return $this->item->getSku();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDescription()
    {
        return $this->item->getDescription();
    }

    /**
     * {@inheritdoc}
     */
    protected function getTaxAmount()
    {
        return $this->item->getBaseTaxAmount();
    }

    /**
     * {@inheritdoc}
     */
    protected function getTaxPercent()
    {
        return $this->item->getTaxPercent();
    }
}
