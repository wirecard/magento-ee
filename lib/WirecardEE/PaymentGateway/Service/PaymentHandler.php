<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Service;

use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\Action;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Actions\RedirectAction;
use WirecardEE\PaymentGateway\Data\BasketMapper;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\UserMapper;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessPaymentInterface;

class PaymentHandler
{
    /** @var \Mage_Core_Model_Store */
    protected $store;

    public function __construct(\Mage_Core_Model_Store $store)
    {
        $this->store = $store;
    }

    /**
     * @param OrderSummary       $orderSummary
     * @param TransactionService $transactionService
     *
     * @param Redirect           $redirect
     * @param string             $notificationUrl
     *
     * @return Action
     */
    public function execute(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Redirect $redirect,
        $notificationUrl
    ) {
        $payment = $orderSummary->getPayment();

        $this->prepareTransaction($orderSummary, $redirect, $notificationUrl);

        $user = new UserMapper($orderSummary->getOrder(), '', '');
        var_dump($user->getWirecardShippingAccountHolder());

        try {
            if ($payment instanceof ProcessPaymentInterface) {
                $action = $payment->processPayment();

                if ($action) {
                    return $action;
                }
            }

            $response = $transactionService->process(
                $payment->getTransaction(),
                $payment->getPaymentConfig()->getTransactionOperation()
            );
        } catch (\Exception $e) {
            (new Logger())->error('Transaction service process failed: ' . $e->getMessage());
            return new ErrorAction(0, 'Transaction processing failed');
        }

        if ($response instanceof SuccessResponse || $response instanceof InteractionResponse) {
            return new RedirectAction($response->getRedirectUrl());
        }

        if ($response instanceof FailureResponse) {
            exit(var_dump($response->getData()));
        }

        return new ErrorAction(0, 'Payment processing failed');
    }

    private function prepareTransaction(
        OrderSummary $orderSummary,
        Redirect $redirect,
        $notificationUrl
    ) {
        $payment = $orderSummary->getPayment();
        $order   = $orderSummary->getOrder();

        $paymentConfig = $payment->getPaymentConfig();
        $transaction   = $payment->getTransaction();

        $customFields = new CustomFieldCollection();
        $customFields->add(new CustomField('order-id', $orderSummary->getOrder()->getId()));
        $transaction->setCustomFields($customFields);

        $transaction->setAmount(
            new Amount(BasketMapper::numberFormat($order->getBaseGrandTotal()), $order->getBaseCurrencyCode())
        );

        $transaction->setRedirect($redirect);
        $transaction->setNotificationUrl($notificationUrl);

        if ($paymentConfig->sendBasket() || $paymentConfig->hasFraudPrevention()) {
            $transaction->setBasket($orderSummary->getBasketMapper()->getWirecardBasket());
        }

        if ($paymentConfig->hasFraudPrevention()) {
            $transaction->setOrderNumber($orderSummary->getOrder()->getRealOrderId());
            $transaction->setDevice($orderSummary->getWirecardDevice());
            $transaction->setConsumerId($orderSummary->getOrder()->getCustomerId());
            $transaction->setIpAddress($orderSummary->getUserMapper()->getClientIp());
            $transaction->setAccountHolder($orderSummary->getUserMapper()->getWirecardBillingAccountHolder());
            $transaction->setShipping($orderSummary->getUserMapper()->getWirecardShippingAccountHolder());
            $transaction->setLocale($orderSummary->getUserMapper()->getLocale());
        }

        if ($paymentConfig->sendOrderIdentification() || $paymentConfig->hasFraudPrevention()) {
            $transaction->setDescriptor($this->getDescriptor($orderSummary->getOrder()->getRealOrderId()));
        }
    }

    protected function getDescriptor($orderNumber)
    {
        $shopName = substr($this->store->getName(), 0, 9);
        return substr($shopName . ' ' . $orderNumber, 0, 20);
    }
}
