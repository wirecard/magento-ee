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
use Wirecard\PaymentSdk\Transaction\Operation;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Actions\SuccessAction;
use WirecardEE\PaymentGateway\Service\BackendOperationsHandler;
use WirecardEE\PaymentGateway\Service\Logger;
use WirecardEE\PaymentGateway\Service\PaymentFactory;
use WirecardEE\PaymentGateway\Service\TransactionManager;

/**
 * @since 1.0.0
 */
class WirecardEE_PaymentGateway_Model_BackendOperation
{
    const ERROR_MESSAGE_SESSION_KEY = 'wirecardee_backendoperation_error_message';

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
     * @param Varien_Event_Observer $observer
     *
     * @throws Mage_Core_Exception
     * @throws \WirecardEE\PaymentGateway\Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function capture(Varien_Event_Observer $observer)
    {
        $invoice = $observer->getData('invoice');

        if (! ($invoice instanceof Mage_Sales_Model_Order_Invoice)) {
            \Mage::throwException("Unable to process backend operation (capture)");
            return;
        }

        if (! $this->paymentFactory->isSupportedPayment($invoice->getOrder()->getPayment())) {
            return;
        }

        // Skip auto-generated invoices.
        if ($invoice->getData('auto_capture')) {
            return;
        }

        $payment = $this->paymentFactory->createFromMagePayment($invoice->getOrder()->getPayment());
        $backendService      = new BackendService(
            $payment->getTransactionConfig(Mage::app()->getLocale()->getCurrency()),
            $this->logger
        );
        $initialNotification = $this->transactionManager->findInitialNotification($invoice->getOrder());

        if (! $initialNotification) {
            $invoice->getOrder()->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_INVOICE, false);
            $this->handleError("Capture failed (unable to find initial notification transaction)");
            return;
        }

        $this->logger->info('Executing operation "capture" on transaction ' . $initialNotification->getTxnId()
                            . " (ID: " . $initialNotification->getId() . ")");

        $backendTransaction = $payment->getBackendTransaction();
        $backendTransaction->setParentTransactionId($initialNotification->getTxnId());
        $backendTransaction->setAmount(new Amount($invoice->getGrandTotal(), $invoice->getBaseCurrencyCode()));

        if (! array_key_exists(Operation::PAY,
            $backendService->retrieveBackendOperations($backendTransaction)
        )) {
            $invoice->getOrder()->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_INVOICE, false);
            $this->handleError("Operation (Capture) not allowed on transaction " . $initialNotification->getTxnId());
            return;
        }

        $refundableBasket = [];
        foreach ($invoice->getAllItems() as $item) {
            /** @var Mage_Sales_Model_Order_Invoice_Item $item */
            $refundableBasket[$item->getProductId()] = (int)$item->getQty();
        }
        $refundableBasket[TransactionManager::ADDITIONAL_AMOUNT_KEY] = $invoice->getShippingAmount() > 0.0
            ? $invoice->getShippingAmount()
            : 0;

        $action = $this->backendOperationHandler->execute(
            $backendTransaction,
            $backendService,
            Operation::PAY,
            [TransactionManager::REFUNDABLE_BASKET_KEY => json_encode($refundableBasket)]
        );

        if ($action instanceof SuccessAction) {
            $transactionId = $action->getContextItem('transaction_id');
            $amount        = $action->getContextItem('amount');

            $invoice->setTransactionId($transactionId);

            $this->logger->info("Captured $amount from $transactionId");
            return;
        }
        if ($action instanceof ErrorAction) {
            $invoice->getOrder()->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_INVOICE, false);
            $this->handleError($action->getMessage(), ['code' => $action->getCode()]);
            return;
        }

        $invoice->getOrder()->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_INVOICE, false);
        $this->handleError('Capture failed');
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @throws Mage_Core_Exception
     * @throws \WirecardEE\PaymentGateway\Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function refund(Varien_Event_Observer $observer)
    {
        $creditMemo = $observer->getData('creditmemo');

        if (! ($creditMemo instanceof Mage_Sales_Model_Order_Creditmemo)) {
            $this->handleError("Unable to process backend operation (refund)");
            return;
        }

        if (! $this->paymentFactory->isSupportedPayment($creditMemo->getOrder()->getPayment())) {
            return;
        }

        $payment = $this->paymentFactory->createFromMagePayment($creditMemo->getOrder()->getPayment());
        $backendService = new BackendService(
            $payment->getTransactionConfig(Mage::app()->getLocale()->getCurrency()),
            $this->logger
        );

        $refundableTransactions = $this->transactionManager->findRefundableTransactions(
            $creditMemo->getOrder(),
            $payment,
            $backendService
        );

        $remainingAdditionalAmount = $creditMemo->getShippingAmount()
                                     + $creditMemo->getAdjustmentPositive()
                                     - $creditMemo->getAdjustmentNegative();

        $transactionEntries = [];
        $this->findTransactionsForAdditionalAmount(
            $transactionEntries,
            $refundableTransactions,
            $remainingAdditionalAmount
        );
        $this->findTransactionsForItems(
            $transactionEntries,
            $refundableTransactions,
            $creditMemo->getAllItems()
        );

        if (count($transactionEntries) === 0) {
            $this->handleError('Unable to refund (no transactions found)');
        }

        $this->logger->info('Executing operation "refund" on order #' . $creditMemo->getOrder()->getRealOrderId()
                            . ' (split across ' . count($transactionEntries) . ' transactions)');

        foreach ($transactionEntries as $transactionEntry) {
            /** @var Mage_Sales_Model_Order_Payment_Transaction $transaction */
            $transaction = $transactionEntry['transaction'];
            $amount      = $transactionEntry['amount'];

            if ($amount <= 0) {
                continue;
            }

            $backendTransaction = $payment->getBackendTransaction();
            $backendTransaction->setParentTransactionId($transaction->getTxnId());
            $backendTransaction->setAmount(
                new Amount($amount, $creditMemo->getBaseCurrencyCode())
            );

            $action = $this->backendOperationHandler->execute($backendTransaction, $backendService, Operation::REFUND);

            if ($action instanceof SuccessAction) {
                $transactionId = $action->getContextItem('transaction_id');
                $this->logger->info("Refunded $amount from $transactionId");

                $additionalInformation = $transaction->getAdditionalInformation(
                    \Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS
                );
                $refundableBasket      = json_decode(
                    $additionalInformation[TransactionManager::REFUNDABLE_BASKET_KEY],
                    true
                );

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
                $this->handleError($action->getMessage(), ['code' => $action->getCode()]);
                return;
            }
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @throws Mage_Core_Exception
     * @throws \WirecardEE\PaymentGateway\Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function cancel(Varien_Event_Observer $observer)
    {
        $magePayment = $observer->getData('payment');

        if (! ($magePayment instanceof Mage_Sales_Model_Order_Payment)) {
            \Mage::throwException("Unable to process backend operation (cancel)");
        }

        if (! $this->paymentFactory->isSupportedPayment($magePayment)) {
            return;
        }

        $payment = $this->paymentFactory->createFromMagePayment($magePayment);
        $backendService      = new BackendService(
            $payment->getTransactionConfig(Mage::app()->getLocale()->getCurrency()),
            $this->logger
        );
        $initialNotification = $this->transactionManager->findInitialNotification($magePayment->getOrder());

        if (! $initialNotification) {
            $magePayment->getOrder()->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, false);
            $this->handleError("Cancellation failed (unable to find initial notification transaction)");
            return;
        }

        $this->logger->info('Executing operation "cancel" on transaction ' . $initialNotification->getTxnId());

        $backendTransaction = $payment->getBackendTransaction();
        $backendTransaction->setParentTransactionId($initialNotification->getTxnId());

        if (! array_key_exists(Operation::CANCEL,
            $backendService->retrieveBackendOperations($backendTransaction)
        )) {
            $magePayment->getOrder()->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, false);
            $this->handleError("Operation (Cancel) not allowed on transaction " . $initialNotification->getTxnId(), [
                'transaction_id'     => $initialNotification->getTxnId(),
                'allowed_operations' => join(', ', $backendService->retrieveBackendOperations($backendTransaction)),
            ]);
            return;
        }

        $action = $this->backendOperationHandler->execute($backendTransaction, $backendService, Operation::CANCEL);

        if ($action instanceof SuccessAction) {
            $transactionId = $action->getContextItem('transaction_id');
            $this->logger->info("Cancelled $transactionId");
            return;
        }
        if ($action instanceof ErrorAction) {
            $magePayment->getOrder()->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, false);
            $this->handleError($action->getMessage(), ['code' => $action->getCode()]);
            return;
        }

        $magePayment->getOrder()->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, false);
        $this->handleError('Cancellation failed');
    }

    /**
     * In case an error happened during the backend operations we need to clear the message data stored within
     * the session and set the proper error; otherwise Magento would show a success and an error message simultaneously.
     *
     * @since 1.0.0
     */
    public function checkSessionMessage()
    {
        $error      = $this->getAdminSession()->getData(self::ERROR_MESSAGE_SESSION_KEY);
        $request    = \Mage::app()->getRequest();
        $module     = $request->getModuleName();
        $controller = $request->getControllerName();
        $action     = $request->getActionName();

        if ($module === 'admin' && $controller === 'sales_order' && ($action === 'index' || $action === 'view')) {
            if ($error !== '' && $this->getAdminSession()->hasData(self::ERROR_MESSAGE_SESSION_KEY)) {
                $this->getAdminSession()->unsetData();
                $this->getAdminSession()->getMessages(true);
                $this->getAdminSession()->addError($error);
            }
        }
    }

    /**
     * @param string $message
     * @param array  $context
     *
     * @throws Mage_Core_Exception
     *
     * @since 1.0.0
     */
    private function handleError($message, array $context = [])
    {
        $this->logger->error($message, $context);
        \Mage::throwException($message);
    }

    /**
     * @return Mage_Core_Model_Abstract|Mage_Adminhtml_Model_Session
     *
     * @since 1.0.0
     */
    private function getAdminSession()
    {
        return \Mage::getSingleton('adminhtml/session');
    }

    /**
     * @param array                                        $suitableTransactions
     * @param Mage_Sales_Model_Order_Payment_Transaction[] $refundableTransactions
     *
     * @param float                                        $remainingAdditionalAmount
     *
     * @return array
     *
     * @throws Mage_Core_Exception
     */
    private function findTransactionsForAdditionalAmount(
        array &$suitableTransactions,
        array $refundableTransactions,
        $remainingAdditionalAmount
    ) {
        if ($remainingAdditionalAmount <= 0) {
            return [];
        }

        foreach ($refundableTransactions as $refundableTransaction) {
            $additionalInformation = $refundableTransaction->getAdditionalInformation(
                \Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS
            );

            if (empty($additionalInformation[TransactionManager::REFUNDABLE_BASKET_KEY])) {
                continue;
            }

            $refundableBasket = json_decode(
                $additionalInformation[TransactionManager::REFUNDABLE_BASKET_KEY],
                true
            );

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
            $this->handleError('Unable to refund additional amount (remaining amount: ' . $remainingAdditionalAmount
                               . ')');
        }

        return $suitableTransactions;
    }

    /**
     * @param array                                        $suitableTransactions
     * @param Mage_Sales_Model_Order_Payment_Transaction[] $refundableTransactions
     * @param Mage_Sales_Model_Order_Creditmemo_Item[]     $items
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    private function findTransactionsForItems(array &$suitableTransactions, array $refundableTransactions, array $items)
    {
        if (! $items) {
            return [];
        }

        foreach ($items as $key => $item) {
            $remainingQuantity = $item->getQty();

            foreach ($refundableTransactions as $transaction) {
                $additionalInformation = $transaction->getAdditionalInformation(
                    \Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS
                );

                if (empty($additionalInformation[TransactionManager::REFUNDABLE_BASKET_KEY])) {
                    continue;
                }

                $refundableBasket = json_decode(
                    $additionalInformation[TransactionManager::REFUNDABLE_BASKET_KEY],
                    true
                );

                // Check if the product is in this transaction
                if (! isset($refundableBasket[$item->getProductId()])) {
                    continue;
                }

                $refundableQuantity = (int)$refundableBasket[$item->getProductId()];

                $quantity = ($remainingQuantity > $refundableQuantity)
                    ? $refundableQuantity
                    : $remainingQuantity;

                if (! array_key_exists($transaction->getId(), $suitableTransactions)) {
                    $suitableTransactions[$transaction->getId()] = [
                        'amount'      => 0,
                        'basket'      => [TransactionManager::ADDITIONAL_AMOUNT_KEY => 0],
                        'transaction' => $transaction,
                    ];
                }

                $suitableTransactions[$transaction->getId()]['amount']                        += $quantity
                                                                                                 * $item->getBasePriceInclTax();
                $suitableTransactions[$transaction->getId()]['basket'][$item->getProductId()] = $quantity;

                $remainingQuantity -= $quantity;

                if ($remainingQuantity <= 0) {
                    break;
                }
            }

            if ($remainingQuantity > 0) {
                $this->handleError('Unable to refund (not enough transactions available)');
            }
        }

        return $suitableTransactions;
    }
}
