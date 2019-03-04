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
 * Represents the basket from a `Mage_Sales_Model_Order_Invoice` as object.
 *
 * @since 1.2.0
 */
class InvoiceBasketMapper extends BasketMapper
{
    /**
     * @var \Mage_Sales_Model_Order_Invoice
     */
    protected $model;

    /**
     * @var array
     */
    protected $invoicedOrderItems;

    /**
     * @param \Mage_Sales_Model_Order_Invoice $invoice
     * @param array                           $invoicedOrderItems
     * @param Transaction|null                $transaction
     *
     * @since 1.2.0
     */
    public function __construct(
        \Mage_Sales_Model_Order_Invoice $invoice,
        array $invoicedOrderItems,
        Transaction $transaction = null
    ) {
        $this->invoicedOrderItems = $invoicedOrderItems;

        parent::__construct($invoice, $transaction);
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
        return $this->model->getOrder()->getCouponCode();
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
        return $this->model->getBaseShippingInclTax();
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems()
    {
        /** @var \Mage_Sales_Model_Order_Invoice_Item[] $items */
        $items = $this->model->getAllItems();

        $invoicedItems = [];
        foreach ($items as $item) {
            if (! array_key_exists($item->getData('order_item_id'), $this->invoicedOrderItems)) {
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
        return InvoiceItemMapper::class;
    }
}
