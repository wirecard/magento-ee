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
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\RatepayInvoicePaymentConfig;
use WirecardEE\PaymentGateway\Payments\RatepayInvoicePayment;
use WirecardEE\Tests\Test\MagentoTestCase;

class RatepayInvoicePaymentTest extends MagentoTestCase
{
    public function testPayment()
    {
        $payment     = new RatepayInvoicePayment();
        $transaction = $payment->getTransaction();
        $this->assertInstanceOf(RatepayInvoiceTransaction::class, $transaction);
        $this->assertSame($transaction, $payment->getTransaction());

        $this->assertInstanceOf(Config::class, $payment->getTransactionConfig('EUR'));
        $this->assertInstanceOf(RatepayInvoicePaymentConfig::class, $payment->getPaymentConfig());

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getRealOrderId')->willReturn('12345');

        $backendTransaction = $payment->getBackendTransaction(
            $order,
            Operation::PAY,
            $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class)
        );

        $this->assertEquals(12345, $backendTransaction->getOrderNumber());
    }

    public function testProcessPayment()
    {
        $payment = new RatepayInvoicePayment();

        $payment->getTransaction()->setAccountHolder(
            $this->createMock(AccountHolder::class)
        );

        $orderSummary       = $this->createMock(OrderSummary::class);
        $transactionService = $this->createMock(TransactionService::class);
        $redirect           = $this->createMock(Redirect::class);
        $order              = $this->createMock(\Mage_Sales_Model_Order::class);

        $orderSummary->method('getOrder')->willReturn($order);
        $order->method('getRealOrderId')->willReturn('ABC123');
        $order->method('__call')->willReturnMap([
            ['getBaseGrandTotal', [], '1000.0'],
            ['getBaseCurrencyCode', [], 'EUR'],
            ['getCustomerDob', [], '27.10.1990'],
        ]);

        $this->assertNull($payment->processPayment($orderSummary, $transactionService, $redirect));
    }

    public function testProcessPaymentWithInvalidAmount()
    {
        $payment = new RatepayInvoicePayment();

        $payment->getTransaction()->setAccountHolder(
            $this->createMock(AccountHolder::class)
        );

        $orderSummary       = $this->createMock(OrderSummary::class);
        $transactionService = $this->createMock(TransactionService::class);
        $redirect           = $this->createMock(Redirect::class);
        $order              = $this->createMock(\Mage_Sales_Model_Order::class);

        $orderSummary->method('getOrder')->willReturn($order);
        $order->method('getRealOrderId')->willReturn('ABC123');
        $order->method('__call')->willReturnMap([
            ['getBaseGrandTotal', [], '5.0'],
            ['getBaseCurrencyCode', [], 'EUR'],
        ]);

        $this->assertInstanceOf(
            ErrorAction::class,
            $payment->processPayment($orderSummary, $transactionService, $redirect)
        );
    }

    public function testProcessPaymentWithInvalidDateOfBirth()
    {
        $payment = new RatepayInvoicePayment();

        $payment->getTransaction()->setAccountHolder(
            $this->createMock(AccountHolder::class)
        );

        $orderSummary       = $this->createMock(OrderSummary::class);
        $transactionService = $this->createMock(TransactionService::class);
        $redirect           = $this->createMock(Redirect::class);
        $order              = $this->createMock(\Mage_Sales_Model_Order::class);

        $orderSummary->method('getOrder')->willReturn($order);
        $order->method('getRealOrderId')->willReturn('ABC123');
        $order->method('__call')->willReturnMap([
            ['getBaseGrandTotal', [], '1000.0'],
            ['getBaseCurrencyCode', [], 'EUR'],
            ['getCustomerDob', [], date('d.m.Y')],
        ]);

        /** @var ErrorAction $action */
        $action = $payment->processPayment($orderSummary, $transactionService, $redirect);
        $this->assertInstanceOf(ErrorAction::class, $action);
        $this->assertEquals(ErrorAction::PROCESSING_FAILED, $action->getCode());
        $this->assertContains('at least 18 years old', $action->getMessage());
    }

    public function testDisplayRestrictions()
    {
        $payment = new RatepayInvoicePayment();

        $checkoutSession = $this->createMock(\Mage_Checkout_Model_Session::class);
        $quote           = $this->createMock(\Mage_Sales_Model_Quote::class);
        $shippingAddress = $this->createMock(\Mage_Sales_Model_Quote_Address::class);
        $billingAddress  = $this->createMock(\Mage_Sales_Model_Quote_Address::class);

        $billingAddress->method('getCountry')->willReturn('AT');
        $shippingAddress->method('getCountry')->willReturn('AT');
        $shippingAddress->method('__call')->willReturnMap([
            ['getSameAsBilling', [], true]
        ]);

        $quote->method('getBillingAddress')->willReturn($billingAddress);
        $quote->method('getShippingAddress')->willReturn($shippingAddress);
        $quote->method('__call')->willReturnMap([
            ['getQuoteCurrencyCode', [], 'EUR'],
            ['getBaseGrandTotal', [], '1000.0'],
            ['getCustomerDob', [], '27.10.1990']
        ]);

        $checkoutSession->method('getQuote')->willReturn($quote);

        $this->assertTrue($payment->checkDisplayRestrictions($checkoutSession));
    }
}
