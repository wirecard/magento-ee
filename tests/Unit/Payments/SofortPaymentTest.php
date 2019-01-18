<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Payments;

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\Transaction\SofortTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\SepaCreditTransferPaymentConfig;
use WirecardEE\PaymentGateway\Payments\SofortPayment;
use WirecardEE\Tests\Test\MagentoTestCase;

class SofortPaymentTest extends MagentoTestCase
{
    public function testPayment()
    {
        $payment     = new SofortPayment();
        $transaction = $payment->getTransaction();
        $this->assertInstanceOf(SofortTransaction::class, $transaction);
        $this->assertSame($transaction, $payment->getTransaction());

        $this->assertInstanceOf(Config::class, $payment->getTransactionConfig('EUR'));
        $this->assertInstanceOf(SepaCreditTransferPaymentConfig::class, $payment->getPaymentConfig());

        $order       = $this->createMock(\Mage_Sales_Model_Order::class);
        $transaction = $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class);
        $this->assertInstanceOf(
            SepaCreditTransferTransaction::class,
            $payment->getBackendTransaction($order, Operation::CREDIT, $transaction)
        );
        $this->assertInstanceOf(
            SepaCreditTransferTransaction::class,
            $payment->getBackendTransaction($order, Operation::CANCEL, $transaction)
        );
        $this->assertInstanceOf(
            SofortTransaction::class,
            $payment->getBackendTransaction($order, Operation::REFUND, $transaction)
        );
    }

    public function testProcessPayment()
    {
        $payment     = new SofortPayment();
        $transaction = $payment->getTransaction();
        $this->assertNull($transaction->getOrderNumber());

        $orderSummary       = $this->createMock(OrderSummary::class);
        $transactionService = $this->createMock(TransactionService::class);
        $redirect           = $this->createMock(Redirect::class);
        $order              = $this->createMock(\Mage_Sales_Model_Order::class);

        $orderSummary->method('getOrder')->willReturn($order);
        $order->method('getRealOrderId')->willReturn('ABC123');

        $this->assertNull($payment->processPayment($orderSummary, $transactionService, $redirect));

        $this->assertEquals('ABC123', $transaction->getOrderNumber());
    }
}
