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

        $payment             = $this->paymentFactory->createFromMagePayment($invoice->getOrder()->getPayment());
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

        $action = $this->backendOperationHandler->execute($backendTransaction, $backendService, Operation::PAY);

        if ($action instanceof SuccessAction) {
            $transactionId = $action->getContextItem('transaction_id');
            $amount        = $action->getContextItem('amount');

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
    public function cancel(Varien_Event_Observer $observer)
    {
        $magePayment = $observer->getData('payment');

        if (! ($magePayment instanceof Mage_Sales_Model_Order_Payment) || $magePayment->getOrder()->canCancel()) {
            \Mage::throwException("Unable to process backend operation (cancel)");
        }

        $payment             = $this->paymentFactory->createFromMagePayment($magePayment);
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
}
