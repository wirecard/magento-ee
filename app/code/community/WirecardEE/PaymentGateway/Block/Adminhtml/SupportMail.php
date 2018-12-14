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
class WirecardEE_PaymentGateway_Block_Adminhtml_SupportMail extends Mage_Adminhtml_Block_Widget_Form_Container
{
    // @codingStandardsIgnoreEnd
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'paymentgateway';
        $this->_controller = 'adminhtml';
        $this->_mode = "supportMail";
        $this->_updateButton('save', 'label', Mage::helper('paymentgateway')->__('Submit'));
        $this->_updateButton('delete', 'label', Mage::helper('paymentgateway')->__('Delete'));
    }

    public function getHeaderText()
    {
        return Mage::helper('paymentgateway')->__('My Form Container');
    }
}
