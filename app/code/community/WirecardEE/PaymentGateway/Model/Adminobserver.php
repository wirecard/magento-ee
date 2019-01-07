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
 */
class WirecardEE_PaymentGateway_Model_Adminobserver
{
    /**
     * Append html from block 'paymentgateway.sales.order' to sales order view
     * See app/design/adminhtml/default/default/template/WirecardEE/order.phtml
     *
     * @param Varien_Event_Observer $observer
     *
     * @since 1.0.0
     */
    public function getSalesOrderViewInfo(Varien_Event_Observer $observer)
    {
        /** @var Mage_Core_Block_Template $block */
        $block = $observer->getData('block');
        if (($child = $block->getChild('paymentgateway.sales.order'))) {
            $transport = $observer->getData('transport');
            if ($transport) {
                $transport->setHtml($transport->getHtml() . $child->toHtml());
            }
        }
    }
}
