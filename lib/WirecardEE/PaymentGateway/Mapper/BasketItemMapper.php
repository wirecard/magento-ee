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
use Wirecard\PaymentSdk\Entity\Item;

/**
 * Parent class for basket items.
 *
 * @since 1.0.0
 */
abstract class BasketItemMapper
{
    /**
     * @var \Mage_Core_Model_Abstract
     */
    protected $item;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @param \Mage_Core_Model_Abstract $item
     * @param string                    $currency
     *
     * @since 1.0.0
     */
    public function __construct(\Mage_Core_Model_Abstract $item, $currency)
    {
        $this->item     = $item;
        $this->currency = $currency;
    }

    /**
     * @return Item
     *
     * @since 1.0.0
     */
    public function getItem()
    {
        $amount = new Amount(BasketMapper::numberFormat($this->getPrice()), $this->currency);

        $item = new Item($this->getName(), $amount, (int)$this->getQuantity());
        $item->setArticleNumber($this->getSku());
        $item->setDescription($this->getDescription());

        if ($amount->getValue() >= 0.0) {
            $taxAmount = new Amount(
                BasketMapper::numberFormat($this->getTaxAmount() / (int)$this->getQuantity()),
                $this->currency
            );

            $item->setTaxRate($this->getTaxPercent());
            $item->setTaxAmount($taxAmount);
        }

        return $item;
    }

    /**
     * @return float
     *
     * @since 1.2.0
     */
    abstract protected function getPrice();

    /**
     * @return float
     *
     * @since 1.2.0
     */
    abstract protected function getQuantity();

    /**
     * @return string
     *
     * @since 1.2.0
     */
    abstract protected function getName();

    /**
     * @return string
     *
     * @since 1.2.0
     */
    abstract protected function getSku();

    /**
     * @return string
     *
     * @since 1.2.0
     */
    abstract protected function getDescription();

    /**
     * @return float
     *
     * @since 1.2.0
     */
    abstract protected function getTaxAmount();

    /**
     * @return float
     *
     * @since 1.2.0
     */
    abstract protected function getTaxPercent();
}
