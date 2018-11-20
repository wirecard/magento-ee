<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Data;

use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Address;

class UserMapper
{
    /** @var \Mage_Sales_Model_Order */
    protected $order;

    /**
     * @var string
     */
    protected $clientIp;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @param \Mage_Sales_Model_Order $order
     * @param                         $clientIp
     * @param                         $locale
     */
    public function __construct(\Mage_Sales_Model_Order $order, $clientIp, $locale)
    {
        $this->order    = $order;
        $this->clientIp = $clientIp;
        $this->locale   = $locale;
    }

    /**
     * @return string
     */
    public function getClientIp()
    {
        return $this->clientIp;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return AccountHolder
     */
    public function getWirecardBillingAccountHolder()
    {
        $billingAddress = $this->getOrder()->getBillingAddress();
        $billingAccountHolder = new AccountHolder();
        $billingAccountHolder->setFirstName($billingAddress->getFirstname());
        $billingAccountHolder->setLastName($billingAddress->getLastname());
        $billingAccountHolder->setEmail($billingAddress->getEmail());
        $billingAccountHolder->setDateOfBirth(new \DateTime($this->getOrder()->getCustomerDob()));
        $billingAccountHolder->setPhone($billingAddress->getTelephone());
        $billingAccountHolder->setAddress($this->getWirecardBillingAddress());
        $billingAccountHolder->setGender($this->getOrder()->getCustomerGender());

        return $billingAccountHolder;
    }

    /**
     * @return Address
     */
    public function getWirecardBillingAddress()
    {
        $address = $this->getOrder()->getBillingAddress();
        $billingAddress = new Address(
            $address->getCountryId(),
            $address->getCity(),
            !empty($address->getStreet()[0]) ? $address->getStreet()[0] : ''
        );
        $billingAddress->setPostalCode($address->getPostcode());
        $billingAddress->setStreet2($address->getStreet2());
        $billingAddress->setState($address->getRegion());

        return $billingAddress;
    }

    /**
     * @return AccountHolder
     */
    public function getWirecardShippingAccountHolder()
    {
        $shippingAccountHolder = new AccountHolder();
        $shippingAccountHolder->setFirstName($this->getOrder()->getShippingAddress()->getFirstname());
        $shippingAccountHolder->setLastName($this->getOrder()->getShippingAddress()->getLastname());
        $shippingAccountHolder->setAddress($this->getWirecardShippingAddress());

        return $shippingAccountHolder;
    }

    /**
     * @return Address
     */
    public function getWirecardShippingAddress()
    {
        $address = $this->getOrder()->getShippingAddress();
        $shippingAddress = new Address(
            $address->getCountryId(),
            $address->getCity(),
            !empty($address->getStreet()[0]) ? $address->getStreet()[0] : ''
        );
        $shippingAddress->setPostalCode($address->getPostcode());
        $shippingAddress->setStreet2($address->getStreet2());
        $shippingAddress->setState($address->getRegion());

        return $shippingAddress;
    }

    /**
     * @return \Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->order;
    }
}
