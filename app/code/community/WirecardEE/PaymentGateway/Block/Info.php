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
class WirecardEE_PaymentGateway_Block_Info extends Mage_Payment_Block_Info
{
    /**
     * @since 1.0.0
     */
    protected function _construct()
    {
        parent::_construct();

        if ($this->getAction()->getFullActionName() !== 'adminhtml_sales_order_view') {
            $this->setTemplate('WirecardEE/info.phtml');
        }
    }

    /**
     * Attaches the fingerprint id iframe in case the payment has fraud prevention enabled.
     *
     * @return string
     * @throws Mage_Core_Exception
     * @throws \WirecardEE\PaymentGateway\UnknownPaymentException
     *
     * @since 1.0.0
     */
    protected function _toHtml()
    {
        $info = $this->getInfo();

        $paymentCode = $info->getMethodInstance()->getCode();
        $paymentName = str_replace('wirecardee_paymentgateway_', '', $paymentCode);
        $payment     = (new \WirecardEE\PaymentGateway\Service\PaymentFactory())->create($paymentName);

        if ($payment->getPaymentConfig()->hasFraudPrevention()) {
            /** @var WirecardEE_PaymentGateway_Helper_Data $paymentHelper */
            $paymentHelper       = Mage::helper('paymentgateway');
            $deviceFingerprintId = $paymentHelper->getDeviceFingerprintId($payment->getPaymentConfig()->getTransactionMAID());

            $this->setData('WirecardEEDeviceFingerprintId', $deviceFingerprintId);
            $this->setData('WirecardEEIncludeDeviceFingerprintIFrame', true);
        }

        return parent::_toHtml();
    }
}
