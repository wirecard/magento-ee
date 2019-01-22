<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use WirecardEE\PaymentGateway\Payments\PoiPayment;

class WirecardEE_PaymentGateway_Model_Poi extends WirecardEE_PaymentGateway_Model_Payment
{
    protected $_code = 'wirecardee_paymentgateway_poi';
    protected $_paymentMethod = PoiPayment::NAME;
}
