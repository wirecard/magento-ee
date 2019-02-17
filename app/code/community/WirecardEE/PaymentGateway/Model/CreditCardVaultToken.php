<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

/**
 * Represents a credit card vault token from the database.
 *
 * @since 1.2.0
 */
class WirecardEE_PaymentGateway_Model_CreditCardVaultToken extends Mage_Core_Model_Abstract
{
    /**
     * @since 1.2.0
     */
    protected function _construct()
    {
        $this->_init('paymentgateway/creditCardVaultToken');
    }

    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

    /**
     * @param int $customerId
     *
     * @since 1.2.0
     */
    public function setCustomerId($customerId)
    {
        $this->setData('customer_id', $customerId);
    }

    public function getMaskedAccountNumber()
    {
        return $this->getData('masked_account_number');
    }

    /**
     * @param string $maskedAccountNumber
     *
     * @since 1.2.0
     */
    public function setMaskedAccountNumber($maskedAccountNumber)
    {
        $this->setData('masked_account_number', $maskedAccountNumber);
    }

    public function getToken()
    {
        return $this->getData('token');
    }

    public function getFirstName()
    {
        return $this->getFromAdditionalData('firstName');
    }

    public function getLastName()
    {
        return $this->getFromAdditionalData('lastName');
    }

    private function getFromAdditionalData($key)
    {
        $additionalData = $this->getAdditionalData();
        if (! is_array($additionalData) || ! isset($additionalData[$key])) {
            return null;
        }
        return $additionalData[$key];
    }

    /**
     * @param string $token
     *
     * @since 1.2.0
     */
    public function setToken($token)
    {
        $this->setData('token', $token);
    }

    public function getAdditionalData()
    {
        $data = $this->getData('additional_data');
        if (! $data) {
            return [];
        }
        return unserialize($this->getData('additional_data'));
    }

    /**
     * @param array $additionalData
     *
     * @since 1.2.0
     */
    public function setAdditionalData(array $additionalData)
    {
        $this->setData('additional_data', serialize($additionalData));
    }

    /**
     * @param DateTime $lastUsed
     *
     * @since 1.2.0
     */
    public function setLastUsed(\DateTime $lastUsed)
    {
        $this->setData('last_used', $lastUsed->format(\DateTime::W3C));
    }

    /**
     * @param Mage_Sales_Model_Order_Address $billingAddress
     *
     * @since 1.2.0
     */
    public function setBillingAddress(\Mage_Sales_Model_Order_Address $billingAddress)
    {
        $billingAddressString = $billingAddress->toString();
        $this->setData('billing_address', $billingAddressString);
        $this->setData('billing_address_hash', md5($billingAddressString));
    }

    /**
     * @param Mage_Sales_Model_Order_Address $shippingAddress
     *
     * @since 1.2.0
     */
    public function setShippingAddress(\Mage_Sales_Model_Order_Address $shippingAddress)
    {
        $shippingAddressString = $shippingAddress->toString();
        $this->setData('shipping_address', $shippingAddressString);
        $this->setData('shipping_address_hash', md5($shippingAddressString));
    }

    /**
     * @param int $year
     * @param int $month
     *
     * @since 1.2.0
     */
    public function setExpirationDate($year, $month)
    {
        $this->setData(
            'expiration_date',
            \DateTime::createFromFormat('Ym-d', $year . $month . '-1')->format(\DateTime::W3C)
        );
    }
}
