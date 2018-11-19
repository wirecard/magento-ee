<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

class WirecardEE_PaymentGateway_Block_Info extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('WirecardEE/info.phtml');
    }

    protected function _toHtml()
    {
        $info = $this->getInfo();

        $paymentCode  = $info->getMethodInstance()->getCode();
        $paymentName  = str_replace('wirecardee_paymentgateway_', '', $paymentCode);
        $payment      = (new \WirecardEE\PaymentGateway\Service\PaymentFactory())->create($paymentName);

        if ($payment->getPaymentConfig()->hasFraudPrevention()) {
            $deviceFingerprintId = Mage::helper('paymentgateway')
                                       ->getDeviceFingerprintId($payment->getPaymentConfig()->getTransactionMAID());

            $this->setData('WirecardEEDeviceFingerprintId', $deviceFingerprintId);
            $this->setData('WirecardEEIncludeDeviceFingerprintIFrame', true);
        }

        return parent::_toHtml();
    }
}
