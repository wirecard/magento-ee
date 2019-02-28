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
use Wirecard\PaymentSdk\Transaction\MasterpassTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardEE\PaymentGateway\Data\PaymentConfig;
use WirecardEE\PaymentGateway\Payments\MasterpassPayment;
use WirecardEE\Tests\Test\MagentoTestCase;

class MasterpassPaymentTest extends MagentoTestCase
{
    public function testPayment()
    {
        $payment     = new MasterpassPayment();
        $transaction = $payment->getTransaction();
        $this->assertInstanceOf(MasterpassTransaction::class, $transaction);
        $this->assertSame($transaction, $payment->getTransaction());

        $this->assertInstanceOf(Config::class, $payment->getTransactionConfig('EUR'));
        $this->assertInstanceOf(PaymentConfig::class, $payment->getPaymentConfig());

        $order       = $this->createMock(\Mage_Sales_Model_Order::class);
        $transaction = $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class);
        $transaction->method('getAdditionalInformation')->willReturnOnConsecutiveCalls([
            'payment-methods.0.name'            => 'masterpass',
            Transaction::PARAM_TRANSACTION_TYPE => Transaction::TYPE_DEBIT,
        ], [
            'payment-methods.0.name'            => 'masterpass',
            Transaction::PARAM_TRANSACTION_TYPE => Transaction::TYPE_AUTHORIZATION,
        ], [
            'payment-methods.0.name'            => 'masterpass',
            Transaction::PARAM_TRANSACTION_TYPE => Transaction::TYPE_CREDIT,
        ]);

        $this->assertNull($payment->getBackendTransaction($order, null, $transaction));
        $this->assertNull($payment->getBackendTransaction($order, null, $transaction));
        $this->assertInstanceOf(
            MasterpassTransaction::class,
            $payment->getBackendTransaction($order, null, $transaction)
        );
    }
}
