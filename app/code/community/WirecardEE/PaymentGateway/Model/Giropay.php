<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use Wirecard\PaymentSdk\Transaction\GiropayTransaction;

/**
 * @since 1.1.0
 */
class WirecardEE_PaymentGateway_Model_Giropay extends WirecardEE_PaymentGateway_Model_Payment
{
    protected $_code = 'wirecardee_paymentgateway_giropay';
    protected $_paymentMethod = GiropayTransaction::NAME;

    /**
     * @return $this
     *
     * @throws Mage_Core_Exception
     *
     * @since 1.1.0
     */
    public function validate()
    {
        parent::validate();
        $paymentData = Mage::app()->getRequest()->getParam('wirecardElasticEngine');

        if (empty($paymentData['giropayBic'])) {
            Mage::throwException($this->_getHelper()->__('bic_required'));
        }

        return $this;
    }
}
