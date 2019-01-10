<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Service;

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\PaymentConfig;
use WirecardEE\PaymentGateway\Payments\PaypalPayment;
use WirecardEE\Tests\Test\MagentoTestCase;

class PaypalPaymentTest extends MagentoTestCase
{
    public function testPayment()
    {
        $payment     = new PaypalPayment();
        $transaction = $payment->getTransaction();
        $this->assertInstanceOf(PayPalTransaction::class, $transaction);
        $this->assertSame($transaction, $payment->getTransaction());

        $this->assertInstanceOf(Config::class, $payment->getTransactionConfig('EUR'));
        $this->assertInstanceOf(PaymentConfig::class, $payment->getPaymentConfig());

        $order       = $this->createMock(\Mage_Sales_Model_Order::class);
        $transaction = $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class);
        $this->assertInstanceOf(
            PayPalTransaction::class,
            $payment->getBackendTransaction($order, Operation::CREDIT, $transaction)
        );
        $this->assertInstanceOf(
            PayPalTransaction::class,
            $payment->getBackendTransaction($order, Operation::CANCEL, $transaction)
        );
    }

    public function testProcessPayment()
    {
        $payment     = new PaypalPayment();
        $transaction = $payment->getTransaction();
        $transaction->setOperation(Operation::PAY);
        $transaction->setLocale('en_US');
        $this->assertArrayNotHasKey('order-detail', $transaction->mappedProperties());

        $orderSummary       = $this->createMock(OrderSummary::class);
        $transactionService = $this->createMock(TransactionService::class);
        $redirect           = $this->createMock(Redirect::class);
        $order              = $this->createMock(\Mage_Sales_Model_Order::class);

        $orderSummary->method('getOrder')->willReturn($order);
        $order->method('getRealOrderId')->willReturn('ABC123');
        $order->method('__call')->willReturnMap([
            ['getBaseGrandTotal', [], '10.0'],
            ['getBaseCurrencyCode', [], 'EUR'],
        ]);

        $this->assertNull($payment->processPayment($orderSummary, $transactionService, $redirect));

        $this->assertArrayHasKey('order-detail', $transaction->mappedProperties());
    }
}
