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
    /**
     * @var \Mage_Sales_Model_Order
     */
    protected $order;

    /**
     * @var string
     */
    protected $clientIp;

    /**
     * @var string
     */
    protected $userAgent;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @param \Mage_Sales_Model_Order $order
     * @param string $clientIp
     * @param string $userAgent
     * @param string $locale
     *
     * @since 1.0.0
     */
    public function __construct(\Mage_Sales_Model_Order $order, $clientIp, $userAgent, $locale)
    {
        $this->order     = $order;
        $this->clientIp  = $clientIp;
        $this->userAgent = $userAgent;
        $this->locale    = $locale;
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
    public function getUserAgent()
    {
        return $this->userAgent;
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
     * @throws \Exception
     *
     * @since 1.0.0
     */
    public function getBillingAccountHolder()
    {
        $address       = $this->getOrder()->getBillingAddress();
        $accountHolder = new AccountHolder();
        $accountHolder->setCrmId($this->getOrder()->getCustomerId());
        $accountHolder->setFirstName($address->getFirstname());
        $accountHolder->setLastName($address->getLastname());
        $accountHolder->setEmail($address->getEmail());
        $accountHolder->setCrmId($this->getOrder()->getCustomerId());
        if ($this->getOrder()->getCustomerDob()) {
            $accountHolder->setDateOfBirth(new \DateTime($this->getOrder()->getCustomerDob()));
        }
        $accountHolder->setPhone($address->getTelephone());
        $accountHolder->setAddress($this->getAddress($address));

        if (($gender = $this->getOrder()->getCustomerGender())) {
            $accountHolder->setGender($gender === '1' ? 'm' : 'f');
        }

        return $accountHolder;
    }

    /**
     * @return AccountHolder
     *
     * @since 1.0.0
     */
    public function getShippingAccountHolder()
    {
        $address = $this->getOrder()->getShippingAddress();

        if (! $address) {
            $address = $this->getOrder()->getBillingAddress();
        }

        $accountHolder = new AccountHolder();
        $accountHolder->setFirstName($address->getFirstname());
        $accountHolder->setLastName($address->getLastname());
        $accountHolder->setAddress($this->getAddress($address));

        return $accountHolder;
    }

    /**
     * @param \Mage_Sales_Model_Order_Address $address
     *
     * @return Address
     *
     * @since 1.0.0
     */
    private function getAddress(\Mage_Sales_Model_Order_Address $address)
    {
        $shippingAddress = new Address(
            $address->getCountryId(),
            $address->getCity(),
            $address->getStreet1()
        );
        $shippingAddress->setPostalCode($address->getPostcode());
        $shippingAddress->setStreet2($address->getStreet2());
        $shippingAddress->setStreet3($address->getStreet3());
        if ($address->getRegion()) {
            $shippingAddress->setState($address->getRegion());
        }

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
