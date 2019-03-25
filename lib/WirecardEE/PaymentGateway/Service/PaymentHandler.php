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
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\Action;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Actions\RedirectAction;
use WirecardEE\PaymentGateway\Actions\ViewAction;
use WirecardEE\PaymentGateway\Mapper\BasketMapper;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessPaymentInterface;

/**
 * Responsible for handling the payment. Payments may implement their own way of handling payments by implementing
 * the `ProcessPaymentInterface` interface.
 * Ultimately a proper `Action` is returned to the controller.
 *
 * @since   1.0.0
 */
class PaymentHandler extends Handler
{
    const DESCRIPTOR_MAX_LENGTH = 20;
    const DESCRIPTOR_SHOP_NAME_MAX_LENGTH = 9;

    /**
     * @param OrderSummary       $orderSummary
     * @param TransactionService $transactionService
     * @param Redirect           $redirect
     * @param string             $notificationUrl
     *
     * @return Action
     * @throws \Mage_Core_Exception
     *
     * @since   1.0.0
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
                $action = $payment->processPayment($orderSummary, $transactionService, $redirect);

                if ($action) {
                    return $action;
                }
            }

            $response = $transactionService->process(
                $payment->getTransaction(),
                $payment->getPaymentConfig()->getTransactionOperation()
            );
        } catch (\Exception $e) {
            (new Logger())
                ->error('Transaction service process failed: ' . $e->getMessage() . ' (' . get_class($e) . ')');
            return new ErrorAction(ErrorAction::PROCESSING_FAILED, 'order_error');
        }

        if ($response instanceof FormInteractionResponse) {
            $this->transactionManager->createTransaction(
                TransactionManager::TYPE_INITIAL,
                $orderSummary->getOrder(),
                $response
            );
            return new ViewAction('paymentgateway/redirect', [
                'method'     => $response->getMethod(),
                'formFields' => $response->getFormFields(),
                'url'        => $response->getUrl(),
            ]);
        }
        if ($response instanceof SuccessResponse || $response instanceof InteractionResponse) {
            $this->transactionManager->createTransaction(
                TransactionManager::TYPE_INITIAL,
                $orderSummary->getOrder(),
                $response
            );
            return new RedirectAction($response->getRedirectUrl());
        }
        if ($response instanceof FailureResponse) {
            $this->logger->error('Failure response', $response->getData());
            return new ErrorAction(ErrorAction::FAILURE_RESPONSE, 'order_error');
        }
        return new ErrorAction(ErrorAction::PROCESSING_FAILED, 'order_error');
    }

    /**
     * Prepares the transaction for being sent to Wirecard by adding specific (e.g. amount) and optional (e.g. fraud
     * prevention data) data to the `Transaction` object of the payment.
     * Keep in mind that the transaction returned by the payment is ALWAYS the same instance, hence we don't need to
     * return the transaction here.
     *
     * @param OrderSummary $orderSummary
     * @param Redirect     $redirect
     * @param              $notificationUrl
     *
     * @since   1.0.0
     * @throws \Mage_Core_Model_Store_Exception
     */
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
            $transaction->setBasket($orderSummary->getBasketMapper()->getBasket());
        }

        if ($paymentConfig->hasFraudPrevention()) {
            $transaction->setOrderNumber($orderSummary->getOrder()->getRealOrderId());
            $transaction->setDevice($orderSummary->getWirecardDevice());
            $transaction->setConsumerId($orderSummary->getOrder()->getCustomerId());
            $transaction->setIpAddress($orderSummary->getUserMapper()->getClientIp());
            $transaction->setAccountHolder($orderSummary->getUserMapper()->getBillingAccountHolder());
            $transaction->setShipping($orderSummary->getUserMapper()->getShippingAccountHolder());
            $transaction->setLocale($orderSummary->getUserMapper()->getLocale());
        }

        if ($paymentConfig->sendOrderIdentification() || $paymentConfig->hasFraudPrevention()) {
            $transaction->setDescriptor(
                $this->getDescriptor(
                    \Mage::app()->getStore()->getFrontendName(),
                    $orderSummary->getOrder()->getRealOrderId()
                )
            );
        }
    }

    /**
     * Returns the descriptor sent to Wirecard. Change to your own needs.
     *
     * @param string     $shopName
     * @param string|int $orderNumber
     *
     * @return string
     *
     * @since   1.0.0
     */
    protected function getDescriptor($shopName, $orderNumber)
    {
        $orderNumberMaxLength = strlen($shopName) < self::DESCRIPTOR_SHOP_NAME_MAX_LENGTH
            ? self::DESCRIPTOR_MAX_LENGTH - strlen($shopName)
            : self::DESCRIPTOR_MAX_LENGTH - self::DESCRIPTOR_SHOP_NAME_MAX_LENGTH;

        if (strlen($orderNumber) > $orderNumberMaxLength) {
            $orderNumber = substr($orderNumber, (strlen($orderNumber) - $orderNumberMaxLength));
        }

        return substr($shopName, 0, self::DESCRIPTOR_SHOP_NAME_MAX_LENGTH) . ' ' . $orderNumber;
    }
}
