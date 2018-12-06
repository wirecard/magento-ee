<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

/**
 * @since 1.0.0
 * @codingStandardsIgnoreStart
 */
class WirecardEE_PaymentGateway_Block_Form_Sepa extends WirecardEE_PaymentGateway_Block_Form
{
    /**
     * @since 1.0.0
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('WirecardEE/form/sepa.phtml');
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getMethodLabelAfterHtml()
    {
        try {
            $paymentCode = $this->getMethodCode();
            $paymentName = str_replace('wirecardee_paymentgateway_', '', $paymentCode);
            $payment     = (new \WirecardEE\PaymentGateway\Service\PaymentFactory())->create($paymentName);

            if ($payment instanceof \WirecardEE\PaymentGateway\Payments\Contracts\AdditionalViewAssignmentsInterface) {
                $this->_wirecardEEHasAdditional = true;

                $additionalViewAssignments = $payment->getAdditionalViewAssignments();
                $this->setData('WirecardEEAdditionalViewAssignments', $additionalViewAssignments);
            }
        } catch(\Exception $e) {
        }
        return parent::_toHtml();
    }
}
