<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Payments;

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\PoiPiaTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\PaymentConfig;
use WirecardEE\PaymentGateway\Mapper\ResponseMapper;
use WirecardEE\PaymentGateway\Payments\Contracts\AdditionalCheckoutSuccessTemplateInterface;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessPaymentInterface;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessReturnInterface;

class PiaPayment extends Payment implements
    ProcessPaymentInterface,
    ProcessReturnInterface,
    AdditionalCheckoutSuccessTemplateInterface
{
    const NAME = 'pia';

    /**
     * @var PoiPiaTransaction
     */
    private $transactionInstance;

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return PoiPiaTransaction
     *
     * @since 1.1.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new PoiPiaTransaction();
        }
        return $this->transactionInstance;
    }

    /**
     * @param $selectedCurrency
     *
     * @return Config
     *
     * @since 1.1.0
     */
    public function getTransactionConfig($selectedCurrency)
    {
        $config = parent::getTransactionConfig($selectedCurrency);
        $config->add(new PaymentMethodConfig(
            PoiPiaTransaction::NAME,
            $this->getPaymentConfig()->getTransactionMAID(),
            $this->getPaymentConfig()->getTransactionSecret()
        ));

        return $config;
    }

    /**
     * @return  PaymentConfig
     *
     * @since 1.1.0
     */
    public function getPaymentConfig()
    {
        $paymentConfig = new PaymentConfig(
            $this->getPluginConfig('api_url'),
            $this->getPluginConfig('api_user'),
            $this->getPluginConfig('api_password')
        );

        $paymentConfig->setTransactionMAID($this->getPluginConfig('api_maid'));
        $paymentConfig->setTransactionSecret($this->getPluginConfig('api_secret'));
        $paymentConfig->setTransactionOperation(Operation::RESERVE);
        $paymentConfig->setFraudPrevention($this->getPluginConfig('fraud_prevention'));

        return $paymentConfig;
    }

    /**
     * @param OrderSummary       $orderSummary
     * @param TransactionService $transactionService
     * @param Redirect           $redirect
     *
     * @return null
     *
     * @since 1.0.0
     */
    public function processPayment(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Redirect $redirect
    ) {
        if (! $this->getPaymentConfig()->hasFraudPrevention()) {
            $billingAddress = $orderSummary->getOrder()->getBillingAddress();
            $accountHolder  = new AccountHolder();
            $accountHolder->setLastName($billingAddress->getFirstname());
            $accountHolder->setFirstName($billingAddress->getLastname());
            $this->getTransaction()->setAccountHolder($accountHolder);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function processReturn(TransactionService $transactionService, \Mage_Core_Controller_Request_Http $request)
    {
        $response = $transactionService->handleResponse($request->getParams());

        if ($response instanceof SuccessResponse) {
            /** @var \Mage_Checkout_Model_Session|\Mage_Core_Model_Abstract $checkoutSession */
            $checkoutSession = \Mage::getSingleton('checkout/session');
            $order           = $checkoutSession->getLastRealOrder();
            $responseMapper  = new ResponseMapper($response);
            $bankData        = $responseMapper->getBankData();

            $order->getPayment()->setAdditionalInformation($bankData);
            $order->addStatusHistoryComment(implode('<br>', array_filter(array_map(function ($key, $value) {
                if (! $value) {
                    return null;
                }
                return $this->getHelper()->__($key) . ': ' . $value;
            }, array_keys($bankData), array_values($bankData)))));
            $order->save();
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getCheckoutSuccessTemplate()
    {
        return 'WirecardEE/checkout/bank_data.phtml';
    }
}
