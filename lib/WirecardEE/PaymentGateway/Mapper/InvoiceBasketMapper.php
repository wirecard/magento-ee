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
 * Represents the basket from a `Mage_Sales_Model_Order_Invoice` as object.
 *
 * @since 1.1.0
 */
class InvoiceBasketMapper extends BasketMapper
{
    /**
     * @var \Mage_Sales_Model_Order_Invoice
     */
    protected $model;

    public function __construct(\Mage_Sales_Model_Order_Invoice $invoice, Transaction  $transaction = null)
    {
        parent::__construct($invoice, $transaction);
    }

    protected function getCurrency()
    {
        return $this->model->getBaseCurrencyCode();
    }

    /**
     * @return float
     */
    protected function getDiscountAmount()
    {
        return $this->model->getBaseDiscountAmount();
    }

    /**
     * @return string
     */
    protected function getCouponCode()
    {
        return $this->model->getOrder()->getCouponCode();
    }

    /**
     * @return float
     */
    protected function getShippingTaxAmount()
    {
        return $this->model->getShippingTaxAmount();
    }

    /**
     * @return string
     */
    protected function getShippingDescription()
    {
        return $this->model->getOrder()->getShippingDescription();
    }

    /**
     * @return float
     */
    protected function getShippingCosts()
    {
        return $this->model->getBaseShippingInclTax();
    }

    /**
     * @return \Mage_Sales_Model_Order_Invoice_Item[]
     */
    protected function getItems()
    {
        return $this->model->getAllItems();
    }

    protected function getItemMapper()
    {
        return InvoiceItemMapper::class;
    }
}
