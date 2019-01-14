<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

/**
 * Display plugin version in Admin Wirecard Settings page
 *
 * @since 1.0.0
 */
class WirecardEE_PaymentGateway_Block_Adminhtml_System_Config_PluginVersion
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * Render element html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        /** @var \WirecardEE_PaymentGateway_Helper_Data $paymentHelper */
        $paymentHelper = \Mage::helper('paymentgateway');

        $html = '<td class="label">' . $element->getData('label') . '</td>';
        $html .= '<td class="value">' . $paymentHelper->getPluginVersion() . '</td>';
        return '<tr id="row_' . $element->getHtmlId() . '">' . $html . '</tr>';
    }
}
