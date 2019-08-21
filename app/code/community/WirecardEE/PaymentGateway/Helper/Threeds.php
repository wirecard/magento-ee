<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

/**
 * ThreeDS Payment helper object, can be accessed with Mage::helper('paymentgateway/threeds')
 *
 * @since 1.2.4
 */
class WirecardEE_PaymentGateway_Helper_Threeds extends Mage_Payment_Helper_Data
{

    /**
     * Get last login timestamp
     * Depends on shop configuration
     * Configuration->Advanced->System => set Log => Enable Log to "Yes".
     *
     * @return int
     * @since 1.2.4
     */
    public function getCustomerLastLogin()
    {
        $customer = $this->getCustomerSession()->getCustomer();
        /** @var Mage_Log_Model_Customer $customerLog */
        $customerLog = Mage::getModel('log/customer');
        $customerLog->loadByCustomer($customer->getId());

        return $customerLog->getLoginAtTimestamp();
    }

    /**
     * get customer session
     *
     * @return Mage_Customer_Model_Session
     * @since 1.2.4
     */
    public function getCustomerSession()
    {
        /** @var Mage_Customer_Model_Session $customerSession */
        $customerSession = Mage::getSingleton('customer/session');

        return $customerSession;
    }

    /**
     * Get configured challenge indicator
     *
     * @return mixed|string
     * @throws Exception
     * @since 1.2.4
     */
    public function getChallengeIndicator()
    {
        return Mage::getStoreConfig('payment/wirecardee_paymentgateway_creditcard/challenge_indicator');
    }

    /**
     * Check if a token is a new one
     *
     * @param $tokenId
     *
     * @return bool
     * @throws Exception
     * @since 1.2.4
     */
    public function isNewToken($tokenId)
    {
        if ($tokenId === null) {
            return false;
        }
        /** @var WirecardEE_PaymentGateway_Model_Resource_CreditCardVaultToken_Collection $vaultTokenModelColl */
        $vaultTokenModelColl = Mage::getModel('paymentgateway/creditCardVaultToken')->getCollection();

        $vaultTokenModelColl->getTokenForCustomer($tokenId, $this->getCustomerSession()->getCustomerId());

        return $vaultTokenModelColl->getFirstItem()->isEmpty();
    }

    /**
     * creation date of used card token
     *
     * @param $tokenId
     *
     * @return mixed|string
     * @throws Exception
     * @since 1.2.4
     */
    public function getCardCreationDate($tokenId)
    {
        if ($tokenId === null) {
            return new DateTime();
        }

        if (!$this->getCustomerSession()->isLoggedIn()) {
            return new DateTime();
        }

        /** @var WirecardEE_PaymentGateway_Model_Resource_CreditCardVaultToken_Collection $vaultTokenModelColl */
        $vaultTokenModelColl = Mage::getModel('paymentgateway/creditCardVaultToken')->getCollection();

        $vaultTokenModelColl->getTokenForCustomer($tokenId, $this->getCustomerSession()->getCustomerId());
        /** @var WirecardEE_PaymentGateway_Model_CreditCardVaultToken $vaultToken */
        $vaultToken = $vaultTokenModelColl->getFirstItem();
        if ($vaultToken->isEmpty()) {
            return new DateTime();
        }

        return $vaultToken->getCreatedAt();
    }

    /**
     * return datetime of first address usage
     *
     * @param $addressId
     *
     * @return DateTime|null
     * @throws Exception
     * @since 1.2.4
     */
    public function getAddressFirstUsed($addressId)
    {
        /** @var Mage_Sales_Model_Resource_Order_Collection $orderCollection */
        $orderCollection = Mage::getModel('sales/order')
            ->getCollection();

        $orderCollection->join(['oa' => 'sales/order_address'], 'oa.entity_id = main_table.shipping_address_id');

        $orderCollection->addFieldToFilter('oa.customer_address_id', $addressId);

        $orderCollection->addAttributeToSelect('created_at')
            ->addAttributeToSort('created_at')
            ->setPageSize(1)
            ->setCurPage(1);

        $first = $orderCollection->getFirstItem();
        if ($first->isEmpty()) {
            return null;
        }

        return new DateTime($first->getCreatedAt());
    }

    /**
     * retreive successful number of orders within the last 6 months
     *
     * @param $customerId
     *
     * @return int
     * @throws Exception
     * @since 1.2.4
     */
    public function getSuccessfulOrdersLastSixMonths($customerId)
    {
        /** @var Mage_Sales_Model_Resource_Order_Collection $orderCollection */
        $orderCollection = Mage::getModel('sales/order')
            ->getCollection();

        $orderCollection->addFieldToFilter('customer_id', $customerId);

        $now       = new DateTime();
        $dateStart = $now->sub(new DateInterval('P6M'))->format('Y-m-d H:i:s');
        $orderCollection->addFieldToFilter('created_at', ['from' => $dateStart]);

        $successfulStates = [
            Mage_Sales_Model_Order::STATE_COMPLETE,
            Mage_Sales_Model_Order::STATE_PROCESSING,
            Mage_Sales_Model_Order::STATE_CLOSED,
            Mage_Sales_Model_Order::STATE_CANCELED
        ];
        $orderCollection->addFieldToFilter('state', ['in' => $successfulStates]);

        return $orderCollection->count();
    }

    /**
     * check if order has at least one reordered item
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return bool
     */
    public function hasReorderedItems(Mage_Sales_Model_Order $order)
    {
        /** @var Mage_Sales_Model_Order_Item[] */
        $items = $order->getAllVisibleItems();

        $productIds = array_map(function ($i) {
            /** @var Mage_Sales_Model_Order_Item $i */
            return $i->getProductId();
        }, $items);

        /** @var Mage_Sales_Model_Resource_Order_Collection $orderCollection */
        $orderCollection = Mage::getModel('sales/order')
            ->getCollection();

        $orderCollection->join(['oi' => 'sales/order_item'], 'oi.order_id = main_table.entity_id');
        $orderCollection->addFieldToFilter('customer_id', $this->getCustomerSession()->getCustomerId());
        $orderCollection->addFieldToFilter('oi.product_id', ['in' => $productIds]);

        return $orderCollection->count() > 0;
    }

    /**
     * check if order has at least one virtual item (electronic delivery)
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return bool
     */
    public function hasVirtualItems(Mage_Sales_Model_Order $order)
    {
        /** @var Mage_Sales_Model_Order_Item[] */
        $items = $order->getAllVisibleItems();

        foreach ($items as $item) {
            /** @var Mage_Sales_Model_Order_Item $item */
            if ($item->getIsVirtual()) {
                return true;
            }
        }

        return false;
    }
}
