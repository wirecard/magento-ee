<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Mapper;

use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Address;

/**
 * @since 1.0.0
 */
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
     * @param string                  $clientIp
     * @param string                  $locale
     *
     * @since 1.0.0
     */
    public function __construct(\Mage_Sales_Model_Order $order, $clientIp, $locale)
    {
        $this->order    = $order;
        $this->clientIp = $clientIp;
        $this->locale   = $locale;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getClientIp()
    {
        return $this->clientIp;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return AccountHolder
     *
     * @since 1.0.0
     */
    public function getWirecardBillingAccountHolder()
    {
        $billingAddress       = $this->getOrder()->getBillingAddress();
        $billingAccountHolder = new AccountHolder();
        $billingAccountHolder->setFirstName($billingAddress->getFirstname());
        $billingAccountHolder->setLastName($billingAddress->getLastname());
        $billingAccountHolder->setEmail($billingAddress->getEmail());
        $billingAccountHolder->setDateOfBirth(new \DateTime($this->getOrder()->getCustomerDob()));
        $billingAccountHolder->setPhone($billingAddress->getTelephone());
        $billingAccountHolder->setAddress($this->getWirecardBillingAddress());

        if ($gender = $this->getOrder()->getCustomerGender()) {
            $billingAccountHolder->setGender(
                $gender === '1' ? 'm' : 'f'
            );
        }

        return $billingAccountHolder;
    }

    /**
     * @return Address
     *
     * @since 1.0.0
     */
    public function getWirecardBillingAddress()
    {
        $address        = $this->getOrder()->getBillingAddress();
        $billingAddress = new Address(
            $address->getCountryId(),
            $address->getCity(),
            ! empty($address->getStreet()[0]) ? $address->getStreet()[0] : ''
        );
        $billingAddress->setPostalCode($address->getPostcode());
        $billingAddress->setStreet2($address->getStreet2());
        $billingAddress->setState($address->getRegion());

        return $billingAddress;
    }

    /**
     * @return AccountHolder
     *
     * @since 1.0.0
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
     *
     * @since 1.0.0
     */
    public function getWirecardShippingAddress()
    {
        $address         = $this->getOrder()->getShippingAddress();
        $shippingAddress = new Address(
            $address->getCountryId(),
            $address->getCity(),
            ! empty($address->getStreet()[0]) ? $address->getStreet()[0] : ''
        );
        $shippingAddress->setPostalCode($address->getPostcode());
        $shippingAddress->setStreet2($address->getStreet2());
        $shippingAddress->setState($address->getRegion());

        return $shippingAddress;
    }

    /**
     * @return \Mage_Sales_Model_Order
     *
     * @since 1.0.0
     */
    public function getOrder()
    {
        return $this->order;
    }
}
