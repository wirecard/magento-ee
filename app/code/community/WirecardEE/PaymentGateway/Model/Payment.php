<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

/**
 * Parent class for payment models.
 *
 * @since 1.0.0
 */
abstract class WirecardEE_PaymentGateway_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'wirecardee_paymentgateway_payment';
    protected $_paymentMethod = 'unknown';

    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = true;
    protected $_canUseCheckout = true;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;

    protected $_formBlockType = 'paymentgateway/form';
    protected $_infoBlockType = 'paymentgateway/info';

    /**
     * Sets the redirect url for Wirecard payments.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('paymentgateway/gateway', [
            '_secure' => true,
            'method'  => $this->_paymentMethod,
        ]);
    }

    /**
     * @return string
     *
     * @since 1.2.0
     */
    public function getPaymentMethod()
    {
        return $this->_paymentMethod;
    }
}
