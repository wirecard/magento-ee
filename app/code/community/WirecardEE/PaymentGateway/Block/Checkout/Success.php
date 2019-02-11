<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use WirecardEE\PaymentGateway\Exception\UnknownPaymentException;
use WirecardEE\PaymentGateway\Payments\PaymentInterface;
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
        return $this->getCheckoutSession()->getLastRealOrder();
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
        return $this->getPaymentFactory()->isSupportedPayment($this->getCheckoutSession()
                                                               ->getLastRealOrder()
                                                               ->getPayment());
    }

    /**
     * @return PaymentInterface
     *
     * @throws UnknownPaymentException
     *
     * @since 1.2.0
     */
    public function getPayment()
    {
        return $this->getPaymentFactory()->createFromMagePayment($this->getCheckoutSession()
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

    /**
     * @return PaymentFactory
     *
     * @since 1.2.0
     */
    protected function getPaymentFactory()
    {
        return new PaymentFactory();
    }
}
