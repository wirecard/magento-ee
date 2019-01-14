<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use WirecardEE\PaymentGateway\Payments\Contracts\CustomFormTemplate;

/**
 * Allows for a payment method to display a custom form during the checkout process by implementing CustomFormTemplate.
 *
 * @since 1.0.0
 */
class WirecardEE_PaymentGateway_Block_Form extends Mage_Payment_Block_Form
{
    /**
     * @return string
     * @throws \WirecardEE\PaymentGateway\Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function getTemplate()
    {
        $paymentCode = $this->getMethodCode();
        $paymentName = str_replace('wirecardee_paymentgateway_', '', $paymentCode);
        $payment     = (new \WirecardEE\PaymentGateway\Service\PaymentFactory())->create($paymentName);

        if ($payment instanceof CustomFormTemplate) {
            return $payment->getFormTemplateName();
        }

        return 'WirecardEE/form.phtml';
    }

    /**
     * Add payment logo to checkout. Keep in mind that logos are not not shown by default; to show them uncomment the
     * body of this method.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getMethodLabelAfterHtml()
    {
        // $imagePath = 'images/WirecardEE/PaymentGateway/';
        // $imageName = \Mage::helper('catalog')
        //                      ->__(str_replace('wirecardee_paymentgateway_', '', $this->getMethodCode()) . '.png');
        // $filePath  = sprintf(
        //      '%s/frontend/base/default/%s', Mage::getBaseDir('skin'),
        //      $imagePath . $imageName
        // );
        //
        // if (file_exists($filePath)) {
        //      $image = $this->getSkinUrl($imagePath . $imageName);
        //      return "<img src='$image' style='float: initial !important;'>";
        // }
        return '';
    }
}
