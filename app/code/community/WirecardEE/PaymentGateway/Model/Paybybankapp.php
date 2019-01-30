<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use Wirecard\PaymentSdk\Transaction\PayByBankAppTransaction;

/**
 * @since 1.0.0
 */
class WirecardEE_PaymentGateway_Model_Paybybankapp extends WirecardEE_PaymentGateway_Model_Payment
{
    protected $_code = 'wirecardee_paymentgateway_zapp';
    protected $_paymentMethod = PayByBankAppTransaction::NAME;
}
