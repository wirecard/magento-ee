<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Service;

use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Entity\Status;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Actions\SuccessAction;

/**
 * Responsible for handling backend operations.
 *
 * @since   1.0.0
 */
class BackendOperationsHandler extends Handler
{
    /**
     * @param Transaction    $transaction
     * @param BackendService $transactionService
     * @param string         $operation
     *
     * @return ErrorAction|SuccessAction
     *
     * @since 1.0.0
     */
    public function execute(
        Transaction $transaction,
        BackendService $transactionService,
        $operation
    ) {
        try {
            $response = $transactionService->process($transaction, $operation);

            if ($response instanceof SuccessResponse) {
                /** @var \Mage_Sales_Model_Order $order */
                $order = \Mage::getModel('sales/order')->load($response->getCustomFields()->get('order-id'));
                $this->transactionManager->createTransaction(TransactionManager::TYPE_BACKEND, $order, $response);

                return new SuccessAction([
                    'operation'      => $operation,
                    'transaction_id' => $response->getTransactionId(),
                    'amount'         => $response->getRequestedAmount()
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Transaction service process failed: ' . $e->getMessage());
            return new ErrorAction(ErrorAction::PROCESSING_FAILED, 'Transaction processing failed');
        }

        $message = 'BackendOperationFailedUnknownResponse';
        if ($response instanceof FailureResponse) {
            $errors = [];
            foreach ($response->getStatusCollection() as $status) {
                /** @var Status $status */
                $errors[] = $status->getDescription();
            }
            $message = join("\n", $errors);
        }

        $this->logger->error('Backend operation failed', $response->getData());
        return new ErrorAction(ErrorAction::BACKEND_OPERATION_FAILED, $message);
    }
}
