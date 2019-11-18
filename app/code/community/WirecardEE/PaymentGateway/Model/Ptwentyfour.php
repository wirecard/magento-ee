<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use WirecardEE\PaymentGateway\Payments\PtwentyfourPayment;

/**
 * @since 2.0.0
 */
class WirecardEE_PaymentGateway_Model_Ptwentyfour extends WirecardEE_PaymentGateway_Model_Payment
{
    protected $_code = 'wirecardee_paymentgateway_ptwentyfour';
    protected $_paymentMethod = PtwentyfourPayment::NAME;
}

