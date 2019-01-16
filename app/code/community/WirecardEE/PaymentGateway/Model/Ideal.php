<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use Wirecard\PaymentSdk\Entity\IdealBic;
use Wirecard\PaymentSdk\Transaction\IdealTransaction;

/**
 * @since 1.1.0
 */
class WirecardEE_PaymentGateway_Model_Ideal extends WirecardEE_PaymentGateway_Model_Payment
{
    protected $_code = 'wirecardee_paymentgateway_ideal';
    protected $_paymentMethod = IdealTransaction::NAME;

    /**
     * @return array
     *
     * @since 1.1.0
     */
    public function getBanks()
    {
        return IdealBic::toArray();
    }
}
