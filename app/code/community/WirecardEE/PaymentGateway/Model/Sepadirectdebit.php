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
 */
class WirecardEE_PaymentGateway_Model_Sepadirectdebit extends WirecardEE_PaymentGateway_Model_Payment
{
    protected $_code = 'wirecardee_paymentgateway_sepadirectdebit';
    protected $_paymentMethod = SepaDirectDebitTransaction::NAME;

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
                'label' => Mage::helper('catalog')->__('text_payment_action_reserve'),
            ],
            [
                'value' => Operation::PAY,
                'label' => Mage::helper('catalog')->__('text_payment_action_pay'),
            ],
        ];
    }

    /**
     * @return string
     */
    public function getMandateText()
    {
        /** @var Mage_Adminhtml_Block_Abstract $mandateBlock */
        $mandateBlock = Mage::app()->getLayout()->createBlock('cms/block')
                            ->setData('block_id', 'wirecardee_sepa_mandate_text');
        $mandateText  = $mandateBlock->toHtml();

        if (! $mandateText) {
            $mandateTextForm = Mage::helper('catalog')
                                   ->__('sepa_mandate_full_text');

            $mandateText = sprintf(
                $mandateTextForm,
                Mage::getStoreConfig('payment/wirecardee_paymentgateway_sepadirectdebit/creditor_name')
            );
        }

        return $mandateText;
    }

    /**
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function validate()
    {
        parent::validate();
        $paymentData = Mage::app()->getRequest()->getParam('wirecardElasticEngine');
        $errorMsg    = "";

        if (empty($paymentData['sepaLastName'])) {
            $errorMsg .= $this->_getHelper()->__('sepa_last_name_required') . PHP_EOL;
        }
        if (empty($paymentData['sepaIban'])) {
            $errorMsg .= $this->_getHelper()->__('sepa_iban_required') . PHP_EOL;
        }
        if (empty($paymentData['sepaConfirmMandate']) || $paymentData['sepaConfirmMandate'] !== 'confirmed') {
            $errorMsg .= $this->_getHelper()->__('sepa_mandate_not_confirmed') . PHP_EOL;
        }

        if ($errorMsg) {
            Mage::throwException($errorMsg);
        }

        return $this;
    }
}
