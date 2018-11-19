<?php

namespace WirecardEE\PaymentGateway\Service;

use Mage_Sales_Model_Order;
use Psr\Log\LoggerInterface;
use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;

class NotificationHandler
{
    protected $logger;
    protected $transactionManager;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger             = $logger;
        $this->transactionManager = new TransactionManager($logger);
    }

    public function handleResponse(Response $response, BackendService $backendService)
    {
        if ($response instanceof SuccessResponse) {
            $this->handleSuccess($response, $backendService);
        }
    }

    protected function handleSuccess(SuccessResponse $response, BackendService $backendService)
    {
        $this->logger->info('Incoming success notification', $response->getData());

        /** @var Mage_Sales_Model_Order $order */
        $order = \Mage::getModel('sales/order')->load($response->getCustomFields()->get('order-id'));
        if (! $order) {
            $this->logger->error("Order not found for notification " . $response->getTransactionId());
            throw new \Exception("Order not found");
        }

//        $this->transactionManager->createTransaction($order, $response);

        if ($order->getStatus() !== 'pending') {
            return;
        }

        $status = $this->getOrderStatus($backendService, $response);
        $order->addStatusHistoryComment('Status updated', $status);
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
            case BackendService::TYPE_PROCESSING:
                return \Mage_Sales_Model_Order::STATE_COMPLETE;
            case BackendService::TYPE_AUTHORIZED:
                return \Mage_Sales_Model_Order::STATE_PROCESSING;
            case BackendService::TYPE_CANCELLED:
                return \Mage_Sales_Model_Order::STATE_CANCELED;
            default:
                return \Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        }
    }
}
