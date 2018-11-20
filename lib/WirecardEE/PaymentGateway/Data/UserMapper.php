<?php

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

    public function __construct(\Mage_Sales_Model_Order $order, $clientIp, $locale)
    {
        $this->order    = $order;
        $this->clientIp = $clientIp;
        $this->locale   = $locale;
    }

    public function getClientIp()
    {
        return $this->clientIp;
    }

    public function getLocale()
    {
        return $this->locale;
    }

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

    public function getWirecardShippingAccountHolder()
    {
        $shippingAccountHolder = new AccountHolder();
        $shippingAccountHolder->setFirstName($this->getOrder()->getShippingAddress()->getFirstname());
        $shippingAccountHolder->setLastName($this->getOrder()->getShippingAddress()->getLastname());
        $shippingAccountHolder->setAddress($this->getWirecardShippingAddress());

        return $shippingAccountHolder;
    }

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

    public function getOrder()
    {
        return $this->order;
    }
}
