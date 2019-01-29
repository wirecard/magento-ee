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
 * @since 1.1.0
 */
class CreditmemoBasketMapper extends BasketMapper
{
    /**
     * @var \Mage_Sales_Model_Order_Creditmemo
     */
    protected $model;

    public function __construct(\Mage_Sales_Model_Order_Creditmemo $creditmemo, Transaction $transaction = null)
    {
        parent::__construct($creditmemo, $transaction);
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
        return null;
    }

    /**
     * @return string
     */
    protected function getCouponCode()
    {
        return null;
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
     * @return \Mage_Sales_Model_Order_Item[]
     */
    protected function getItems()
    {
        return $this->model->getAllItems();
    }

    protected function getItemMapper()
    {
        return CreditmemoItemMapper::class;
    }
}
