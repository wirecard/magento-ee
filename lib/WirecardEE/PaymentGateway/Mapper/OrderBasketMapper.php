<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Mapper;

use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * Represents the basket from a `Mage_Sales_Model_Order` as object.
 *
 * @since 1.1.0
 */
class OrderBasketMapper extends BasketMapper
{
    /**
     * @var \Mage_Sales_Model_Order
     */
    protected $model;

    /**
     * @param \Mage_Sales_Model_Order $order
     * @param Transaction|null        $transaction
     *
     * @since 1.2.0
     */
    public function __construct(\Mage_Sales_Model_Order $order, Transaction $transaction = null)
    {
        parent::__construct($order, $transaction);
    }

    /**
     * @return \Mage_Sales_Model_Order
     *
     * @since 1.2.0
     */
    public function getOrder()
    {
        return $this->model;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCurrency()
    {
        return $this->model->getBaseCurrencyCode();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDiscountAmount()
    {
        return $this->model->getBaseDiscountAmount();
    }

    /**
     * {@inheritdoc}
     */
    protected function getCouponCode()
    {
        return $this->model->getCouponCode();
    }

    /**
     * {@inheritdoc}
     */
    protected function getShippingTaxAmount()
    {
        return $this->model->getShippingTaxAmount();
    }

    /**
     * {@inheritdoc}
     */
    protected function getShippingDescription()
    {
        return $this->model->getShippingDescription();
    }

    /**
     * {@inheritdoc}
     */
    protected function getShippingCosts()
    {
        return $this->model->getShippingInclTax();
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems()
    {
        return $this->model->getAllVisibleItems();
    }

    /**
     * {@inheritdoc}
     */
    protected function getItemMapper()
    {
        return OrderItemMapper::class;
    }
}
