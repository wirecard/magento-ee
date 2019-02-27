<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use Psr\Log\LoggerInterface;
use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Entity\Amount;
use WirecardEE\PaymentGateway\Actions\Action;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Actions\SuccessAction;
use WirecardEE\PaymentGateway\Exception\UnknownPaymentException;
use WirecardEE\PaymentGateway\Payments\PaymentInterface;
use WirecardEE\PaymentGateway\Service\BackendOperationsHandler;
use WirecardEE\PaymentGateway\Service\Logger;
use WirecardEE\PaymentGateway\Service\PaymentFactory;
use WirecardEE\PaymentGateway\Service\TransactionManager;

/**
 * @since 1.0.0
 */
class WirecardEE_PaymentGateway_Model_BackendOperation
{
    /**
     * @var BackendOperationsHandler
     */
    protected $backendOperationHandler;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var PaymentFactory
     */
    protected $paymentFactory;

    /**
     * @var TransactionManager
     */
    protected $transactionManager;

    /**
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->logger                  = new Logger();
        $this->paymentFactory          = new PaymentFactory();
        $this->transactionManager      = new TransactionManager($this->logger);
        $this->backendOperationHandler = new BackendOperationsHandler($this->transactionManager, $this->logger);
    }

    /**
     * @param BackendOperationsHandler $backendOperationHandler
     *
     * @since 1.0.0
     */
    public function setBackendOperationHandler(BackendOperationsHandler $backendOperationHandler)
    {
        $this->backendOperationHandler = $backendOperationHandler;
    }

    /**
     * @param TransactionManager $transactionManager
     *
     * @since 1.0.0
     */
    public function setTransactionManager(TransactionManager $transactionManager)
    {
        $this->transactionManager = $transactionManager;
    }

    /**
     * @param PaymentFactory $paymentFactory
     *
     * @since 1.1.0
     */
    public function setPaymentFactory(PaymentFactory $paymentFactory)
    {
        $this->paymentFactory = $paymentFactory;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return Action|null
     * @throws Mage_Core_Exception
     * @throws UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function capture(Varien_Event_Observer $observer)
    {
        $invoice = $observer->getData('invoice');

        if (! ($invoice instanceof Mage_Sales_Model_Order_Invoice)) {
            \Mage::throwException("Unable to process backend operation (capture)");
        }

        if (! $this->paymentFactory->isSupportedPayment($invoice->getOrder()->getPayment())) {
            return null;
        }

        // Skip auto-generated invoices.
        if ($invoice->getData('auto_capture')) {
            return null;
        }

        $invoicePost = Mage::app()->getRequest()->getPost('invoice', []);
        if (empty($invoicePost['items'])) {
            \Mage::throwException("Unable to capture empty invoice");
        }

        $payment = $this->paymentFactory->createFromMagePayment($invoice->getOrder()->getPayment());

        if (! $payment->getCaptureOperation()) {
            return null;
        }

        $initialNotification = $this->transactionManager->findInitialNotification($invoice->getOrder());

        if (! $initialNotification) {
            $this->throwError("Capture failed (unable to find initial notification transaction)");
        }

        $refundableBasket                                            = $invoicePost['items'];
        $refundableBasket[TransactionManager::ADDITIONAL_AMOUNT_KEY] = $invoice->getShippingAmount() > 0.0
            ? $invoice->getShippingAmount()
            : 0;

        $action = $this->processBackendOperation(
            $initialNotification,
            $payment,
            new BackendService(
                $payment->getTransactionConfig($invoice->getOrderCurrencyCode()),
                $this->logger
            ),
            $payment->getCaptureOperation(),
            new Amount($invoice->getGrandTotal(), $invoice->getBaseCurrencyCode()),
            [TransactionManager::REFUNDABLE_BASKET_KEY => json_encode($refundableBasket)]
        );

        if ($action instanceof SuccessAction) {
            $amount = $action->getContextItem('amount');

            $invoice->save();
            $this->logger->info("Captured $amount from {$initialNotification->getTxnId()}");
            return $action;
        }
        if ($action instanceof ErrorAction) {
            $this->throwError($action->getMessage(), ['code' => $action->getCode()]);
        }

        $this->throwError('Capture failed');
        return $action;
    }

    /**
     * Refunds are processed by finding proper transactions and apply the operation on each of them.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Action[]
     * @throws Mage_Core_Exception
     * @throws \WirecardEE\PaymentGateway\Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function refund(Varien_Event_Observer $observer)
    {
        $creditMemo     = $observer->getData('creditmemo');
        $creditMemoPost = Mage::app()->getRequest()->getPost('creditmemo', []);

        if (! ($creditMemo instanceof Mage_Sales_Model_Order_Creditmemo) || empty($creditMemoPost['items'])) {
            $this->throwError("Unable to process backend operation (refund)");
        }

        if (! $this->paymentFactory->isSupportedPayment($creditMemo->getOrder()->getPayment())) {
            return [];
        }

        $payment = $this->paymentFactory->createFromMagePayment($creditMemo->getOrder()->getPayment());

        if (! $payment->getRefundOperation()) {
            return null;
        }

        $backendService = new BackendService(
            $payment->getTransactionConfig($creditMemo->getOrderCurrencyCode()),
            $this->logger
        );

        $refundableTransactions = $this->transactionManager->findRefundableTransactions(
            $creditMemo->getOrder(),
            $payment,
            $backendService
        );

        if (count($refundableTransactions) === 0) {
            $this->throwError("No refundable transactions found (either transactions are lacking " .
                              "a refundable basket or desired backend operation is not supported on any transaction " .
                              "for this order)");
        }

        $remainingAdditionalAmount = $creditMemo->getShippingAmount()
                                     + $creditMemo->getAdjustmentPositive()
                                     - $creditMemo->getAdjustmentNegative();

        foreach ($creditMemo->getAllItems() as $item) {
            /** @var Mage_Sales_Model_Order_Creditmemo_Item $item */
            if (array_key_exists($item->getOrderItemId(), $creditMemoPost['items'])) {
                $creditMemoPost['items'][$item->getOrderItemId()]['price'] = $item->getBasePriceInclTax();
            }
        }

        $transactionEntries = [];
        $transactionEntries = $this->findTransactionsForAdditionalAmount(
            $transactionEntries,
            $refundableTransactions,
            $remainingAdditionalAmount
        );
        $transactionEntries = $this->findTransactionsForItems(
            $transactionEntries,
            $refundableTransactions,
            $creditMemoPost['items']
        );

        if (count($transactionEntries) === 0) {
            $this->throwError('Unable to refund (no proper refundable baskets)', [
                'refundableTransactions' => $refundableTransactions,
            ]);
        }

        $this->logger->info('Executing operation "refund" on order #' . $creditMemo->getOrder()->getRealOrderId()
                            . ' (split across ' . count($transactionEntries) . ' transactions)');

        $actions = [];
        foreach ($transactionEntries as $transactionEntry) {
            /** @var Mage_Sales_Model_Order_Payment_Transaction $transaction */
            $transaction = $transactionEntry['transaction'];
            $amount      = $transactionEntry['amount'];

            if ($amount <= 0) {
                continue;
            }

            $action    = $this->processBackendOperation(
                $transaction,
                $payment,
                $backendService,
                $payment->getRefundOperation(),
                new Amount($amount, $creditMemo->getBaseCurrencyCode())
            );
            $actions[] = $action;

            if ($action instanceof SuccessAction) {
                $transactionId = $action->getContextItem('transaction_id');
                $this->logger->info("Refunded $amount from $transactionId");

                $additionalInformation = TransactionManager::getAdditionalInformationFromTransaction($transaction);
                $refundableBasket      = TransactionManager::getRefundableBasketFromTransaction($transaction);

                $transaction->setOrderPaymentObject($creditMemo->getOrder()->getPayment());

                foreach ($transactionEntry['basket'] as $key => $value) {
                    if (isset($refundableBasket[$key])) {
                        $refundableBasket[$key] -= $value;
                    }
                }

                $transaction->setAdditionalInformation(
                    \Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                    array_merge($additionalInformation, [
                        TransactionManager::REFUNDABLE_BASKET_KEY => json_encode($refundableBasket),
                    ])
                );

                $transaction->save();
            }
            if ($action instanceof ErrorAction) {
                $this->throwError($action->getMessage(), ['code' => $action->getCode()]);
            }
        }

        return $actions;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return Action|null
     * @throws Mage_Core_Exception
     * @throws \WirecardEE\PaymentGateway\Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function cancel(Varien_Event_Observer $observer)
    {
        $magePayment = $observer->getData('payment');

        if (! ($magePayment instanceof Mage_Sales_Model_Order_Payment)) {
            $this->throwError("Unable to process backend operation (cancel)");
        }

        if (! $this->paymentFactory->isSupportedPayment($magePayment)) {
            return null;
        }

        $payment = $this->paymentFactory->createFromMagePayment($magePayment);

        if (! $payment->getCancelOperation()) {
            return null;
        }

        $initialNotification = $this->transactionManager->findInitialNotification($magePayment->getOrder());

        if (! $initialNotification) {
            return $this->preventOperation(
                $magePayment->getOrder(),
                \Mage_Sales_Model_Order::ACTION_FLAG_CANCEL,
                "Cancellation failed (unable to find initial notification transaction)"
            );
        }

        $action = $this->processBackendOperation(
            $initialNotification,
            $payment,
            new BackendService(
                $payment->getTransactionConfig($magePayment->getOrder()->getOrderCurrencyCode()),
                $this->logger
            ),
            $payment->getCancelOperation()
        );

        if ($action instanceof SuccessAction) {
            $transactionId = $action->getContextItem('transaction_id');
            $this->logger->info("Cancelled $transactionId");
            return $action;
        }
        if ($action instanceof ErrorAction) {
            return $this->preventOperation(
                $magePayment->getOrder(),
                \Mage_Sales_Model_Order::ACTION_FLAG_CANCEL,
                $action->getMessage(),
                ['code' => $action->getCode()]
            );
        }

        $this->throwError('Cancellation failed');
        return $action;
    }

    /**
     * @param string $message
     * @param array  $context
     *
     * @return void
     * @throws Mage_Core_Exception
     *
     * @since 1.0.0
     */
    private function throwError($message, array $context = [])
    {
        $this->logger->error($message, $context);
        \Mage::throwException(\Mage::helper('catalog')
                                   ->__('An error has occurred during executing your request. ' .
                                        'Check log files for further information.'));
    }

    /**
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param PaymentInterface                           $payment
     * @param BackendService                             $backendService
     * @param string                                     $operation
     * @param Amount|null                                $amount
     * @param array                                      $transactionContext
     *
     * @return ErrorAction|SuccessAction
     *
     * @since 1.0.0
     */
    private function processBackendOperation(
        Mage_Sales_Model_Order_Payment_Transaction $transaction,
        PaymentInterface $payment,
        BackendService $backendService,
        $operation,
        Amount $amount = null,
        $transactionContext = []
    ) {
        $amountText = $amount ? ($amount->getValue() . ' ' . $amount->getCurrency()) : '';
        $this->logger->info("Executing operation $operation on " . $transaction->getTxnId()
                            . " (ID: " . $transaction->getId() . ") " . ($amountText ? " - Amount: $amountText" : ""));

        $backendTransaction = $payment->getBackendTransaction(
            $transaction->getOrder(),
            $operation,
            $transaction
        );
        $backendTransaction->setParentTransactionId($transaction->getTxnId());
        if ($amount) {
            $backendTransaction->setAmount($amount);
        }

        $action = $this->backendOperationHandler->execute(
            $backendTransaction,
            $backendService,
            $operation,
            $transactionContext
        );

        return $action;
    }

    /**
     * @param array                                        $suitableTransactions
     * @param Mage_Sales_Model_Order_Payment_Transaction[] $refundableTransactions
     * @param float                                        $remainingAdditionalAmount
     *
     * @return array
     * @throws Mage_Core_Exception
     *
     * @since 1.0.0
     */
    private function findTransactionsForAdditionalAmount(
        array $suitableTransactions,
        array $refundableTransactions,
        $remainingAdditionalAmount
    ) {
        if ($remainingAdditionalAmount <= 0) {
            return $suitableTransactions;
        }

        foreach ($refundableTransactions as $refundableTransaction) {
            if (! $refundableBasket = TransactionManager::getRefundableBasketFromTransaction($refundableTransaction)) {
                continue;
            }

            if (! isset($refundableBasket[TransactionManager::ADDITIONAL_AMOUNT_KEY])
                || $refundableBasket[TransactionManager::ADDITIONAL_AMOUNT_KEY] === 0) {
                continue;
            }

            $refundableAmount = floatval($refundableBasket[TransactionManager::ADDITIONAL_AMOUNT_KEY]);

            $amount = ($remainingAdditionalAmount > $refundableAmount)
                ? $refundableAmount
                : $remainingAdditionalAmount;

            if (! array_key_exists($refundableTransaction->getId(), $suitableTransactions)) {
                $suitableTransactions[$refundableTransaction->getId()] = [
                    'amount'      => 0,
                    'basket'      => [TransactionManager::ADDITIONAL_AMOUNT_KEY => 0],
                    'transaction' => $refundableTransaction,
                ];
            }

            $suitableTransactions[$refundableTransaction->getId()]['amount']                                            += $amount;
            $suitableTransactions[$refundableTransaction->getId()]['basket'][TransactionManager::ADDITIONAL_AMOUNT_KEY] += $amount;

            $remainingAdditionalAmount -= $amount;

            if ($remainingAdditionalAmount <= 0) {
                break;
            }
        }

        if ($remainingAdditionalAmount > 0) {
            $this->throwError("Unable to refund additional amount ($remainingAdditionalAmount)");
        }

        return $suitableTransactions;
    }

    /**
     * @param array                                        $suitableTransactions
     * @param Mage_Sales_Model_Order_Payment_Transaction[] $refundableTransactions
     * @param array                                        $items
     *
     * @return array
     * @throws Mage_Core_Exception
     *
     * @since 1.0.0
     */
    private function findTransactionsForItems(
        array $suitableTransactions,
        array $refundableTransactions,
        array $items
    ) {
        if (! $items) {
            return $suitableTransactions;
        }

        foreach ($items as $item => $meta) {
            if (empty($meta['qty']) || empty($meta['price'])) {
                continue;
            }

            $remainingQuantity = $meta['qty'];

            foreach ($refundableTransactions as $refundableTransaction) {
                if (! $refundableBasket = TransactionManager::getRefundableBasketFromTransaction($refundableTransaction)) {
                    continue;
                }

                // Check if the product is in this transaction
                if (! isset($refundableBasket[$item])) {
                    continue;
                }

                $refundableQuantity = (int)$refundableBasket[$item];

                $quantity = ($remainingQuantity > $refundableQuantity)
                    ? $refundableQuantity
                    : $remainingQuantity;

                if (! array_key_exists($refundableTransaction->getId(), $suitableTransactions)) {
                    $suitableTransactions[$refundableTransaction->getId()] = [
                        'amount'      => 0,
                        'basket'      => [TransactionManager::ADDITIONAL_AMOUNT_KEY => 0],
                        'transaction' => $refundableTransaction,
                    ];
                }

                $suitableTransactions[$refundableTransaction->getId()]['amount']        += $quantity
                                                                                           * $meta['price'];
                $suitableTransactions[$refundableTransaction->getId()]['basket'][$item] = $quantity;

                $remainingQuantity -= $quantity;

                if ($remainingQuantity <= 0) {
                    break;
                }
            }

            if ($remainingQuantity > 0) {
                $this->throwError("Unable to refund item {$item} (remaining quantity: $remainingQuantity)");
            }
        }

        return $suitableTransactions;
    }

    /**
     * Checks if the session messages should be flushed and, if applicable, remove all messages but error messages.
     *
     * @since 1.1.0
     */
    public function checkSessionMessages()
    {
        if (! $this->getAdminSession()->getData('flush_messages')) {
            return;
        }

        $messages = $this->getAdminSession()->getMessages();
        $errors   = [];
        foreach ($messages->getItems() as $message) {
            if ($message instanceof \Mage_Core_Model_Message_Error) {
                $errors[] = $message;
            }
        }

        if (count($errors) > 0) {
            $this->getAdminSession()->getMessages(true);
            foreach ($errors as $error) {
                /** @var $error Mage_Core_Model_Message_Abstract */
                $this->getAdminSession()->addMessage($error);
            }
        }
    }

    /**
     * Prevents a backend operation by setting a proper flag.
     *
     * @param Mage_Sales_Model_Order $order
     * @param string                 $actionFlag
     * @param string                 $reason
     * @param array                  $context
     *
     * @return null
     */
    private function preventOperation(\Mage_Sales_Model_Order $order, $actionFlag, $reason, $context = [])
    {
        $order->setActionFlag($actionFlag, false);
        $this->getAdminSession()->setData('flush_messages', true);
        $this->getAdminSession()->addError(\Mage::helper('catalog')
                                                ->__('An error has occurred during executing your request (Order #'
                                                     . $order->getRealOrderId() .
                                                     '). Check log files for further information.'));
        $this->logger->error($reason, $context);

        return null;
    }

    /**
     * @return Mage_Adminhtml_Model_Session|Mage_Core_Model_Abstract
     *
     * @since 1.1.0
     */
    private function getAdminSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }
}
