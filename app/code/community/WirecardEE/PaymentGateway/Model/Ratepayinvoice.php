<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;

/**
 * @since 1.1.0
 */
class WirecardEE_PaymentGateway_Model_Ratepayinvoice extends WirecardEE_PaymentGateway_Model_Payment
{
    protected $_code = 'wirecardee_paymentgateway_ratepayinvoice';
    protected $_paymentMethod = RatepayInvoiceTransaction::NAME;

    /**
     * @return bool
     *
     * @since 1.1.0
     */
    public function showDobForm()
    {
        /** @var Mage_Checkout_Model_Session $checkoutSession */
        $checkoutSession = Mage::getSingleton('checkout/session');
        return ! $checkoutSession->getQuote()->getCustomerDob();
    }
}
