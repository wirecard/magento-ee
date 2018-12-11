<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction;
use Wirecard\PaymentSdk\Transaction\Operation;

/**
 * @since 1.0.0
 * @codingStandardsIgnoreStart
 */
class WirecardEE_PaymentGateway_Model_Sepadirectdebit extends WirecardEE_PaymentGateway_Model_Payment
{
    protected $_code = 'wirecardee_paymentgateway_sepadirectdebit';
    protected $_paymentMethod = SepaDirectDebitTransaction::NAME;

    protected $_formBlockType = 'paymentgateway/form_sepa';

    // @codingStandardsIgnoreEnd

    /**
     * Return available transaction types for this payment.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => Operation::RESERVE,
                'label' => Mage::helper('catalog')->__('Authorization'),
            ],
            [
                'value' => Operation::PAY,
                'label' => Mage::helper('catalog')->__('Purchase'),
            ],
        ];
    }

    public function validate()
    {
        parent::validate();
        $paymentData = Mage::app()->getRequest()->getParam('wirecardElasticEngine');

        if (empty($paymentData['sepaFirstName'])) {
            $errorCode = 'invalid_data';
            $errorMsg = $this->_getHelper()->__('First Name is a required field.' . PHP_EOL);
        }

        if (empty($paymentData['sepaLastName'])) {
            $errorCode = 'invalid_data';
            $errorMsg .= $this->_getHelper()->__('Last Name is a required field.' . PHP_EOL);
        }

        if (empty($paymentData['sepaIban'])) {
            $errorCode = 'invalid_data';
            $errorMsg .= $this->_getHelper()->__('IBAN is a required field.' . PHP_EOL);
        }

        if (empty($paymentData['sepaConfirmMandate']) || $paymentData['sepaConfirmMandate'] !== 'confirmed') {
            $errorCode = 'invalid_data';
            $errorMsg .= $this->_getHelper()->__('You have to confirm the mandate.' . PHP_EOL);
        }

        if ($errorMsg) {
            Mage::throwException($errorMsg);
        }

        return $this;
    }
}
