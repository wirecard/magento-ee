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

            $basketItem = new Item('Shipping', $shippingAmount, 1);
            $basketItem->setDescription('Shipping');
            $basketItem->setArticleNumber('Shipping');
            $basketItem->setTaxAmount(
                new Amount(self::numberFormat($shippingTaxValue),
                $this->getOrder()->getBaseCurrencyCode())
            );

            $basket->add($basketItem);
        }

        $couponCode = $this->getOrder()->getCouponCode();
        if ($couponCode !== '') {
            $discountValue = new Amount(
                self::numberFormat($this->getOrder()->getBaseDiscountAmount()),
                $this->getOrder()->getBaseCurrencyCode()
            );

            $basketItem = new Item('Discount', $discountValue, 1);
            $basketItem->setDescription($couponCode);

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
