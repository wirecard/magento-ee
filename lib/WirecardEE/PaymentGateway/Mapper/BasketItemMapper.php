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
        $amount = new Amount(BasketMapper::numberFormat($this->getItem()->getPriceInclTax()), $this->currency);

        $item = new Item($this->getItem()->getName(), $amount, (int)$this->getItem()->getQtyOrdered());
        $item->setArticleNumber($this->getItem()->getSku());
        $item->setDescription($this->getItem()->getDescription());

        if ($amount->getValue() >= 0.0) {
            $taxAmount = new Amount(
                BasketMapper::numberFormat($this->getItem()->getTaxAmount() / $this->getItem()->getQtyOrdered()),
                $this->currency
            );

            $item->setTaxRate($this->getItem()->getTaxPercent());
            $item->setTaxAmount($taxAmount);
        }

        return $item;
    }
}
