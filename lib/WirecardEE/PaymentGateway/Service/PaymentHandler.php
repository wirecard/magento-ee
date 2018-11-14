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
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessPaymentInterface;

class PaymentHandler
{
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

//        exit();

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
        $payment       = $orderSummary->getPayment();
        $order         = $orderSummary->getOrder();

        $paymentConfig = $payment->getPaymentConfig();
        $transaction   = $payment->getTransaction();

        $transaction->setAmount(
            new Amount(BasketMapper::numberFormat($order->getBaseGrandTotal()), $order->getBaseCurrencyCode())
        );

        $transaction->setRedirect($redirect);
        $transaction->setNotificationUrl($notificationUrl);

        if ($paymentConfig->sendBasket() || $paymentConfig->hasFraudPrevention()) {
            $transaction->setBasket($orderSummary->getBasketMapper()->getWirecardBasket());
        }
    }

    protected function getDescriptor()
    {
        return '';
    }
}
