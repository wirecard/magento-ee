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
use Wirecard\PaymentSdk\Transaction\GiropayTransaction;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\PaymentConfig;
use WirecardEE\PaymentGateway\Payments\GiropayPayment;
use WirecardEE\Tests\Test\MagentoTestCase;

class GiropayPaymentTest extends MagentoTestCase
{
    public function testPayment()
    {
        $payment     = new GiropayPayment();
        $transaction = $payment->getTransaction();
        $this->assertInstanceOf(GiropayTransaction::class, $transaction);
        $this->assertSame($transaction, $payment->getTransaction());

        $this->assertInstanceOf(Config::class, $payment->getTransactionConfig('EUR'));
        $this->assertInstanceOf(PaymentConfig::class, $payment->getPaymentConfig());

        $order       = $this->createMock(\Mage_Sales_Model_Order::class);
        $transaction = $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class);
        $this->assertInstanceOf(
            SepaCreditTransferTransaction::class,
            $payment->getBackendTransaction($order, Operation::CREDIT, $transaction)
        );
        $this->assertInstanceOf(
            GiropayTransaction::class,
            $payment->getBackendTransaction($order, null, $transaction)
        );
    }

    public function testProcessPayment()
    {
        $payment     = new GiropayPayment();
        $transaction = $payment->getTransaction();
        $transaction->setOperation(Operation::PAY);
        $transaction->setLocale('en_US');

        $this->assertNull($transaction->getOrderNumber());
        $this->assertArrayNotHasKey('bank-account', $transaction->mappedProperties());

        $orderSummary       = $this->createMock(OrderSummary::class);
        $transactionService = $this->createMock(TransactionService::class);
        $redirect           = $this->createMock(Redirect::class);

        $orderSummary->method('getAdditionalPaymentData')->willReturn([
            'giropayBic' => 'BIC123'
        ]);

        $this->assertNull($payment->processPayment($orderSummary, $transactionService, $redirect));
        $this->assertArrayHasKey('bank-account', $transaction->mappedProperties());
    }
}
