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

    /**
     * @return string
     *
     * @since 1.2.0
     */
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

    /**
     * @return string
     *
     * @since 1.2.0
     */
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

    /**
     * @return string
     *
     * @since 1.2.0
     */
    public function getToken()
    {
        return $this->getData('token');
    }

    /**
     * @return string|null
     *
     * @since 1.2.0
     */
    public function getFirstName()
    {
        return $this->getFromAdditionalData('firstName');
    }

    /**
     * @return string|null
     *
     * @since 1.2.0
     */
    public function getLastName()
    {
        return $this->getFromAdditionalData('lastName');
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     *
     * @since 1.2.0
     */
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

    /**
     * @return array
     *
     * @since 1.2.0
     */
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
     *
     * @throws Mage_Core_Exception
     */
    public function setBillingAddress(\Mage_Sales_Model_Order_Address $billingAddress)
    {
        $address = $this->createAddress($billingAddress);
        $this->setData('billing_address', serialize($address));
        $this->setData('billing_address_hash', $this->createAddressHash($billingAddress));
    }

    /**
     * Returns the serialized billing address.
     *
     * @return string
     *
     * @since 1.2.0
     */
    public function getSerializedBillingAddress()
    {
        return $this->getData('billing_address');
    }

    /**
     * @return string
     *
     * @since 1.2.0
     */
    public function getBillingAddressHash()
    {
        return $this->getData('billing_address_hash');
    }

    /**
     * @param Mage_Sales_Model_Order_Address $shippingAddress
     *
     * @since 1.2.0
     *
     * @throws Mage_Core_Exception
     */
    public function setShippingAddress(\Mage_Sales_Model_Order_Address $shippingAddress)
    {
        $address = $this->createAddress($shippingAddress);
        $this->setData('shipping_address', serialize($address));
        $this->setData('shipping_address_hash', $this->createAddressHash($shippingAddress));
    }

    /**
     * Returns the serialized shipping address.
     *
     * @return string
     *
     * @since 1.2.0
     */
    public function getSerializedShippingAddress()
    {
        return $this->getData('shipping_address');
    }

    /**
     * @return string
     *
     * @since 1.2.0
     */
    public function getShippingAddressHash()
    {
        return $this->getData('shipping_address_hash');
    }

    /**
     * @param \Mage_Sales_Model_Order_Address|\Mage_Sales_Model_Quote_Address $address
     *
     * @return array
     *
     * @throws Mage_Core_Exception
     *
     * @since 1.2.0
     */
    public function createAddress($address)
    {
        if (! ($address instanceof \Mage_Sales_Model_Order_Address)
            && ! ($address instanceof \Mage_Sales_Model_Quote_Address)) {
            \Mage::throwException('Invalid address');
        }

        return [
            'country' => $address->getCountryId(),
            'city'    => $address->getCity(),
            'street'  => $address->getStreet(),
            'zip'     => $address->getPostcode(),
            'region'  => $address->getRegionCode(),
        ];
    }

    /**
     * @param \Mage_Sales_Model_Order_Address|Mage_Sales_Model_Quote_Address $address
     *
     * @return string
     *
     * @throws Mage_Core_Exception
     *
     * @since 1.2.0
     */
    public function createAddressHash($address)
    {
        return md5(serialize($this->createAddress($address)));
    }

    /**
     * @param int $year
     * @param int $month
     *
     * @since 1.2.0
     */
    public function setExpirationDate($year, $month)
    {
        $date = \DateTime::createFromFormat('Ym-d', $year . $month . '-1');

        if ($date instanceof \DateTime) {
            $this->setData('expiration_date', $date->format(\DateTime::W3C));
        }
    }

    /**
     * @return \DateTime
     *
     * @since 1.2.0
     */
    public function getExpirationDate()
    {
        return $this->getData('expiration_date');
    }
}
