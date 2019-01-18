<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Payments;

use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\PoiPiaTransaction;
use Wirecard\PaymentSdk\TransactionService;

use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\PaymentConfig;
use WirecardEE\PaymentGateway\Payments\Contracts\AdditionalPaymentInformationInterface;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessPaymentInterface;
use WirecardEE\PaymentGateway\Service\Logger;
use WirecardEE\PaymentGateway\Service\TransactionManager;

class PiaPayment extends Payment implements ProcessPaymentInterface, AdditionalPaymentInformationInterface
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
     * @return null|Action
     *
     * @throws InsufficientDataException
     *
     * @since 1.0.0
     */
    public function processPayment(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Redirect $redirect
    ) {
        if (! $this->getPaymentConfig()->hasFraudPrevention()) {
            $accountHolder = new AccountHolder();
            $accountHolder->setLastName($orderSummary->getUserMapper()->getLastName());
            $accountHolder->setFirstName($orderSummary->getUserMapper()->getFirstName());
            $this->getTransaction()->setAccountHolder($accountHolder);
        }
    }

    public function assignAdditionalPaymentInformation(\Mage_Sales_Model_Order $order)
    {
        $logger = new Logger();
        $transactionManager = new TransactionManager($logger);

        $response = $transactionManager->findInitialResponse($order);

        if (! $response) {
            return null;
        }

        $bankData = [ \Mage::helper('paymentgateway')->__('iban') . ' ' . $response['merchant-bank-account.0.iban']];

        if ($response['merchant-bank-account.0.bic']) {
            $bankData[] =  \Mage::helper('paymentgateway')->__('bic') . ' ' . $response['merchant-bank-account.0.bic'];
        }

        $bankData[] = \Mage::helper('paymentgateway')->__('ptrid') . ': ' . $response['provider-transaction-reference-id'];

        if ($response['merchant-bank-account.0.bank-name']) {
            $bankData[] = $response['merchant-bank-account.0.bank-name'];
        }

        if ($response['merchant-bank-account.0.branch-address']) {
            $bankData[] = $response['merchant-bank-account.0.branch-address'];
            $bankData[] = $response['merchant-bank-account.0.branch-city'] . ' ' . $response['merchant-bank-account.0.branch-state'];
        }
        $order->addStatusHistoryComment(implode('<br>', $bankData));
        $order->save();

        return [
            'bankData' => [
                'bankName'  => $response['merchant-bank-account.0.bank-name'],
                'bic'       => $response['merchant-bank-account.0.bic'],
                'iban'      => $response['merchant-bank-account.0.iban'],
                'address'   => $response['merchant-bank-account.0.branch-address'],
                'city'      => $response['merchant-bank-account.0.branch-city'],
                'state'     => $response['merchant-bank-account.0.branch-state'],
                'reference' => $response['provider-transaction-reference-id'],
            ]
        ];
    }
}
