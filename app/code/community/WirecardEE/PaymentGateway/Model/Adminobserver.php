<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use WirecardEE\PaymentGateway\Service\SessionManager;

/**
 * @since 1.0.0
 * @codingStandardsIgnoreStart
 */
class WirecardEE_PaymentGateway_Model_Adminobserver
{
    // @codingStandardsIgnoreEnd
    public function getSalesOrderViewInfo(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();
        if (($child = $block->getChild('paymentgateway.sales.order'))) {
            $transport = $observer->getTransport();
            if ($transport) {
                $html = $transport->getHtml();
                $html .= $child->toHtml();
                $transport->setHtml($html);
            }
        }
    }
}
