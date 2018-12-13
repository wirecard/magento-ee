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
    public function cancel(Varien_Event_Observer $observer)
    {
        $magePayment = $observer->getData('payment');

        if (! ($magePayment instanceof Mage_Sales_Model_Order_Payment)) {
            \Mage::throwException("Unable to process backend operation");
        }

        $payment        = $this->paymentFactory->createFromMagePayment($magePayment);
        $backendService = new BackendService(
            $payment->getTransactionConfig(Mage::app()->getLocale()->getCurrency()),
            $this->logger
        );

        if ($initialNotification = $this->transactionManager->findInitialNotification($magePayment->getOrder())) {
            $this->logger->info('Executing operation "cancel" on transaction ' . $initialNotification->getTxnId());

            $backendTransaction = $payment->getBackendTransaction();
            $backendTransaction->setParentTransactionId($initialNotification->getTxnId());

            if (! array_key_exists(Operation::CANCEL,
                $backendService->retrieveBackendOperations($backendTransaction)
            )) {
                $magePayment->getOrder()->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, false);
                $this->setError("Operation (Cancel) not allowed on transaction " . $initialNotification->getTxnId());
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
                $this->setError($action->getMessage());
                return;
            }
        }

        $magePayment->getOrder()->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, false);
        $this->setError('Cancellation failed');
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
     *
     * @since 1.0.0
     */
    private function setError($message)
    {
        $this->logger->error($message);
        $this->getAdminSession()->setData(self::ERROR_MESSAGE_SESSION_KEY, $message);
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
