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
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\PoiPiaTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\PaymentConfig;
use WirecardEE\PaymentGateway\Payments\Contracts\AdditionalCheckoutSuccessTemplateInterface;
use WirecardEE\PaymentGateway\Payments\PiaPayment;
use WirecardEE\Tests\Test\MagentoTestCase;

class PiaPaymentTest extends MagentoTestCase
{
    public function testPayment()
    {
        $payment     = new PiaPayment();
        $this->assertInstanceOf(AdditionalCheckoutSuccessTemplateInterface::class, $payment);
        $transaction = $payment->getTransaction();
        $this->assertInstanceOf(PoiPiaTransaction::class, $transaction);
        $this->assertSame($transaction, $payment->getTransaction());

        $this->assertInstanceOf(Config::class, $payment->getTransactionConfig('EUR'));
        $this->assertInstanceOf(PaymentConfig::class, $payment->getPaymentConfig());

        $order       = $this->createMock(\Mage_Sales_Model_Order::class);
        $transaction = $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class);
        $this->assertInstanceOf(
            PoiPiaTransaction::class,
            $payment->getBackendTransaction($order, null, $transaction)
        );
    }

    public function testProcessPayment()
    {
        $orderSummary       = $this->createMock(OrderSummary::class);
        $transactionService = $this->createMock(TransactionService::class);
        $redirect           = $this->createMock(Redirect::class);

        $payment = new PiaPayment();

        $this->assertNull($payment->processPayment($orderSummary, $transactionService, $redirect));
    }

    public function testProcessReturn()
    {
        $payment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);
        $payment->expects($this->once())->method('setAdditionalInformation');

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getPayment')->willReturn($payment);
        $order->expects($this->once())->method('addStatusHistoryComment');
        $order->expects($this->once())->method('save');

        $checkoutSession = $this->createMock(\Mage_Checkout_Model_Session::class);
        $checkoutSession->method('getLastRealOrder')->willReturn($order);
        $this->replaceMageSingleton('checkout/session', $checkoutSession);

        $response           = $this->createMock(SuccessResponse::class);
        $transactionService = $this->createMock(TransactionService::class);

        $transactionService->method('handleResponse')->willReturn($response);

        $request = $this->createMock(\Mage_Core_Controller_Request_Http::class);
        $request->method('getParams')->willReturn([]);

        $payment = new PiaPayment();

        $return = $payment->processReturn($transactionService, $request);
        $this->assertInstanceOf(SuccessResponse::class, $return);
    }
}
