<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;
use WirecardEE\PaymentGateway\Payments\RatepayInvoicePayment;

/**
 * @since 1.2.0
 */
class WirecardEE_PaymentGateway_Model_Ratepayinvoice extends WirecardEE_PaymentGateway_Model_Payment
{
    protected $_code = 'wirecardee_paymentgateway_ratepayinvoice';
    protected $_paymentMethod = RatepayInvoiceTransaction::NAME;

    /**
     * @return bool
     *
     * @since 1.2.0
     */
    public function showDobForm()
    {
        /** @var Mage_Checkout_Model_Session $checkoutSession */
        $checkoutSession = Mage::getSingleton('checkout/session');
        return ! $checkoutSession->getQuote()->getCustomerDob();
    }

    /**
     * @throws Mage_Core_Exception
     *
     * @return $this
     *
     * @since 1.2.0
     */
    public function validate()
    {
        parent::validate();

        /** @var Mage_Checkout_Model_Session $checkoutSession */
        $checkoutSession = Mage::getSingleton('checkout/session');
        $errorMsg    = "";

        if ($checkoutSession->getQuote()->getCustomerDob()) {
            return $this;
        }

        $paymentData = Mage::app()->getRequest()->getParam('wirecardElasticEngine');
        if (! isset($paymentData['birthday'])
            || empty($paymentData['birthday']['month'])
            || empty($paymentData['birthday']['day'])
            || empty($paymentData['birthday']['year'])) {
            $errorMsg = $this->_getHelper()->__('dob_required');
        }

        $birthday = $checkoutSession->getQuote()->getCustomerDob()
            ? new \DateTime($checkoutSession->getQuote()->getCustomerDob())
            : (new \DateTime())->setDate(
                intval($paymentData['birthday']['year']),
                intval($paymentData['birthday']['month']),
                intval($paymentData['birthday']['day'])
            );

        if ($birthday->diff(new \DateTime())->y < RatepayInvoicePayment::MINIMUM_CONSUMER_AGE) {
            $errorMsg = Mage::helper('catalog')->__('ratepay_age_restriction');
        }

        if ($errorMsg) {
            Mage::throwException($errorMsg);
        }

        return $this;
    }
}
