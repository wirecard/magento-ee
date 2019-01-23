<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use WirecardEE\PaymentGateway\Exception\UnknownPaymentException;
use WirecardEE\PaymentGateway\Payments\Contracts\DisplayRestrictionInterface;
use WirecardEE\PaymentGateway\Payments\Payment;
use WirecardEE\PaymentGateway\Service\PaymentFactory;

/**
 * @since 1.1.0
 */
class WirecardEE_PaymentGateway_Model_PaymentFilter
{
    /**
     * @param Varien_Event_Observer $observer
     *
     * @throws UnknownPaymentException
     *
     * @since 1.1.0
     */
    public function checkPaymentAvailability(Varien_Event_Observer $observer)
    {
        /** @var WirecardEE_PaymentGateway_Model_Payment $paymentModel */
        $paymentModel = $observer->getEvent()->getData('method_instance');

        if (! $paymentModel || ! ($paymentModel instanceof WirecardEE_PaymentGateway_Model_Payment)) {
            return;
        }

        $factory = new PaymentFactory();
        $result  = $observer->getEvent()->getData('result');
        $payment = $factory->createFromPaymentModel($paymentModel);

        $config = \Mage::getStoreConfig(Payment::CONFIG_PREFIX . $payment->getName());
        if (! isset($config['active']) || ! $config['active']) {
            return;
        }

        if ($payment instanceof DisplayRestrictionInterface) {
            $result->isAvailable = $payment->checkDisplayRestrictions($this->getCheckoutSession());
        }
    }

    /**
     * @return Mage_Checkout_Model_Session|Mage_Core_Model_Abstract
     *
     * @since 1.1.0
     */
    protected function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }
}
