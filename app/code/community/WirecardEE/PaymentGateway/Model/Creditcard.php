<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\Transaction\Operation;

/**
 * @since 1.0.0
 */
class WirecardEE_PaymentGateway_Model_CreditCard extends WirecardEE_PaymentGateway_Model_Payment
{
    protected $_code = 'wirecardee_paymentgateway_creditcard';
    protected $_paymentMethod = CreditCardTransaction::NAME;

    /**
     * Return available transaction types for this payment.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => Operation::RESERVE,
                'label' => Mage::helper('catalog')->__('text_payment_action_reserve'),
            ],
            [
                'value' => Operation::PAY,
                'label' => Mage::helper('catalog')->__('text_payment_action_pay'),
            ],
        ];
    }

    /**
     * Keep in mind that this additionally depends on the credit card payment configuration.
     *
     * @return bool
     *
     * @since 1.2.0
     */
    public function showTokenSelection()
    {
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }

    /**
     * @return array|WirecardEE_PaymentGateway_Model_Resource_CreditCardVaultToken_Collection
     *
     * @since 1.2.0
     *
     * @throws Mage_Core_Exception
     */
    public function getTokensForCustomer()
    {
        /** @var \WirecardEE_PaymentGateway_Model_CreditCardVaultToken $mageVaultTokenModel */
        $mageVaultTokenModel = \Mage::getModel('paymentgateway/creditCardVaultToken');
        /** @var \WirecardEE_PaymentGateway_Model_Resource_CreditCardVaultToken_Collection $mageVaultTokenModelCollection */
        $mageVaultTokenModelCollection = $mageVaultTokenModel->getCollection();
        $mageVaultTokenModelCollection->getTokensForCustomer(Mage::getSingleton('customer/session')->getCustomerId());

        $allowAddressChange = Mage::getStoreConfig(
            'payment/wirecardee_paymentgateway_creditcard/vault_allow_address_changes'
        );
        if (! $allowAddressChange) {
            $quote               = Mage::getSingleton('checkout/session')->getQuote();
            $billingAddressHash  = $mageVaultTokenModel->createAddressHash($quote->getBillingAddress());
            $shippingAddressHash = $quote->getShippingAddress()
                ? $mageVaultTokenModel->createAddressHash($quote->getShippingAddress())
                : $billingAddressHash;
            $mageVaultTokenModelCollection->addFilter('billing_address_hash', $billingAddressHash);
            $mageVaultTokenModelCollection->addFilter('shipping_address_hash', $shippingAddressHash);
        }

        if ($mageVaultTokenModelCollection->count() === 0) {
            return [];
        }

        return $mageVaultTokenModelCollection;
    }
}
