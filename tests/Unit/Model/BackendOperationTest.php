<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Model;

use Mage_Sales_Model_Order_Creditmemo;
use PHPUnit_Framework_MockObject_MockObject;
use Varien_Event_Observer;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Actions\SuccessAction;
use WirecardEE\PaymentGateway\Payments\PaymentInterface;
use WirecardEE\PaymentGateway\Service\BackendOperationsHandler;
use WirecardEE\PaymentGateway\Service\PaymentFactory;
use WirecardEE\PaymentGateway\Service\TransactionManager;
use WirecardEE\Tests\Test\MagentoTestCase;

class BackendOperationTest extends MagentoTestCase
{
    public function testCaptureException()
    {
        $observer = $this->createMock(Varien_Event_Observer::class);
        $backend  = new \WirecardEE_PaymentGateway_Model_BackendOperation();
        $this->expectException(\Mage_Core_Exception::class);
        $backend->capture($observer);
    }

    public function testCaptureNull()
    {
        $payment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getPayment')->willReturn($payment);

        $invoice = $this->createMock(\Mage_Sales_Model_Order_Invoice::class);
        $invoice->method('getOrder')->willReturn($order);

        $observer = $this->createMock(Varien_Event_Observer::class);
        $observer->method('getData')->willReturn($invoice);

        $backend = new \WirecardEE_PaymentGateway_Model_BackendOperation();
        $this->assertNull($backend->capture($observer));
    }

    public function testDisabledCapture()
    {
        $request = $this->createMock(\Mage_Core_Controller_Request_Http::class);
        $request->method('getPost')->willReturn([
            'items' => ['item']
        ]);
        \Mage::app()->setRequest($request);

        $payment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getPayment')->willReturn($payment);

        $invoice = $this->createMock(\Mage_Sales_Model_Order_Invoice::class);
        $invoice->method('getOrder')->willReturn($order);

        $observer = $this->createMock(Varien_Event_Observer::class);
        $observer->method('getData')->willReturn($invoice);

        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getCaptureOperation')->willReturn(null);

        $factory = $this->createMock(PaymentFactory::class);
        $factory->method('createFromMagePayment')->willReturn($payment);
        $factory->method('isSupportedPayment')->willReturn(true);

        $backend = new \WirecardEE_PaymentGateway_Model_BackendOperation();
        $backend->setPaymentFactory($factory);

        $this->assertNull($backend->capture($observer));
    }

    public function testCapture()
    {
        $request = $this->createMock(\Mage_Core_Controller_Request_Http::class);
        $request->method('getPost')->willReturn([
            'items' => ['item']
        ]);
        \Mage::app()->setRequest($request);

        $payment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);
        $payment->method('getData')->willReturn('wirecardee_paymentgateway_creditcard');

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getPayment')->willReturn($payment);

        $transaction = new \Mage_Sales_Model_Order_Payment_Transaction();
        $transaction->setAdditionalInformation(\Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, [
            TransactionManager::TYPE_KEY => TransactionManager::TYPE_NOTIFY,
        ]);
        $transaction->setTxnType(\Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
        $transaction->setOrder($order);
        $transactions = $this->createMock(\Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection::class);
        $transactions->method('count')->willReturn(1);
        $transactions->method('getIterator')->willReturn(new \ArrayIterator([$transaction]));
        $this->replaceMageResourceModel('sales/order_payment_transaction_collection', $transactions);

        $invoice = $this->createMock(\Mage_Sales_Model_Order_Invoice::class);
        $invoice->method('getOrder')->willReturn($order);
        $invoice->method('getAllItems')->willReturn([]);
        $invoice->method('__call')->willReturnMap([
            ['getGrandTotal', [], 25],
            ['getBaseCurrencyCode', [], 'EUR']
        ]);

        $observer = $this->createMock(Varien_Event_Observer::class);
        $observer->method('getData')->willReturn($invoice);

        $handler = $this->createMock(BackendOperationsHandler::class);
        $success = new SuccessAction();
        $handler->method('execute')->willReturn($success);

        $backend = new \WirecardEE_PaymentGateway_Model_BackendOperation();
        $backend->setBackendOperationHandler($handler);
        $this->assertSame($success, $backend->capture($observer));

        $handler = $this->createMock(BackendOperationsHandler::class);
        $handler->method('execute')->willReturn(new ErrorAction(0, 'testerror'));

        $backend->setBackendOperationHandler($handler);
        $this->expectException(\Mage_Core_Exception::class);
        $backend->capture($observer);
    }

    public function testRefundException()
    {
        $observer = $this->createMock(Varien_Event_Observer::class);
        $backend  = new \WirecardEE_PaymentGateway_Model_BackendOperation();
        $this->expectException(\Mage_Core_Exception::class);
        $backend->refund($observer);
    }

    public function testRefundEmpty()
    {
        $payment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getPayment')->willReturn($payment);

        $creditMemo = $this->createMock(Mage_Sales_Model_Order_Creditmemo::class);
        $creditMemo->method('getOrder')->willReturn($order);

        $observer = $this->createMock(Varien_Event_Observer::class);
        $observer->method('getData')->willReturn($creditMemo);

        $backend = new \WirecardEE_PaymentGateway_Model_BackendOperation();
        $this->assertEquals([], $backend->refund($observer));
    }

    public function testRefund()
    {
        $payment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);
        $payment->method('getData')->willReturn('wirecardee_paymentgateway_creditcard');

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getPayment')->willReturn($payment);

        $transaction = $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class);
        $transaction->method('getAdditionalInformation')->willReturn([
            TransactionManager::TYPE_KEY              => TransactionManager::TYPE_NOTIFY,
            TransactionManager::REFUNDABLE_BASKET_KEY => json_encode([TransactionManager::ADDITIONAL_AMOUNT_KEY => 6]),
        ]);
        $transaction->method('getOrder')->willReturn($order);

        $transactionManager = $this->createMock(TransactionManager::class);
        $transactionManager->method('findRefundableTransactions')->willReturn([$transaction]);

        $creditMemo = $this->createMock(Mage_Sales_Model_Order_Creditmemo::class);
        $creditMemo->method('getOrder')->willReturn($order);
        $creditMemo->method('getAllItems')->willReturn([]);
        $creditMemo->method('__call')->willReturnMap([
            ['getShippingAmount', [], 5],
            ['getAdjustmentPositive', [], 2],
            ['getAdjustmentNegative', [], 1],
            ['getBaseCurrencyCode', [], 'EUR']
        ]);

        $observer = $this->createMock(Varien_Event_Observer::class);
        $observer->method('getData')->willReturn($creditMemo);

        $handler = $this->createMock(BackendOperationsHandler::class);
        $success = new SuccessAction();
        $handler->method('execute')->willReturn($success);

        $backend = new \WirecardEE_PaymentGateway_Model_BackendOperation();
        $backend->setBackendOperationHandler($handler);
        $backend->setTransactionManager($transactionManager);
        $this->assertSame([$success], $backend->refund($observer));

        $handler = $this->createMock(BackendOperationsHandler::class);
        $handler->method('execute')->willReturn(new ErrorAction(0, 'testerror'));

        $backend->setBackendOperationHandler($handler);
        $backend->setTransactionManager($transactionManager);
        $this->expectException(\Mage_Core_Exception::class);
        $backend->refund($observer);
    }

    public function testUnableToRefund()
    {
        $payment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);
        $payment->method('getData')->willReturn('wirecardee_paymentgateway_creditcard');

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getPayment')->willReturn($payment);

        /** @var TransactionManager|PHPUnit_Framework_MockObject_MockObject $transactionManager */
        $transactionManager = $this->createMock(TransactionManager::class);
        $transactionManager->method('findRefundableTransactions')->willReturn([]);

        $creditMemo = $this->createMock(Mage_Sales_Model_Order_Creditmemo::class);
        $creditMemo->method('getOrder')->willReturn($order);
        $creditMemo->method('getAllItems')->willReturn([]);
        $creditMemo->method('__call')->willReturnMap([
            ['getBaseCurrencyCode', [], 'EUR'],
            ['getShippingAmount', [], 0],
            ['getAdjustmentPositive', [], 0],
            ['getAdjustmentNegative', [], 0],
        ]);

        /** @var Varien_Event_Observer|PHPUnit_Framework_MockObject_MockObject $observer */
        $observer = $this->createMock(Varien_Event_Observer::class);
        $observer->method('getData')->willReturn($creditMemo);

        $backend = new \WirecardEE_PaymentGateway_Model_BackendOperation();
        $backend->setTransactionManager($transactionManager);
        $this->expectException(\Mage_Core_Exception::class);
        $backend->refund($observer);
    }

    public function testDisabledRefund()
    {
        $magePayment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);
        $magePayment->method('getData')->willReturn('wirecardee_paymentgateway_creditcard');

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getPayment')->willReturn($magePayment);

        $creditMemo = $this->createMock(Mage_Sales_Model_Order_Creditmemo::class);
        $creditMemo->method('getOrder')->willReturn($order);
        $creditMemo->method('getAllItems')->willReturn([]);
        $creditMemo->method('__call')->willReturnMap([
            ['getShippingAmount', [], 0],
            ['getAdjustmentPositive', [], 0],
            ['getAdjustmentNegative', [], 0],
        ]);

        $observer = $this->createMock(Varien_Event_Observer::class);
        $observer->method('getData')->willReturn($creditMemo);

        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getRefundOperation')->willReturn(null);

        $factory = $this->createMock(PaymentFactory::class);
        $factory->method('createFromMagePayment')->willReturn($payment);
        $factory->method('isSupportedPayment')->willReturn(true);

        $backend = new \WirecardEE_PaymentGateway_Model_BackendOperation();
        $backend->setPaymentFactory($factory);

        $this->assertNull($backend->refund($observer));
    }

    public function testCancelException()
    {
        $observer = $this->createMock(Varien_Event_Observer::class);
        $backend  = new \WirecardEE_PaymentGateway_Model_BackendOperation();
        $this->expectException(\Mage_Core_Exception::class);
        $backend->cancel($observer);
    }

    public function testCancelNull()
    {
        $payment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);
        $order   = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getPayment')->willReturn($payment);
        $payment->method('getOrder')->willReturn($order);

        $observer = $this->createMock(Varien_Event_Observer::class);
        $observer->method('getData')->willReturn($payment);

        $backend = new \WirecardEE_PaymentGateway_Model_BackendOperation();
        $this->assertNull($backend->cancel($observer));
    }

    public function testCancel()
    {
        $payment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);
        $payment->method('getData')->willReturnMap([['method', null, 'wirecardee_paymentgateway_creditcard']]);

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getPayment')->willReturn($payment);
        $order->method('__call')->willReturnMap([
            ['getBaseCurrencyCode', [], 'EUR'],
            ['getGrandTotal', [], 0]
        ]);
        $order->method('getAllVisibleItems')->willReturn([]);
        $payment->method('getOrder')->willReturn($order);

        $observer = $this->createMock(Varien_Event_Observer::class);
        $observer->method('getData')->willReturn($payment);

        $transaction = $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class);
        $transaction->method('getOrder')->willReturn($order);

        $transactionManager = $this->createMock(TransactionManager::class);
        $transactionManager->method('findInitialNotification')->willReturn($transaction);

        $handler = $this->createMock(BackendOperationsHandler::class);
        $success = new SuccessAction();
        $handler->method('execute')->willReturn($success);

        $backend = new \WirecardEE_PaymentGateway_Model_BackendOperation();
        $backend->setTransactionManager($transactionManager);
        $backend->setBackendOperationHandler($handler);
        $this->assertSame($success, $backend->cancel($observer));

        $handler = $this->createMock(BackendOperationsHandler::class);
        $handler->method('execute')->willReturn(new ErrorAction(0, 'testerror'));

        $backend = new \WirecardEE_PaymentGateway_Model_BackendOperation();
        $backend->setTransactionManager($transactionManager);
        $backend->setBackendOperationHandler($handler);
        $this->assertNull($backend->cancel($observer));
    }

    public function testDisabledCancel()
    {
        $magePayment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);
        $magePayment->method('getData')->willReturnMap([['method', null, 'wirecardee_paymentgateway_eps']]);

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getPayment')->willReturn($magePayment);
        $magePayment->method('getOrder')->willReturn($order);

        $observer = $this->createMock(Varien_Event_Observer::class);
        $observer->method('getData')->willReturn($magePayment);

        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getCancelOperation')->willReturn(null);

        $factory = $this->createMock(PaymentFactory::class);
        $factory->method('createFromMagePayment')->willReturn($payment);
        $factory->method('isSupportedPayment')->willReturn(true);

        $backend = new \WirecardEE_PaymentGateway_Model_BackendOperation();
        $backend->setPaymentFactory($factory);

        $this->assertNull($backend->cancel($observer));
    }
}
