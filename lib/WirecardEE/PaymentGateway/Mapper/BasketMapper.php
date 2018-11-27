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
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\Item;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * Represents the Magento basket as object.
 *
 * @since 1.0.0
 */
class BasketMapper
{
    /**
     * @var \Mage_Sales_Model_Order
     */
    protected $order;

    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * @param \Mage_Sales_Model_Order $order
     * @param Transaction             $transaction
     *
     * @since 1.0.0
     */
    public function __construct(\Mage_Sales_Model_Order $order, Transaction $transaction)
    {
        $this->order       = $order;
        $this->transaction = $transaction;
    }

    /**
     * @return \Mage_Sales_Model_Order
     *
     * @since 1.0.0
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return Basket
     *
     * @since 1.0.0
     */
    public function getWirecardBasket()
    {
        $basket = new Basket();
        $basket->setVersion($this->transaction);

        /** @var \Mage_Sales_Model_Order_Item $item */
        foreach ($this->getOrder()->getAllVisibleItems() as $item) {
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

    /**
     * Returns all items within the basket as array.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function toArray()
    {
        return $this->order->getAllItems();
    }

    /**
     * Helper function to format numbers throughout the plugin.
     *
     * @param string|float $amount
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function numberFormat($amount)
    {
        return number_format($amount, 2, '.', '');
    }
}
