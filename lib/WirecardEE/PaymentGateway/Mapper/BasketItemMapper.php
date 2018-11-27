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
class BasketItemMapper
{
    /**
     * @var \Mage_Sales_Model_Order_Item
     */
    protected $item;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @param \Mage_Sales_Model_Order_Item $item
     * @param string                       $currency
     *
     * @since 1.0.0
     */
    public function __construct(\Mage_Sales_Model_Order_Item $item, $currency)
    {
        $this->item     = $item;
        $this->currency = $currency;
    }

    /**
     * @return \Mage_Sales_Model_Order_Item
     *
     * @since 1.0.0
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @return Item
     *
     * @since 1.0.0
     */
    public function getWirecardItem()
    {
        $mageItem = $this->getItem();
        $amount   = new Amount(BasketMapper::numberFormat($mageItem->getPriceInclTax()), $this->currency);

        $item = new Item($mageItem->getName(), $amount, (int)$mageItem->getQtyOrdered());
        $item->setArticleNumber($mageItem->getSku());
        $item->setDescription($mageItem->getDescription());

        if ($amount->getValue() >= 0.0) {
            $taxAmount = new Amount(
                BasketMapper::numberFormat($mageItem->getTaxAmount() / (int)$mageItem->getQtyOrdered()),
                $this->currency
            );

            $item->setTaxRate($mageItem->getTaxPercent());
            $item->setTaxAmount($taxAmount);
        }

        return $item;
    }
}
