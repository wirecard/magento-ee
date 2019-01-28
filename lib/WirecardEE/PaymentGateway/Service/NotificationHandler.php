<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Service;

use Mage_Sales_Model_Order;
use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardEE\PaymentGateway\Mapper\ResponseMapper;

/**
 * Handles notification responses. Notification responses are server-to-server, meaning you must NEVER access session
 * data in here.
 * Additionally notifications are the "source of truth", hence they are responsible for setting - respectively
 * updating - the payment status.
 *
 * @since 1.0.0
 */
class NotificationHandler extends Handler
{
    /**
     * Transaction types which automatically will be invoiced
     */
    const AUTO_INVOICE_TRANSACTION_TYPES = [Transaction::TYPE_PURCHASE, Transaction::TYPE_DEBIT];

    /**
     * @param Response       $response
     * @param BackendService $backendService
     *
     * @return \Mage_Sales_Model_Order_Payment_Transaction|null
     * @throws \Exception
     *
     * @since 1.0.0
     */
    public function handleResponse(Response $response, BackendService $backendService)
    {
        if ($response instanceof SuccessResponse) {
            return $this->handleSuccess($response, $backendService);
        }

        if ($response instanceof FailureResponse) {
            $this->logger->error("Failure response", ['response' => $response->getRawData()]);
            return null;
        }

        $this->logger->error("Unexpected notification response", [
            'class'    => get_class($response),
            'response' => $response->getData(),
        ]);
        return null;
    }

    /**
     * @param SuccessResponse $response
     * @param BackendService  $backendService
     *
     * @return \Mage_Sales_Model_Order_Payment_Transaction|null
     * @throws \Exception
     *
     * @since 1.0.0
     */
    protected function handleSuccess(SuccessResponse $response, BackendService $backendService)
    {
        $this->logger->info('Incoming success notification', ['response' => $response->getRawData()]);

        /** @var Mage_Sales_Model_Order $order */
        $order = \Mage::getModel('sales/order')->load($response->getCustomFields()->get('order-id'));
        if (! $order->getId()) {
            if (! $orderNumber = (new ResponseMapper($response))->getOrderNumber()) {
                $this->logger->error("Order not found for transaction " . $response->getTransactionId());
                throw new \Exception("Order not found");
            }

            $order = $order->loadByIncrementId($orderNumber);
        }

        $refundableBasket = [];

        // Automatically invoice purchases.
        if (in_array($response->getTransactionType(), self::AUTO_INVOICE_TRANSACTION_TYPES) && $order->canInvoice()) {
            /** @var \Mage_Sales_Model_Order_Invoice $invoice */
            $invoice = $order->prepareInvoice()->register();
            $invoice->setData('auto_capture', true);
            $invoice->capture();
            $invoice->addComment(
                \Mage::helper('catalog')->__('invoice_created')
            );
            $invoice->save();

            /** @var \Mage_Core_Model_Resource_Transaction $resourceTransaction */
            $resourceTransaction = \Mage::getModel('core/resource_transaction');
            $resourceTransaction
                ->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();

            foreach ($order->getAllVisibleItems() as $item) {
                /** @var \Mage_Sales_Model_Order_Item $item */
                $refundableBasket[$item->getId()] = (int)$item->getQtyOrdered();
            }
            if ($order->getShippingAmount() > 0.0) {
                $refundableBasket[TransactionManager::ADDITIONAL_AMOUNT_KEY] = $order->getShippingAmount();
            }
        }

        $transaction = $this->transactionManager->createTransaction(
            TransactionManager::TYPE_NOTIFY,
            $order,
            $response,
            [TransactionManager::REFUNDABLE_BASKET_KEY => json_encode($refundableBasket)]
        );

        if (in_array($order->getState(), [
            \Mage_Sales_Model_Order::STATE_COMPLETE,
            \Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW,
            \Mage_Sales_Model_Order::STATE_CLOSED,
        ])) {
            return $transaction;
        }

        $state = $this->getOrderState($backendService, $response);
        // Don't update if state equals order state or back to pending
        if ($state === $order->getState()
            || ($order->getState() !== \Mage_Sales_Model_Order::STATE_NEW
                && $state === \Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)) {
            return $transaction;
        }

        $sendEmail = $this->shouldSendOrderUpdateEmail($order);
        $order->setState($state, $state, \Mage::helper('paymentgateway')->__('order_status_updated'), $sendEmail);
        $order->save();

        if ($sendEmail) {
            $order->sendOrderUpdateEmail();
        }

        return $transaction;
    }

    /**
     * @param BackendService $backendService
     * @param Response       $response
     *
     * @return int
     *
     * @since 1.0.0
     */
    private function getOrderState($backendService, $response)
    {
        if ($response->getTransactionType() === 'check-payer-response') {
            return \Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        }
        switch ($backendService->getOrderState($response->getTransactionType())) {
            case BackendService::TYPE_AUTHORIZED:
            case BackendService::TYPE_PROCESSING:
                return \Mage_Sales_Model_Order::STATE_PROCESSING;
            case BackendService::TYPE_CANCELLED:
                return \Mage_Sales_Model_Order::STATE_CANCELED;
            default:
                return \Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return bool
     *
     * @since 1.0.0
     */
    private function shouldSendOrderUpdateEmail(\Mage_Sales_Model_Order $order)
    {
        if ($order->getState() === \Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
            && ! \Mage::getStoreConfig('wirecardee_paymentgateway/settings/pending_mail')) {
            return false;
        }
        return true;
    }
}
