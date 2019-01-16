<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

/**
 * Used to display blocks only for orders with a Wirecard payment method.
 *
 * @since 1.0.0
 */
class WirecardEE_PaymentGateway_Block_Sales_Order extends Mage_Core_Block_Template
{
    /**
     * @return Mage_Sales_Model_Order
     *
     * @since 1.0.0
     */
    public function getOrder()
    {
        if (Mage::registry('current_order')) {
            return Mage::registry('current_order');
        } elseif (Mage::registry('order')) {
            return Mage::registry('order');
        }
        return null;
    }

    /**
     * Check if the current order has a Wirecard payment method.
     * Used in app/design/adminhtml/default/default/template/WirecardEE/order.phtml
     *
     * @return bool
     * @throws Mage_Core_Exception
     *
     * @since 1.0.0
     */
    public function isWirecardOrder()
    {
        $order = $this->getOrder();
        return $order && $order->getPayment()->getMethodInstance() instanceof WirecardEE_PaymentGateway_Model_Payment;
    }
}
