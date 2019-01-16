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
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction;
use WirecardEE\PaymentGateway\Data\SepaPaymentConfig;
use WirecardEE\PaymentGateway\Payments\SepaPayment;
use WirecardEE\Tests\Test\MagentoTestCase;

class SepaPaymentTest extends MagentoTestCase
{
    public function testPayment()
    {
        $payment     = new SepaPayment();
        $transaction = $payment->getTransaction();
        $this->assertInstanceOf(SepaDirectDebitTransaction::class, $transaction);
        $this->assertSame($transaction, $payment->getTransaction());

        $this->assertInstanceOf(Config::class, $payment->getTransactionConfig('EUR'));
        $this->assertInstanceOf(SepaPaymentConfig::class, $payment->getPaymentConfig());

        $order       = $this->createMock(\Mage_Sales_Model_Order::class);
        $transaction = $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class);
        $this->assertInstanceOf(
            SepaCreditTransferTransaction::class,
            $payment->getBackendTransaction($order, Operation::CREDIT, $transaction)
        );
        $this->assertInstanceOf(
            SepaDirectDebitTransaction::class,
            $payment->getBackendTransaction($order, Operation::CANCEL, $transaction)
        );
        $this->assertInstanceOf(
            SepaDirectDebitTransaction::class,
            $payment->getBackendTransaction($order, Operation::REFUND, $transaction)
        );
    }
}
