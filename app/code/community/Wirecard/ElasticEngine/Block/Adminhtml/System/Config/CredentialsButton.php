<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

class Wirecard_ElasticEngine_Block_Adminhtml_System_Config_CredentialsButton extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @return $this|Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setTemplate('wirecard/system/config/credentials_button.phtml');

        return $this;
    }

    /**
     * Sets the proper method specified in the `config.xml` for this button.
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $originalData = $element->getData('original_data');
        if (empty($originalData['method'])) {
            throw new RuntimeException('No method given for credentials button. ' .
                                       'Consider adding a <method> node to your button');
        }

        $this->setData([
            'method' => $originalData['method'],
        ]);

        return $this->_toHtml();
    }
}
