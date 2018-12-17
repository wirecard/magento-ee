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
     * @param Response       $response
     * @param BackendService $backendService
     *
     * @return null
     * @throws \Exception
     *
     * @since 1.0.0
     */
    public function handleResponse(Response $response, BackendService $backendService)
    {
        if ($response instanceof SuccessResponse) {
            /** @var Mage_Sales_Model_Order $order */
            $order = \Mage::getModel('sales/order')->load($response->getCustomFields()->get('order-id'));
            $this->handleSuccess($response, $backendService);
            $this->transactionManager->createTransaction(
                TransactionManager::TYPE_NOTIFY,
                $order,
                $response
            );
            return;
        }

        if ($response instanceof FailureResponse) {
            $this->logger->error("Failure response", ['response' => $response->getRawData()]);
            return;
        }

        $this->logger->error("Unexpected notification response", [
            'class'    => get_class($response),
            'response' => $response->getData(),
        ]);
        return;
    }

    /**
     * @param SuccessResponse $response
     * @param BackendService  $backendService
     *
     * @throws \Exception
     *
     * @since 1.0.0
     */
    protected function handleSuccess(SuccessResponse $response, BackendService $backendService)
    {
        $this->logger->info('Incoming success notification', ['response' => $response->getRawData()]);

        /** @var Mage_Sales_Model_Order $order */
        $order = \Mage::getModel('sales/order')->load($response->getCustomFields()->get('order-id'));
        if (! $order) {
            $this->logger->error("Order not found for notification " . $response->getTransactionId());
            throw new \Exception("Order not found");
        }

        $refundableBasket = [];

        // Automatically invoice purchases.
        if ($response->getTransactionType() === Transaction::TYPE_PURCHASE) {
            if ($order->canInvoice()) {
                /** @var \Mage_Sales_Model_Order_Invoice $invoice */
                $invoice = $order->prepareInvoice()->register();
                $invoice->setData('auto_capture', true);
                $invoice->capture();

                /** @var \Mage_Core_Model_Resource_Transaction $resourceTransaction */
                $resourceTransaction = \Mage::getModel('core/resource_transaction');
                $resourceTransaction
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder())
                    ->save();

                foreach ($order->getAllVisibleItems() as $item) {
                    /** @var \Mage_Sales_Model_Order_Item $item */
                    $refundableBasket[$item->getProductId()] = (int)$item->getQtyOrdered();
                }
                if ($order->getShippingAmount() > 0.0) {
                    $refundableBasket[TransactionManager::ADDITIONAL_AMOUNT_KEY] = $order->getShippingAmount();
                }
            }
        }

        $this->transactionManager->createTransaction(
            TransactionManager::TYPE_NOTIFY,
            $order,
            $response,
            [TransactionManager::REFUNDABLE_BASKET_KEY => json_encode($refundableBasket)]
        );

        if (in_array($order->getStatus(), [
            \Mage_Sales_Model_Order::STATE_COMPLETE,
            \Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW,
        ])) {
            return;
        }

        $status = $this->getOrderStatus($backendService, $response);
        if ($status === \Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
            return;
        }

        $order->addStatusHistoryComment('Status updated by notification', $status);
        $order->save();
    }

    /**
     * @param BackendService $backendService
     * @param Response       $response
     *
     * @return int
     *
     * @since 1.0.0
     */
    private function getOrderStatus($backendService, $response)
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
}
