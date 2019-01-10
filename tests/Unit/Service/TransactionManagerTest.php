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
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardEE\PaymentGateway\Payments\PaymentInterface;
use WirecardEE\PaymentGateway\Service\Logger;
use WirecardEE\PaymentGateway\Service\TransactionManager;
use WirecardEE\Tests\Test\MagentoTestCase;

class TransactionManagerTest extends MagentoTestCase
{
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
            TransactionManager::REFUNDABLE_BASKET_KEY => ['item'],
        ]);
        $transaction->setTxnType(\Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
        $transactions = $this->createMock(\Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection::class);
        $transactions->method('count')->willReturn(1);
        $transactions->method('getIterator')->willReturn(new \ArrayIterator([$transaction]));
        $this->replaceMageResourceModel('sales/order_payment_transaction_collection', $transactions);

        $this->assertSame([$transaction], $manager->findRefundableTransactions($order, $payment, $backendService));
    }
}
