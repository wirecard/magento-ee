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
 */
class WirecardEE_PaymentGateway_Block_Redirect extends Mage_Core_Block_Template
{
    /**
     * @since 1.0.0
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('WirecardEE/redirect.phtml');
    }
}
