<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Payments;

use Wirecard\PaymentSdk\Config\CreditCardConfig;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\Action;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Actions\ViewAction;
use WirecardEE\PaymentGateway\Data\CreditCardPaymentConfig;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Payments\Contracts\CustomFormTemplateInterface;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessPaymentInterface;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessReturnInterface;
use WirecardEE\PaymentGateway\Service\Logger;
use WirecardEE\PaymentGateway\Service\SessionManager;

class CreditCardPayment extends Payment implements
    ProcessPaymentInterface,
    ProcessReturnInterface,
    CustomFormTemplateInterface
{
    const NAME = CreditCardTransaction::NAME;

    /**
     * @var CreditCardTransaction
     */
    private $transactionInstance;

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return CreditCardTransaction
     *
     * @since 1.0.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new CreditCardTransaction();
        }
        return $this->transactionInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionConfig($selectedCurrency)
    {
        $transactionConfig = parent::getTransactionConfig($selectedCurrency);
        $paymentConfig     = $this->getPaymentConfig();
        $creditCardConfig  = new CreditCardConfig();

        if ($paymentConfig->getTransactionMAID() && strtolower($paymentConfig->getTransactionMAID()) !== '') {
            $creditCardConfig->setSSLCredentials(
                $paymentConfig->getTransactionMAID(),
                $paymentConfig->getTransactionSecret()
            );
        }

        if ($paymentConfig->getThreeDMAID() && strtolower($paymentConfig->getThreeDMAID()) !== '') {
            $creditCardConfig->setThreeDCredentials(
                $paymentConfig->getThreeDMAID(),
                $paymentConfig->getThreeDSecret()
            );
        }

        if (strtolower($paymentConfig->getSslMaxLimit()) !== '') {
            $creditCardConfig->addSslMaxLimit(
                $this->getLimit(
                    $selectedCurrency,
                    $paymentConfig->getSslMaxLimit(),
                    $paymentConfig->getSslMaxLimitCurrency()
                )
            );
        }
        if (strtolower($paymentConfig->getThreeDMinLimit()) !== '') {
            $creditCardConfig->addThreeDMinLimit(
                $this->getLimit(
                    $selectedCurrency,
                    $paymentConfig->getThreeDMinLimit(),
                    $paymentConfig->getThreeDMinLimitCurrency()
                )
            );
        }

        $transactionConfig->add($creditCardConfig);
        $this->getTransaction()->setConfig($creditCardConfig);

        return $transactionConfig;
    }

    /**
     * @param string       $selectedCurrency
     * @param float|string $limitValue
     * @param string       $limitCurrency
     *
     * @return Amount
     * @throws \Mage_Core_Model_Store_Exception
     *
     * @since 1.0.0
     */
    private function getLimit($selectedCurrency, $limitValue, $limitCurrency)
    {
        $factor = $this->getCurrencyConversionFactor(strtoupper($selectedCurrency), strtoupper($limitCurrency));
        return new Amount($limitValue * $factor, $selectedCurrency);
    }

    /**
     * @param $selectedCurrency
     * @param $limitCurrency
     *
     * @return float|int
     * @throws \Mage_Core_Model_Store_Exception
     *
     * @since 1.0.0
     */
    private function getCurrencyConversionFactor($selectedCurrency, $limitCurrency)
    {
        if ($selectedCurrency === $limitCurrency) {
            return 1.0;
        }

        try {
            \Mage::app()->getLocale()->currency($selectedCurrency);
            \Mage::app()->getLocale()->currency($limitCurrency);
        } catch (\Exception $e) {
            (new Logger())->error("Failed to load currency for converting currency: " . $e->getMessage());
            return 1.0;
        }

        /** @var \Mage_Directory_Helper_Data $directoryHelper */
        $directoryHelper = \Mage::helper('directory');
        $selectedFactor  = $directoryHelper->currencyConvert(
            1,
            \Mage::app()->getStore()->getBaseCurrencyCode(),
            $selectedCurrency
        );
        $limitFactor     = $directoryHelper->currencyConvert(
            1,
            \Mage::app()->getStore()->getBaseCurrencyCode(),
            $limitCurrency
        );
        if (! $selectedFactor) {
            $selectedFactor = 1.0;
        }
        if (! $limitFactor) {
            $limitFactor = 1.0;
        }
        return $selectedFactor / $limitFactor;
    }

    /**
     * @return CreditCardPaymentConfig
     *
     * @since 1.0.0
     */
    public function getPaymentConfig()
    {
        $paymentConfig = new CreditCardPaymentConfig(
            $this->getPluginConfig('api_url'),
            $this->getPluginConfig('api_user'),
            $this->getPluginConfig('api_password')
        );

        $paymentConfig->setTransactionMAID($this->getPluginConfig('api_maid'));
        $paymentConfig->setTransactionSecret($this->getPluginConfig('api_secret'));
        $paymentConfig->setTransactionOperation($this->getPluginConfig('transaction_type'));

        $paymentConfig->setThreeDMAID($this->getPluginConfig('threeds_maid'));
        $paymentConfig->setThreeDSecret($this->getPluginConfig('threeds_secret'));
        $paymentConfig->setSslMaxLimit($this->getPluginConfig('ssl_max_limit'));
        $paymentConfig->setSslMaxLimitCurrency($this->getPluginConfig('ssl_max_limit_currency'));
        $paymentConfig->setThreeDMinLimit($this->getPluginConfig('three_d_min_limit'));
        $paymentConfig->setThreeDMinLimitCurrency($this->getPluginConfig('three_d_min_limit_currency'));

        $paymentConfig->setVaultEnabled($this->getPluginConfig('vault_enabled'));
        $paymentConfig->setAllowAddressChanges($this->getPluginConfig('vault_allow_address_changes'));
        $paymentConfig->setThreeDUsageOnTokens($this->getPluginConfig('vault_use_three_d'));

        $paymentConfig->setFraudPrevention($this->getPluginConfig('fraud_prevention'));
        $paymentConfig->setOrderIdentification($this->getPluginConfig('order_identification'));

        return $paymentConfig;
    }

    /**
     * @param OrderSummary       $orderSummary
     * @param TransactionService $transactionService
     * @param Redirect           $redirect
     *
     * @return Action|null
     * @throws \Exception
     *
     * @since 1.0.0
     */
    public function processPayment(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Redirect $redirect
    ) {
        $transaction = $this->getTransaction();
        $transaction->setTermUrl($redirect);

        if ($this->getPaymentConfig()->isVaultEnabled()) {
            $paymentData = $orderSummary->getAdditionalPaymentData();
            $tokenId     = isset($paymentData['token']) ? $paymentData['token'] : null;

            /** @var \Mage_Core_Model_Session $session */
            $session        = \Mage::getSingleton("core/session", ["name" => "frontend"]);
            $sessionManager = new SessionManager($session);
            $sessionManager->storePaymentData(['saveToken' => ($tokenId === 'wirecardee--new-card-save')]);

            if ($tokenId && ! in_array($tokenId, ['wirecardee--new-card', 'wirecardee--new-card-save'])) {
                return $this->useToken($transaction, $tokenId, $orderSummary);
            }
        }

        $requestData = $transactionService->getCreditCardUiWithData(
            $transaction,
            $orderSummary->getPayment()->getTransactionType(),
            \Mage::app()->getLocale()->getLocaleCode()
        );

        $requestDataArray = json_decode($requestData, true);
        $this->getTransactionManager()->createInitialRequestTransaction($requestDataArray, $orderSummary->getOrder());

        return new ViewAction('paymentgateway/seamless', [
            'wirecardUrl'         => $orderSummary->getPayment()->getPaymentConfig()->getBaseUrl(),
            'wirecardRequestData' => $requestData,
            'url'                 => \Mage::getUrl('paymentgateway/gateway/return', ['method' => self::NAME]),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function processReturn(
        TransactionService $transactionService,
        \Mage_Core_Controller_Request_Http $request,
        \Mage_Sales_Model_Order $order
    ) {
        if ($this->getPaymentConfig()->isVaultEnabled()) {
            $this->saveToken($order, $request->getParams(), $transactionService);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFormTemplateName()
    {
        return 'WirecardEE/form/credit_card.phtml';
    }

    /**
     * Responsible for saving tokens for future checkouts
     *
     * @param \Mage_Sales_Model_Order $order
     * @param array                   $params
     * @param TransactionService      $transactionService
     *
     * @throws \Mage_Core_Exception
     *
     * @since 1.2.0
     */
    private function saveToken(\Mage_Sales_Model_Order $order, array $params, TransactionService $transactionService)
    {
        /** @var \Mage_Core_Model_Session $session */
        $session        = \Mage::getSingleton("core/session", ["name" => "frontend"]);
        $sessionManager = new SessionManager($session);
        $paymentData    = $sessionManager->getPaymentData();

        if (! isset($paymentData['saveToken'])
            || ! $paymentData['saveToken']
            || ! isset($params['token_id'])
            || ! isset($params['transaction_id'])
            || ! isset($params['masked_account_number'])
        ) {
            return;
        }

        $token       = $params['token_id'];
        $customerId  = $order->getCustomerId();
        $transaction = $transactionService->getTransactionByTransactionId(
            $params['transaction_id'],
            CreditCardTransaction::NAME
        );
        $cardInfo    = isset($transaction['payment']['card']) ? $transaction['payment']['card'] : [];

        // Expired credit cards are considered invalid (and will not be shown to the customer) - hence not having
        // information about the expiration date means we could simply skip saving this card.
        if (empty($cardInfo['expiration-year']) || empty($cardInfo['expiration-month'])) {
            return;
        }

        /** @var \WirecardEE_PaymentGateway_Model_CreditCardVaultToken $mageVaultTokenModel */
        $mageVaultTokenModel = \Mage::getModel('paymentgateway/creditCardVaultToken');
        /** @var \Mage_Core_Model_Resource_Db_Collection_Abstract $mageVaultTokenModelCollection */
        $mageVaultTokenModelCollection = $mageVaultTokenModel->getCollection()
                                                             ->findTokenForCustomer($token, $customerId);
        $mageVaultTokenModelCollection->addFilter(
            'billing_address_hash',
            $mageVaultTokenModel->createAddressHash($order->getBillingAddress())
        );
        if ($order->getShippingAddress()) {
            $mageVaultTokenModelCollection->addFilter(
                'shipping_address_hash',
                $mageVaultTokenModel->createAddressHash($order->getShippingAddress())
            );
        }

        if (! $mageVaultTokenModelCollection->getFirstItem()->isEmpty()) {
            $mageVaultTokenModel->load($mageVaultTokenModelCollection->getFirstItem()->getId());
        }

        $mageVaultTokenModel->setExpirationDate($cardInfo['expiration-year'], $cardInfo['expiration-month']);
        $mageVaultTokenModel->setBillingAddress($order->getBillingAddress());
        $mageVaultTokenModel->setShippingAddress(
            $order->getShippingAddress() ? $order->getShippingAddress() : $order->getBillingAddress()
        );
        $mageVaultTokenModel->setMaskedAccountNumber($params['masked_account_number']);
        $mageVaultTokenModel->setToken($token);
        $mageVaultTokenModel->setCustomerId($customerId);
        $mageVaultTokenModel->setAdditionalData([
            'firstName' => $order->getCustomerFirstname(),
            'lastName'  => $order->getCustomerLastname(),
            'cardType'  => isset($cardInfo['card-type']) ? $cardInfo['card-type'] : '',
        ]);
        $mageVaultTokenModel->setLastUsed(new \DateTime());
        $mageVaultTokenModel->save();
    }

    /**
     * Responsible for using a token for one-click checkout
     *
     * @param CreditCardTransaction $transaction
     * @param                       $tokenId
     * @param OrderSummary          $orderSummary
     *
     * @return ErrorAction|null
     *
     * @throws \Mage_Core_Exception
     *
     * @since 1.2.0
     */
    private function useToken(CreditCardTransaction $transaction, $tokenId, OrderSummary $orderSummary)
    {
        /** @var \WirecardEE_PaymentGateway_Model_CreditCardVaultToken $mageVaultTokenModel */
        $mageVaultTokenModel = \Mage::getModel('paymentgateway/creditCardVaultToken');
        $mageVaultTokenModelCollection = $mageVaultTokenModel->getCollection()->getTokenForCustomer(
            $tokenId,
            $orderSummary->getOrder()->getCustomerId()
        );

        if (! $this->getPaymentConfig()->allowAddressChanges()) {
            $billingAddressHash  = $mageVaultTokenModel->createAddressHash(
                $orderSummary->getOrder()->getBillingAddress()
            );
            $shippingAddressHash = $orderSummary->getOrder()->getShippingAddress()
                ? $mageVaultTokenModel->createAddressHash($orderSummary->getOrder()->getShippingAddress())
                : $billingAddressHash;
            $mageVaultTokenModelCollection->addFilter('billing_address_hash', $billingAddressHash);
            $mageVaultTokenModelCollection->addFilter('shipping_address_hash', $shippingAddressHash);
        }

        if ($mageVaultTokenModelCollection->count() === 0) {
            return new ErrorAction(ErrorAction::PROCESSING_FAILED, 'no valid credit card for token found');
        }

        $mageVaultTokenModel->load($mageVaultTokenModelCollection->getFirstItem()->getId());
        $mageVaultTokenModel->setLastUsed(new \DateTime());
        $mageVaultTokenModel->save();

        if (! $this->getPaymentConfig()->useThreeDOnTokens()) {
            $transaction->setThreeD(false);
        }

        $transaction->setTokenId($mageVaultTokenModel->getToken());

        return null;
    }
}
