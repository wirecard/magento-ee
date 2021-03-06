<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

/**
 * Generate support mail form for Admin Support page
 *
 * @since 1.0.0
 */
class WirecardEE_PaymentGateway_Block_Adminhtml_SupportMail_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * @return Mage_Adminhtml_Block_Widget_Form
     *
     * @since 1.0.0
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form([
            'id'      => 'edit_form',
            'action'  => $this->getUrl('*/*/send'),
            'method'  => 'post',
            'enctype' => 'multipart/form-data',
        ]);
        $form->setData('use_container', true);
        $this->setForm($form);

        /** @var WirecardEE_PaymentGateway_Helper_Data $paymentHelper */
        $paymentHelper = Mage::helper('paymentgateway');

        $fieldset = $form->addFieldset('supportmail_form', [
            'legend' => $paymentHelper->__('support_email_title'),
        ]);
        $fieldset->addField('sender_address', 'text', [
            'label'    => $paymentHelper->__('support_email_sender_address'),
            'class'    => 'required-entry',
            'required' => true,
            'name'     => 'sender_address',
        ]);
        $fieldset->addField('reply_to', 'text', [
            'label'    => $paymentHelper->__('support_email_reply_to'),
            'required' => false,
            'name'     => 'reply_to',
        ]);
        $fieldset->addField('content', 'editor', [
            'name'     => 'content',
            'label'    => $paymentHelper->__('config_message'),
            'title'    => $paymentHelper->__('config_message'),
            'style'    => 'width:700px; height:500px;',
            'wysiwyg'  => false,
            'required' => true,
        ]);

        return parent::_prepareForm();
    }
}
