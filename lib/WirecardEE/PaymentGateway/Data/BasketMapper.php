<?php

namespace WirecardEE\PaymentGateway\Data;

use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\Item;
use Wirecard\PaymentSdk\Transaction\Transaction;

class BasketMapper
{
    /** @var \Mage_Sales_Model_Order */
    protected $order;

    /** @var Transaction */
    protected $transaction;

    public function __construct(\Mage_Sales_Model_Order $order, Transaction $transaction)
    {
        $this->order       = $order;
        $this->transaction = $transaction;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function getWirecardBasket()
    {
        $basket = new Basket();
        $basket->setVersion($this->transaction);

        foreach ($this->getOrder()->getAllItems() as $item) {
            /** @var \Mage_Sales_Model_Order_Item $item */
            $basketItem = new BasketItemMapper($item, $this->getOrder()->getBaseCurrencyCode());
            $basket->add($basketItem->getWirecardItem());
        }

        $shippingCosts = $this->getOrder()->getShippingInclTax();
        if ($shippingCosts >= 0.0) {
            $shippingAmount = new Amount(
                self::numberFormat($this->getOrder()->getShippingInclTax()),
                $this->getOrder()->getBaseCurrencyCode()
            );

            $shippingTaxValue = $shippingCosts - $this->getOrder()->getShippingTaxAmount();
            $shippingName     = $this->getOrder()->getShippingMethod();
            $description      = $this->getOrder()->getShippingDescription();

            $basketItem = new Item($shippingName, $shippingAmount, 1);
            $basketItem->setDescription($description);
            $basketItem->setArticleNumber('shipping');
            $basketItem->setTaxAmount(
                new Amount(self::numberFormat($shippingTaxValue),
                $this->getOrder()->getBaseCurrencyCode())
            );

            $basket->add($basketItem);
        }

        return $basket;
    }

    public function toArray()
    {
        return $this->order->getAllItems();
    }

    public static function numberFormat($amount)
    {
        return number_format($amount, 2, '.', '');
    }
}
