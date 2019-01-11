<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use WirecardEE\PaymentGateway\Service\PaymentFactory;

/**
 * Return payment status to output on Checkout Success page
 *
 * @since 1.0.0
 */
class WirecardEE_PaymentGateway_Block_Checkout_Success extends Mage_Checkout_Block_Onepage_Success
{
    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getOrderStatusLabel()
    {
        $order = $this->getCheckoutSession()->getLastRealOrder();
        return $order->getStatusLabel();
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getOrderState()
    {
        return $this->getCheckoutSession()->getLastRealOrder()->getState();
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function isWirecardPayment()
    {
        return (new PaymentFactory())->isSupportedPayment($this->getCheckoutSession()
                                                               ->getLastRealOrder()
                                                               ->getPayment());
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
