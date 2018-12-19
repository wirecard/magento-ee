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
class WirecardEE_PaymentGateway_Block_Checkout_Success extends Mage_Checkout_Block_Onepage_Success
{
    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getPaymentStatus()
    {
        $order = $this->getCheckoutSession()->getLastRealOrder();
        return $order->getStatusLabel();
    }

    /**
     * @return Mage_Checkout_Model_Session|Mage_Core_Model_Abstract
     *
     * @since 1.0.0
     */
    protected function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }
}
