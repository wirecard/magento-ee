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
    public function getBasket()
    {
        $order    = $this->getOrder();
        $currency = $order->getBaseCurrencyCode();

        $basket = new Basket();
        $basket->setVersion($this->transaction);

        /** @var \Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $basketItem = new BasketItemMapper($item, $currency);
            $basket->add($basketItem->getItem());
        }

        $shippingCosts = $order->getShippingInclTax();
        if ($shippingCosts > 0.0) {
            $shippingAmount = new Amount(self::numberFormat($shippingCosts), $currency);

            $basketItem = new Item(\Mage::helper('catalog')->__('Shipping'), $shippingAmount, 1);
            $basketItem->setDescription($order->getShippingDescription());
            $basketItem->setArticleNumber('shipping');
            $basketItem->setTaxAmount(
                new Amount(self::numberFormat($order->getShippingTaxAmount()), $currency)
            );
            $basketItem->setTaxRate(
                $order->getShippingTaxAmount() == 0
                ? 0
                : (($order->getShippingTaxAmount() / $shippingCosts) * 100)
            );

            $basket->add($basketItem);
        }

        $couponCode = (string)$order->getCouponCode();
        if ($couponCode !== '') {
            $discountValue = new Amount(self::numberFormat($order->getBaseDiscountAmount()), $currency);

            $basketItem = new Item(\Mage::helper('catalog')->__('Discount'), $discountValue, 1);
            $basketItem->setDescription($couponCode);
            $basketItem->setTaxRate(0);

            $basket->add($basketItem);
        }

        return $basket;
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
