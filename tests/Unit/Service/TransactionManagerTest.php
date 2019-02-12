<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Service;

use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardEE\PaymentGateway\Payments\PaymentInterface;
use WirecardEE\PaymentGateway\Service\Logger;
use WirecardEE\PaymentGateway\Service\TransactionManager;
use WirecardEE\Tests\Test\MagentoTestCase;

class TransactionManagerTest extends MagentoTestCase
{
    public function testCreateTransactionWithoutTransactionId()
    {
        $order    = $this->createMock(\Mage_Sales_Model_Order::class);
        $response = $this->createMock(SuccessResponse::class);

        $manager = new TransactionManager(new Logger());
        $this->assertNull($manager->createTransaction(TransactionManager::TYPE_INITIAL, $order, $response));
    }

    public function testCreateTransactionWithExistingTransactionModel()
    {
        $order = $this->createMock(\Mage_Sales_Model_Order::class);

        $response = $this->createMock(SuccessResponse::class);
        $response->method('getTransactionId')->willReturn('transaction-id');

        $payment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);
        $order->method('getPayment')->willReturn($payment);

        $transaction = $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class);
        $transaction->method('loadByTxnId')->willReturnSelf();
        $transaction->method('getId')->willReturn(1);
        $this->replaceMageModel('sales/order_payment_transaction', $transaction);

        $manager = new TransactionManager(new Logger());
        $this->assertNull($manager->createTransaction(TransactionManager::TYPE_INITIAL, $order, $response));
    }

    public function testCreateTransactionInitial()
    {
        $order = $this->createMock(\Mage_Sales_Model_Order::class);

        $response = $this->createMock(SuccessResponse::class);
        $response->method('getTransactionId')->willReturn('transaction-id');
        $response->method('getParentTransactionId')->willReturn('parent-id');
        $response->method('getData')->willReturn([]);

        $payment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);
        $order->method('getPayment')->willReturn($payment);

        $transaction = $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class);
        $transaction->method('loadByTxnId')->willReturnSelf();
        $transaction->method('__call')->willReturnMap([['getTxnId', [], 'transaction-id']]);
        $transaction->expects($this->atLeastOnce())->method('setParentTxnId')->with('parent-id', 'transaction-id');
        $this->replaceMageModel('sales/order_payment_transaction', $transaction);

        $parentTransaction = $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class);
        $parentTransaction->method('getId')->willReturn(2);
        $parentTransaction->method('__call')->willReturnMap([['getTxnId', [], 'parent-id']]);

        $transactions = $this->createMock(\Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection::class);
        $transactions->method('getIterator')->willReturn(new \ArrayIterator([$parentTransaction]));
        $this->replaceMageResourceModel('sales/order_payment_transaction_collection', $transactions);

        $manager = new TransactionManager(new Logger());
        $this->assertSame(
            $transaction,
            $manager->createTransaction(TransactionManager::TYPE_INITIAL, $order, $response)
        );
    }

    public function testFindInitialNotification()
    {
        $order = $this->createMock(\Mage_Sales_Model_Order::class);

        $transactions = $this->createMock(\Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection::class);
        $transactions->method('count')->willReturn(0);
        $transactions->method('getIterator')->willReturn(new \ArrayIterator([]));
        $this->replaceMageResourceModel('sales/order_payment_transaction_collection', $transactions);

        $manager = new TransactionManager(new Logger());

        $this->assertNull($manager->findInitialNotification($order));

        $transaction = new \Mage_Sales_Model_Order_Payment_Transaction();
        $transaction->setAdditionalInformation(\Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, [
            TransactionManager::TYPE_KEY => TransactionManager::TYPE_NOTIFY,
        ]);
        $transaction->setTxnType(\Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT);
        $transactions = $this->createMock(\Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection::class);
        $transactions->method('count')->willReturn(1);
        $transactions->method('getIterator')->willReturn(new \ArrayIterator([$transaction]));
        $this->replaceMageResourceModel('sales/order_payment_transaction_collection', $transactions);

        $this->assertNull($manager->findInitialNotification($order));

        $transaction = new \Mage_Sales_Model_Order_Payment_Transaction();
        $transaction->setAdditionalInformation(\Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, [
            TransactionManager::TYPE_KEY => TransactionManager::TYPE_NOTIFY,
        ]);
        $transaction->setTxnType(\Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
        $transactions = $this->createMock(\Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection::class);
        $transactions->method('count')->willReturn(1);
        $transactions->method('getIterator')->willReturn(new \ArrayIterator([$transaction]));
        $this->replaceMageResourceModel('sales/order_payment_transaction_collection', $transactions);

        $this->assertSame($transaction, $manager->findInitialNotification($order));
    }

    public function testRefundableTransactions()
    {
        $order = $this->createMock(\Mage_Sales_Model_Order::class);

        $transactions = $this->createMock(\Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection::class);
        $transactions->method('count')->willReturn(0);
        $transactions->method('getIterator')->willReturn(new \ArrayIterator([]));
        $this->replaceMageResourceModel('sales/order_payment_transaction_collection', $transactions);

        $manager = new TransactionManager(new Logger());

        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getRefundOperation')->willReturn(Operation::REFUND);
        $payment->method('getBackendTransaction')->willReturn($this->createMock(Transaction::class));

        $backendService = $this->createMock(BackendService::class);
        $backendService->method('retrieveBackendOperations')->willReturn([Operation::REFUND => 'Refund']);

        $this->assertEquals([], $manager->findRefundableTransactions($order, $payment, $backendService));

        $transaction = new \Mage_Sales_Model_Order_Payment_Transaction();
        $transaction->setAdditionalInformation(\Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, [
            TransactionManager::TYPE_KEY => TransactionManager::TYPE_NOTIFY,
        ]);
        $transaction->setTxnType(\Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT);
        $transactions = $this->createMock(\Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection::class);
        $transactions->method('count')->willReturn(1);
        $transactions->method('getIterator')->willReturn(new \ArrayIterator([$transaction]));
        $this->replaceMageResourceModel('sales/order_payment_transaction_collection', $transactions);

        $this->assertEquals([], $manager->findRefundableTransactions($order, $payment, $backendService));

        $transaction = new \Mage_Sales_Model_Order_Payment_Transaction();
        $transaction->setAdditionalInformation(\Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, [
            TransactionManager::TYPE_KEY              => TransactionManager::TYPE_NOTIFY,
            TransactionManager::REFUNDABLE_BASKET_KEY => json_encode(['item']),
        ]);
        $transaction->setTxnType(\Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
        $transactions = $this->createMock(\Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection::class);
        $transactions->method('count')->willReturn(1);
        $transactions->method('getIterator')->willReturn(new \ArrayIterator([$transaction]));
        $this->replaceMageResourceModel('sales/order_payment_transaction_collection', $transactions);

        $this->assertSame([$transaction], $manager->findRefundableTransactions($order, $payment, $backendService));
    }

    public function testGetAdditionalInformationFromTransaction()
    {
        $transaction = new \Mage_Sales_Model_Order_Payment_Transaction();

        $this->assertNull(TransactionManager::getAdditionalInformationFromTransaction($transaction));

        $data = ['foo' => 'bar'];
        $transaction->setAdditionalInformation(\Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $data);

        $this->assertSame($data, TransactionManager::getAdditionalInformationFromTransaction($transaction));
    }

    public function testGetRefundableBasketFromTransaction()
    {
        $transaction = new \Mage_Sales_Model_Order_Payment_Transaction();

        $this->assertNull(TransactionManager::getRefundableBasketFromTransaction($transaction));

        $data = ['item' => 1];
        $transaction->setAdditionalInformation(\Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, [
            TransactionManager::REFUNDABLE_BASKET_KEY => json_encode($data)
        ]);

        $refundableBasket = TransactionManager::getRefundableBasketFromTransaction($transaction);

        $this->assertSame($data, $refundableBasket);
    }

    public function testFindInitialResponse()
    {
        $manager = new TransactionManager(new Logger());

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $transactions = $this->createMock(\Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection::class);
        $transactions->method('count')->willReturn(0);
        $transactions->method('getIterator')->willReturn(new \ArrayIterator([]));
        $this->replaceMageResourceModel('sales/order_payment_transaction_collection', $transactions);

        $this->assertNull($manager->findInitialResponse($order));

        $payment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);
        $order->method('getPayment')->willReturn($payment);

        $transaction = new \Mage_Sales_Model_Order_Payment_Transaction();
        $transaction->setAdditionalInformation(\Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, [
            TransactionManager::TYPE_KEY => TransactionManager::TYPE_INITIAL,
        ]);
        $transaction->setTxnType(\Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT);
        $transactions = $this->createMock(\Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection::class);
        $transactions->method('count')->willReturn(1);
        $transactions->method('getIterator')->willReturn(new \ArrayIterator([$transaction]));
        $this->replaceMageResourceModel('sales/order_payment_transaction_collection', $transactions);

        $response = $manager->findInitialResponse($order);

        $this->assertNotNull($response);
        $this->assertSame([
            TransactionManager::TYPE_KEY => TransactionManager::TYPE_INITIAL,
        ], $response);
    }
}
