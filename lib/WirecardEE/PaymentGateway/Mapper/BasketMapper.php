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
 * Parent class for basket mapper classes.
 *
 * @since 1.0.0
 */
abstract class BasketMapper
{
    /**
     * @var \Mage_Sales_Model_Abstract
     */
    protected $model;

    /**
     * @var Transaction
     */
    protected $transaction;

    public function __construct(\Mage_Sales_Model_Abstract $model, Transaction $transaction = null)
    {
        $this->model       = $model;
        $this->transaction = $transaction;
    }

    public function setTransaction(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * @return \Mage_Sales_Model_Abstract
     *
     * @since 1.0.0
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return Basket
     *
     * @since 1.0.0
     */
    public function getBasket()
    {
        $currency = $this->getCurrency();

        $basket = new Basket();
        $basket->setVersion($this->transaction);

        $itemMapper = $this->getItemMapper();
        foreach ($this->getItems() as $item) {
            /** @var BasketItemMapper $basketItem */
            $basketItem = new $itemMapper($item, $currency);
            $basket->add($basketItem->getItem());
        }

        $shippingCosts = $this->getShippingCosts();
        if ($shippingCosts > 0.0) {
            $shippingAmount = new Amount(self::numberFormat($shippingCosts), $this->getCurrency());

            $basketItem = new Item(\Mage::helper('catalog')->__('Shipping'), $shippingAmount, 1);
            $basketItem->setDescription($this->getShippingDescription());
            $basketItem->setArticleNumber('shipping');
            $basketItem->setTaxAmount(
                new Amount(self::numberFormat($this->getShippingTaxAmount()), $this->getCurrency())
            );
            $basketItem->setTaxRate(
                $this->getShippingTaxAmount() == 0
                    ? 0
                    : (($this->getShippingTaxAmount() / $shippingCosts) * 100)
            );

            $basket->add($basketItem);
        }

        $couponCode = $this->getCouponCode();
        if ($couponCode && $couponCode !== '') {
            $discountValue = new Amount(self::numberFormat($this->getDiscountAmount()), $currency);

            $basketItem = new Item(\Mage::helper('catalog')->__('Discount'), $discountValue, 1);
            $basketItem->setDescription($couponCode);
            $basketItem->setTaxRate(0);

            $basket->add($basketItem);
        }

        return $basket;
    }

    /**
     * @return float
     */
    protected abstract function getDiscountAmount();

    /**
     * @return string
     */
    protected abstract function getCouponCode();

    /**
     * @return float
     */
    protected abstract function getShippingTaxAmount();

    /**
     * @return string
     */
    protected abstract function getShippingDescription();

    /**
     * @return float
     */
    protected abstract function getShippingCosts();

    /**
     * @return string
     */
    protected abstract function getCurrency();

    /**
     * @return \Mage_Sales_Model_Order_Item[]
     */
    protected abstract function getItems();

    /**
     * @return string
     */
    protected abstract function getItemMapper();

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
