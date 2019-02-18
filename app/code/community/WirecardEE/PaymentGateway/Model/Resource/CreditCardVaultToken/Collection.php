<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

/**
 * Magento collection for credit card vault tokens.
 *
 * @since 1.2.0
 */
class WirecardEE_PaymentGateway_Model_Resource_CreditCardVaultToken_Collection extends
    Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * @since 1.2.0
     */
    protected function _construct()
    {
        $this->_init('paymentgateway/creditCardVaultToken');
    }

    /**
     * Returns usable tokens for a customer.
     *
     * @param string $customerId
     *
     * @return $this
     *
     * @throws Exception
     *
     * @since 1.2.0
     */
    public function getTokensForCustomer($customerId)
    {
        $this->addFilter('customer_id', $customerId);
        $this->setOrder('last_used', 'DESC');
        $this->addFieldToFilter('expiration_date', [
            'from' => (new DateTime('first day of this month'))->modify('-1 month')->format(DateTime::W3C),
            'date' => true,
        ]);
        return $this;
    }

    /**
     * Returns specific token for customer by its entity id.
     *
     * @param string $tokenId
     * @param string $customerId
     *
     * @return $this
     *
     * @throws Exception
     *
     * @since 1.2.0
     */
    public function getTokenForCustomer($tokenId, $customerId)
    {
        $this->addFilter('customer_id', $customerId);
        $this->addFilter('id', $tokenId);
        $this->addFieldToFilter('expiration_date', [
            'from' => (new DateTime('first day of this month'))->modify('-1 month')->format(DateTime::W3C),
            'date' => true,
        ]);
        return $this;
    }
}
