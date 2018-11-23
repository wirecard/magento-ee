<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Service;

use Mage_Sales_Model_Order_Payment_Transaction;
use Psr\Log\LoggerInterface;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardEE\PaymentGateway\Data\ResponseMapper;

/**
 * @since 1.0.0
 */
class TransactionManager
{
    const TYPE_INITIAL = 'initial';
    const TYPE_NOTIFY = 'notify';
    const TYPE_BACKEND = 'backend';

    // Key for type in additional information in transactions
    const TYPE_KEY = 'source_type';

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     *
     * @since 1.0.0
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * This method creates transactions following a simple rule: initial and backend transactions are always saved
     * and overwritten by notifications.
     *
     * @param \Mage_Sales_Model_Order $order
     * @param Response                $response
     * @param string                  $type
     *
     * @return void
     * @throws \Mage_Core_Exception
     *
     * @since 1.0.0
     */
    public function createTransaction(
        $type,
        \Mage_Sales_Model_Order $order,
        Response $response
    ) {
        $mageTransactionModel = $this->getOrderPaymentTransactionModel();
        $responseMapper       = new ResponseMapper($response);
        $transactionId        = $responseMapper->getTransactionId();
        $parentTransactionId  = $responseMapper->getParentTransactionId();

        if (! $transactionId) {
            // We're not handling responses without transaction ids at all.
            return null;
        }

        $mageTransactionModel->setOrderPaymentObject($order->getPayment());

        switch ($type) {
            case self::TYPE_INITIAL:
            case self::TYPE_BACKEND:
                // Initial transactions are always saved as new transactions.
                if ($mageTransactionModel->loadByTxnId($transactionId)->getId()) {
                    // In some rare cases a notification might has already been arrived before the initial transaction.
                    // Since we don't want initial transactions to overwrite notifications we're going to leave here.
                    return;
                }

                if ($parentTransactionId) {
                    $parentTransactionMageModel = $mageTransactionModel->loadByTxnId($parentTransactionId);
                    if ($parentTransactionMageModel->getId()) {
                        $mageTransactionModel->setParentId($parentTransactionMageModel->getId());
                    }
                }

                $mageTransactionModel->setTxnType(self::getMageTransactionType($response->getTransactionType()));
                $mageTransactionModel->setAdditionalInformation(
                    Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                    array_merge($response->getData(), [
                        self::TYPE_KEY => $type,
                    ])
                );
                $mageTransactionModel->setTxnId($transactionId);
                $mageTransactionModel->setOrderPaymentObject($order->getPayment());
                try {
                    $mageTransactionModel->save();
                } catch (\Exception $e) {
                    // Catching possible race conditions: in case the notification has arrived by now we're not going
                    // to overwrite it by an initial transaction.
                    $this->logger->info("Caught possible race condition by not saving transaction ($transactionId)");
                }

                $this->logger->info("Created transaction (type: $type) for transaction $transactionId");

                return;

            case self::TYPE_NOTIFY:
                // Since notifies are the source of truth for transactions they're overwriting the initial transaction.
                $mageTransactionModel->loadByTxnId($transactionId);

                // Be sure not to overwrite notifications!
                if ($mageTransactionModel->getId()) {
                    $additionalInformation = $mageTransactionModel->getAdditionalInformation(
                        Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS
                    );
                    if (! empty($additionalInformation[self::TYPE_KEY])
                        && $additionalInformation[self::TYPE_KEY] === self::TYPE_NOTIFY) {
                        return;
                    }
                }

                if ($parentTransactionId) {
                    $parentTransactionMageModel = $mageTransactionModel->loadByTxnId($parentTransactionId);
                    if ($parentTransactionMageModel->getId()) {
                        $mageTransactionModel->setParentId($parentTransactionMageModel->getId());
                    }
                }

                $mageTransactionModel->setTxnId($transactionId);
                $mageTransactionModel->setTxnType(self::getMageTransactionType($response->getTransactionType()));
                $mageTransactionModel->setAdditionalInformation(
                    Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                    array_merge($response->getData(), [
                        self::TYPE_KEY => $type,
                    ])
                );
                try {
                    $mageTransactionModel->save();
                } catch (\Exception $e) {
                    // Being unable to save at this point is very likely due to a transaction id collision, which means
                    // a initial transaction with this id has been saved during the execution of this method. Let's
                    // try again to find this transaction and overwrite it.
                    if ($mageTransactionModel->loadByTxnId($transactionId)->getId()) {
                        $mageTransactionModel->setTxnId($transactionId);
                        $mageTransactionModel->setTxnType(self::getMageTransactionType($response->getTransactionType()));
                        $mageTransactionModel->setAdditionalInformation(
                            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                            array_merge($response->getData(), [
                                self::TYPE_KEY => $type,
                            ])
                        );

                        $mageTransactionModel->save();
                    }

                    $this->logger->info("Unable to save transaction ($transactionId)");
                }

                $this->logger->info("Replaced transaction (" . $mageTransactionModel->getTxnId() . ") from notify");

                return;
        }

        throw new \RuntimeException("Unknown transaction type");
    }

    /**
     * @param $transactionType
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function getMageTransactionType($transactionType)
    {
        switch ($transactionType) {
            case Transaction::TYPE_DEBIT:
                return Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
            case Transaction::TYPE_AUTHORIZATION:
                return Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH;
        }

        return Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT;
    }

    /**
     * @return Mage_Sales_Model_Order_Payment_Transaction|\Mage_Core_Model_Abstract
     *
     * @since 1.0.0
     */
    protected function getOrderPaymentTransactionModel()
    {
        return \Mage::getModel('sales/order_payment_transaction');
    }

    /**
     * @return \Mage_Core_Helper_Abstract|\WirecardEE_PaymentGateway_Helper_Data
     *
     * @since 1.0.0
     */
    protected function getHelper()
    {
        return \Mage::helper('paymentgateway');
    }
}
