<?php

namespace WirecardEE\PaymentGateway\Service;

use Mage_Sales_Model_Order_Payment_Transaction;
use Psr\Log\LoggerInterface;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\Transaction;

class TransactionManager
{
    /** @var LoggerInterface */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function createTransaction(
        \Mage_Sales_Model_Order_Payment_Transaction $transaction,
        \Mage_Sales_Model_Order $order,
        Response $response
    ) {
        $transaction->setAdditionalInformation(
            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
            $response->getData()
        );
        $transaction->setTxnType(self::getMageTransactionType($response->getTransactionType()));
        $transaction->setOrderPaymentObject($order->getPayment());
        $transaction->setIsClosed(false);

        if ($response instanceof SuccessResponse) {
            $transaction->setTxnId($response->getTransactionId());
        }

        $transaction->save();
    }

    /**
     * @return \Mage_Core_Helper_Abstract|\WirecardEE_PaymentGateway_Helper_Data
     */
    protected function getHelper()
    {
        return \Mage::helper('paymentgateway');
    }

    public static function getMageTransactionType($transactionType)
    {
        switch ($transactionType) {
            case Transaction::TYPE_DEBIT:
                return Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
            case Transaction::TYPE_AUTHORIZATION:
                return Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH;
        }

        throw new \RuntimeException("Unable to map transaction type ($transactionType)");
    }
}
