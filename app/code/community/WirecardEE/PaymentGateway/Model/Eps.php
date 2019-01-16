<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use Wirecard\PaymentSdk\Transaction\EpsTransaction;

/**
 * @since 1.1.0
 */
class WirecardEE_PaymentGateway_Model_Eps extends WirecardEE_PaymentGateway_Model_Payment
{
    protected $_code = 'wirecardee_paymentgateway_eps';
    protected $_paymentMethod = EpsTransaction::NAME;
}
