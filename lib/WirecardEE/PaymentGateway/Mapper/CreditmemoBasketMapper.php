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
 * Represents the basket from a `Mage_Sales_Model_Order_Creditmemo` as object.
 *
 * @since 1.2.0
 */
class CreditmemoBasketMapper extends BasketMapper
{
    /**
     * @var \Mage_Sales_Model_Order_Creditmemo
     */
    protected $model;

    /**
     * @var array
     */
    protected $refundedOrderItems;

    /**
     * @param \Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @param array                              $refundedOrderItems
     * @param Transaction|null                   $transaction
     *
     * @since 1.2.0
     */
    public function __construct(
        \Mage_Sales_Model_Order_Creditmemo $creditmemo,
        array $refundedOrderItems,
        Transaction $transaction = null
    ) {
        $this->refundedOrderItems = $refundedOrderItems;

        parent::__construct($creditmemo, $transaction);
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
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCouponCode()
    {
        return null;
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
        return $this->model->getOrder()->getShippingDescription();
    }

    /**
     * {@inheritdoc}
     */
    protected function getShippingCosts()
    {
        return $this->model->getShippingAmount();
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems()
    {
        /** @var \Mage_Sales_Model_Order_Creditmemo_Item $items */
        $items = $this->model->getAllItems();

        $invoicedItems = [];
        foreach ($items as $item) {
            if (! array_key_exists($item->getData('order_item_id'), $this->refundedOrderItems)) {
                continue;
            }

            $invoicedItems[] = $item;
        }

        return $invoicedItems;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItemMapper()
    {
        return CreditmemoItemMapper::class;
    }
}
