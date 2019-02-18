<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

/**
 * Required for connecting our model to the database.
 *
 * @since 1.2.0
 */
class WirecardEE_PaymentGateway_Model_Resource_CreditCardVaultToken extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * @since 1.2.0
     */
    protected function _construct()
    {
        $this->_init('paymentgateway/credit_card_vault_token', 'id');
    }
}
