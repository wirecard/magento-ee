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
 * Represents a single item from the Magento basket as object.
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

    abstract protected function getPrice();

    abstract protected function getQuantity();

    abstract protected function getName();

    abstract protected function getSku();

    abstract protected function getDescription();

    abstract protected function getTaxAmount();

    abstract protected function getTaxPercent();
}
