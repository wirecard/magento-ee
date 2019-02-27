<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

/**
 * @since 1.2.0
 */
class WirecardEE_PaymentGateway_Model_Payolutioninvoice extends WirecardEE_PaymentGateway_Model_Payment
{
    protected $_code = 'wirecardee_paymentgateway_payolutioninvoice';
    protected $_paymentMethod = 'payolutioninvoice';

    /**
     * @return bool
     *
     * @since 1.2.0
     */
    public function showDobForm()
    {
        /** @var Mage_Checkout_Model_Session $checkoutSession */
        $checkoutSession = Mage::getSingleton('checkout/session');
        return !! $checkoutSession->getQuote()->getCustomerDob();
    }

    /**
     * @return bool
     *
     * @since 1.2.0
     */
    public function requiresConsent()
    {
        $config = \Mage::getStoreConfig('payment/wirecardee_paymentgateway_payolutioninvoice');
        return isset($config['require_consent']) && $config['require_consent'];
    }

    public function getTermsUrl()
    {
        $config = \Mage::getStoreConfig('payment/wirecardee_paymentgateway_payolutioninvoice');
        return isset($config['terms_url']) ? $config['terms_url'] : '#';
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

        $paymentData = Mage::app()->getRequest()->getParam('wirecardElasticEngine');
        $errorMsg    = "";
        /** @var Mage_Checkout_Model_Session $checkoutSession */
        $checkoutSession = Mage::getSingleton('checkout/session');

        if ($this->requiresConsent()
            && (! isset($paymentData['payolutionConsent'])
                || $paymentData['payolutionConsent'] !== 'confirmed')) {
            $errorMsg .= $this->_getHelper()->__('payolution_consent_required_text') . PHP_EOL;
        }

        if (! $checkoutSession->getQuote()->getCustomerDob() &&
            (! isset($paymentData['birthday'])
                || empty($paymentData['birthday']['month'])
                || empty($paymentData['birthday']['day'])
                || empty($paymentData['birthday']['year']))) {
            $errorMsg .= $this->_getHelper()->__('dob_required') . PHP_EOL;
        }

        if ($errorMsg) {
            Mage::throwException($errorMsg);
        }

        return $this;
    }
}
