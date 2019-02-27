<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use Wirecard\PaymentSdk\Transaction\AlipayCrossborderTransaction;

/**
 * @since 1.1.0
 */
class WirecardEE_PaymentGateway_Model_Alipay extends WirecardEE_PaymentGateway_Model_Payment
{
    protected $_code = 'wirecardee_paymentgateway_alipay-xborder';
    protected $_paymentMethod = AlipayCrossborderTransaction::NAME;
}
