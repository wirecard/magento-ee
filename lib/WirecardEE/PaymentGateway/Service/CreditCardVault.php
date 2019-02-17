<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Service;

use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Entity\Status;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Actions\SuccessAction;
use WirecardEE\PaymentGateway\Mapper\VaultToken;

/**
 * @since 1.2.0
 */
class CreditCardVault
{
    protected $resource;

    public function __construct(\Mage_Core_Model_Resource $resource)
    {
        $this->resource = $resource;
    }

    public function saveToken(
        \Mage_Sales_Model_Order $order,
        $token,
        $maskedAccountNumber,
        $additionalData = []
    ) {
        $billingAddress  = $this->getBillingAddressFromOrderAsString($order);
        $shippingAddress = $this->getShippingAddressFromOrderAsString($order);
        $expirationDate  = null;
        if (! empty($additionalData['expirationYear']) && ! empty($additionalData['expirationMonth'])) {
            $expirationDate = \DateTime::createFromFormat(
                'Ym',
                $additionalData['expirationYear'] . $additionalData['expirationMonth']
            );
        }

        $this->getWriteConnection()
             ->insertMultiple(
                 $this->getTableName('wirecard_elastic_engine_credit_card_vault'),
                 [
                     'customer_id'           => $order->getCustomerId(),
                     'token'                 => $token,
                     'masked_account_number' => $maskedAccountNumber,
                     'billing_address'       => $billingAddress,
                     'billing_address_hash'  => md5($billingAddress),
                     'shipping_address'      => $shippingAddress,
                     'shipping_address_hash' => md5($shippingAddress),
                     'additional_data'       => serialize($additionalData),
                     'last_used'             => (new \DateTime())->format(\DateTime::W3C),
                     'expiration_date'       => $expirationDate->format(\DateTime::W3C),
                 ]
             );
    }

    /**
     * @param $customerId
     *
     * @return VaultToken[]
     *
     * @since 1.2.0
     */
    public function getTokensForCustomer($customerId)
    {
        $tokens = $this->getReadConnection()
                       ->select()
                       ->from($this->getTableName('wirecard_elastic_engine_credit_card_vault'))
                       ->where('customer_id = ?', $customerId)
                       ->order('last_used DESC')
                       ->query()
                       ->fetchAll();

        if (! $tokens || ! is_array($tokens)) {
            return [];
        }

        $vaultTokens = [];
        foreach ($tokens as $token) {
            $vaultTokens[] = new VaultToken($token);
        }

        return $vaultTokens;
    }

    /**
     * @param \Mage_Sales_Model_Order $order
     *
     * @return string
     *
     * @since 1.2.0
     */
    private function getBillingAddressFromOrderAsString(\Mage_Sales_Model_Order $order)
    {
        $address = $order->getBillingAddress();
        if (! $address) {
            return '';
        }
        return serialize($address->toArray());
    }

    /**
     * @param \Mage_Sales_Model_Order $order
     *
     * @return string
     *
     * @since 1.2.0
     */
    private function getShippingAddressFromOrderAsString(\Mage_Sales_Model_Order $order)
    {
        $address = $order->getShippingAddress();
        if (! $address) {
            $address = $order->getBillingAddress();
        }
        return serialize($address->toArray());
    }

    /**
     * @return \Varien_Db_Adapter_Interface
     *
     * @since 1.2.0
     */
    protected function getWriteConnection()
    {
        return $this->resource->getConnection('core_write');
    }

    /**
     * @return \Varien_Db_Adapter_Interface
     *
     * @since 1.2.0
     */
    protected function getReadConnection()
    {
        return $this->resource->getConnection('core_read');
    }

    /**
     * @param $name
     *
     * @return string
     *
     * @since 1.2.0
     */
    protected function getTableName($name)
    {
        return $this->resource->getTableName($name);
    }
}
