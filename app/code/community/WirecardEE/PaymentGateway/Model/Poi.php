<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\PoiPiaTransaction;

/**
 * @since 1.0.0
 */
class WirecardEE_PaymentGateway_Model_Poi extends WirecardEE_PaymentGateway_Model_Payment
{
    protected $_code = 'wirecardee_paymentgateway_poi';
    protected $_paymentMethod = 'poi';

    /**
     * Return available transaction types for this payment.
     *
     * @return array
     *
     * @since 1.0.0
     */
    // public function toOptionArray()
    // {
    //     return [
    //         [
    //             'value' => Operation::RESERVE,
    //             'label' => Mage::helper('catalog')->__('Authorization'),
    //         ],
    //         [
    //             'value' => Operation::PAY,
    //             'label' => Mage::helper('catalog')->__('Purchase'),
    //         ],
    //     ];
    // }
}
