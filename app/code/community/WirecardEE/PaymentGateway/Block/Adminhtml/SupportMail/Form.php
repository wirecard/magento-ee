<?php

class WirecardEE_PaymentGateway_Block_Adminhtml_SupportMail_Form extends Mage_Adminhtml_Block_Widget_Form
{
	protected function _prepareForm()
	{
        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getUrl('*/*/send'),
            'method' => 'post',
            'enctype' => 'multipart/form-data'
        )
        );
        $form->setUseContainer(true);
        $this->setForm($form);

        $fieldset = $form->addFieldset('supportmail_form',array('legend'=>Mage::helper('paymentgateway')->__('Item information')));
        $fieldset->addField('sender_address', 'text', array(
            'label' => Mage::helper('paymentgateway')->__('Sender Address'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'sender_address',
        ));
        $fieldset->addField('reply_to', 'text', array(
            'label' => Mage::helper('paymentgateway')->__('Reply To'),
            'required' => false,
            'name' => 'reply_to',
        ));

        $fieldset->addField('content', 'editor', array(
            'name' => 'content',
            'label' => Mage::helper('paymentgateway')->__('Content'),
            'title' => Mage::helper('paymentgateway')->__('Content'),
            'style' => 'width:700px; height:500px;',
            'wysiwyg' => false,
            'required' => true,
        ));

        return parent::_prepareForm();
	}
}
