<?php

namespace WirecardEE\PaymentGateway\Data;

use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Item;

class BasketItemMapper
{
    /** @var \Mage_Sales_Model_Order_Item */
    protected $item;

    protected $currency;

    public function __construct(\Mage_Sales_Model_Order_Item $item, $currency)
    {
        $this->item     = $item;
        $this->currency = $currency;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function getWirecardItem()
    {
        $amount = new Amount($this->getItem()->getPriceInclTax(), $this->currency);

        $item = new Item($this->getItem()->getName(), $amount, (int)$this->getItem()->getQtyOrdered());
        $item->setArticleNumber($this->getItem()->getProductId());
        $item->setDescription($this->getItem()->getDescription());

        if ($amount->getValue() >= 0.0) {
            $taxAmount = new Amount(
                BasketMapper::numberFormat($this->getItem()->getTaxAmount() / $this->getItem()->getQtyOrdered()),
                $this->currency
            );

            $item->setTaxRate($this->getItem()->getTaxPercent());
            $item->setTaxAmount($taxAmount);
        }

        var_dump($item);

        return $item;
    }
}
