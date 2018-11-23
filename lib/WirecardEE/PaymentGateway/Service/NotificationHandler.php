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
use Psr\Log\LoggerInterface;
use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;

/**
 * Handles notification responses. Notification responses are server-to-server, meaning you must NEVER access session
 * data in here.
 * Additionally notifications are the "source of truth", hence they are responsible for setting - respectively
 * updating - the payment status.
 *
 * @since 1.0.0
 */
class NotificationHandler
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TransactionManager
     */
    protected $transactionManager;

    /**
     * @param LoggerInterface $logger
     *
     * @since 1.0.0
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger             = $logger;
        $this->transactionManager = new TransactionManager($logger);
    }

    /**
     * @param Response       $response
     * @param BackendService $backendService
     *
     * @throws \Exception
     *
     * @since 1.0.0
     */
    public function handleResponse(Response $response, BackendService $backendService)
    {
        if ($response instanceof SuccessResponse) {
            $this->handleSuccess($response, $backendService);
        }
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

        $this->transactionManager->createTransaction(TransactionManager::TYPE_NOTIFY, $order, $response);

        if (in_array($order->getStatus(), [
            \Mage_Sales_Model_Order::STATE_COMPLETE,
            \Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW,
        ])) {
            return;
        }

        $status = $this->getOrderStatus($backendService, $response);
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
