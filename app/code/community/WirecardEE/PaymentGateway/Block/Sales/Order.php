<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

/**
 * @since 1.0.0
 * @codingStandardsIgnoreStart
 */
class WirecardEE_PaymentGateway_Block_Sales_Order extends Mage_Core_Block_Template
{
    protected $order;

    public function getOrder() {
        if (is_null($this->order)) {
            if (Mage::registry('current_order')) {
                $order = Mage::registry('current_order');
            }
            elseif (Mage::registry('order')) {
                $order = Mage::registry('order');
            }
            else {
                $order = new Varien_Object();
            }
            $this->order = $order;
        }
        return $this->order;
    }

    public function isWirecardOrder() {
        if (is_null($this->order)) {
            $this->getOrder();
        }

        $payment = $this->order->getPayment()->getMethodInstance();

        return $payment instanceof WirecardEE_PaymentGateway_Model_Payment;
    }
}
