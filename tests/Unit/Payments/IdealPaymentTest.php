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
use Wirecard\PaymentSdk\Transaction\IdealTransaction;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\SepaCreditTransferPaymentConfig;
use WirecardEE\PaymentGateway\Payments\IdealPayment;
use WirecardEE\Tests\Test\MagentoTestCase;

class IdealPaymentTest extends MagentoTestCase
{
    public function testPayment()
    {
        $payment     = new IdealPayment();
        $transaction = $payment->getTransaction();
        $this->assertInstanceOf(IdealTransaction::class, $transaction);
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
            IdealTransaction::class,
            $payment->getBackendTransaction($order, Operation::CANCEL, $transaction)
        );
    }

    public function testProcessPayment()
    {
        $payment     = new IdealPayment();
        $transaction = $payment->getTransaction();
        $transaction->setOperation(Operation::PAY);
        $transaction->setLocale('en_US');
        $redirect = $this->createMock(Redirect::class);
        $transaction->setRedirect($redirect);

        $this->assertNull($transaction->getOrderNumber());
        $this->assertArrayNotHasKey('bank-account', $transaction->mappedProperties());

        $orderSummary       = $this->createMock(OrderSummary::class);
        $transactionService = $this->createMock(TransactionService::class);
        $redirect           = $this->createMock(Redirect::class);

        $orderSummary->method('getAdditionalPaymentData')->willReturn([
            'idealBank' => 'INGBNL2A',
        ]);

        $this->assertNull($payment->processPayment($orderSummary, $transactionService, $redirect));
        $this->assertArrayHasKey('bank-account', $transaction->mappedProperties());
        $this->assertEquals(['bic' => 'INGBNL2A'], $transaction->mappedProperties()['bank-account']);
    }
}
