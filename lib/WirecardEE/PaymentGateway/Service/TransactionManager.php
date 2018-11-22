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
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * @since 1.0.0
 */
class TransactionManager
{
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
     * @param \Mage_Sales_Model_Order $order
     * @param Response                $response
     *
     * @throws \Mage_Core_Exception
     *
     * @since 1.0.0
     */
    public function createTransaction(
        \Mage_Sales_Model_Order $order,
        Response $response
    ) {
        $transaction = $this->getOrderPaymentTransaction();

        $transaction->setAdditionalInformation(
            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
            $response->getData()
        );
        $transaction->setTxnType(self::getMageTransactionType($response->getTransactionType()));
        $transaction->setOrderPaymentObject($order->getPayment());
        $transaction->setIsClosed(false);

        if ($response instanceof SuccessResponse || $response instanceof InteractionResponse) {
            $transactionId = $response->getTransactionId() . '.' . time();
            $transaction->setTxnId($transactionId);
        }

        $transaction->save();
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
    protected function getOrderPaymentTransaction()
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
