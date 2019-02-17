<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Mapper;

use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\Item;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * @since 1.2.0
 */
class VaultToken
{
    /**
     * @var array
     */
    protected $token;

    public function __construct(array $token)
    {
        $this->token = $token;
    }

    public function getId()
    {
        return $this->get('id');
    }

    public function getCustomerId()
    {
        return $this->get('customer_id');
    }

    public function getToken()
    {
        return $this->get('token');
    }

    public function getMaskedAccountNumber()
    {
        return $this->get('masked_account_number');
    }

    public function getLastUsed()
    {
        $lastUsed = $this->get('last_used');
        if (! $lastUsed) {
            return null;
        }
        return new \DateTime($lastUsed);
    }

    public function getBillingAddress()
    {
        $billingAddress = $this->get('billing_address');
        if (! $billingAddress) {
            return [];
        }
        return unserialize($billingAddress);
    }

    public function getBillingAddressHash()
    {
        return $this->getBillingAddress() ? md5($this->get('billing_address')) : '';
    }

    public function getShippingAddress()
    {
        $shippingAddress = $this->get('shipping_address');
        if (! $shippingAddress) {
            return [];
        }
        return unserialize($shippingAddress);
    }

    public function getShippingAddressHash()
    {
        return $this->getShippingAddress() ? md5($this->get('shipping_address')) : '';
    }

    public function getFromAdditionalData($key)
    {
        $additionalData = $this->get('additional_data');
        if (! $additionalData) {
            return null;
        }
        $additionalData = unserialize($additionalData);
        if (! isset($additionalData[$key])) {
            return null;
        }
        return $additionalData[$key];
    }

    public function getExpirationDate()
    {
        if (! ($month = $this->getFromAdditionalData('expirationMonth'))
            || (! $year = $this->getFromAdditionalData('expirationYear'))) {
            return null;
        }
        return \DateTime::createFromFormat('Ym', $year . $month);
    }

    private function get($key)
    {
        if (! isset($this->token[$key])) {
            return null;
        }
        return $this->token[$key];
    }
}
