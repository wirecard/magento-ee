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
class WirecardEE_PaymentGateway_Block_Additional extends Mage_Payment_Block_Info
{
    protected $_wirecardEEHasAdditional = true;
    /**
     * @since 1.0.0
     */
    protected function _construct()
    {
        parent::_construct();

        if ($this->getAction()->getFullActionName() !== 'adminhtml_sales_order_view') {
            $this->setTemplate('WirecardEE/checkout/additional.phtml');
        }
    }

    /**
     *
     * @return string
     * @throws Mage_Core_Exception
     * @throws \WirecardEE\PaymentGateway\UnknownPaymentException
     *
     * @since 1.0.0
     */
    protected function _toHtml()
    {
        //$info = $this->getInfo();

        try {
            $payment = Mage::getSingleton('checkout/session')->getQuote()->getPayment()->getMethodInstance();
            $paymentCode = $payment->getCode();
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
