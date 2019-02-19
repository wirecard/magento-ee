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
use Wirecard\PaymentSdk\Transaction\MaestroTransaction;
use Wirecard\PaymentSdk\Transaction\Operation;
use WirecardEE\PaymentGateway\Data\PaymentConfig;
use WirecardEE\PaymentGateway\Payments\MaestroPayment;
use WirecardEE\Tests\Test\MagentoTestCase;

class MaestroPaymentTest extends MagentoTestCase
{
    public function testPayment()
    {
        $payment     = new MaestroPayment();
        $transaction = $payment->getTransaction();
        $this->assertInstanceOf(MaestroTransaction::class, $transaction);
        $this->assertSame($transaction, $payment->getTransaction());

        $this->assertInstanceOf(Config::class, $payment->getTransactionConfig('EUR'));
        $this->assertInstanceOf(PaymentConfig::class, $payment->getPaymentConfig());

        $order       = $this->createMock(\Mage_Sales_Model_Order::class);
        $transaction = $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class);
        $this->assertInstanceOf(
            MaestroTransaction::class,
            $payment->getBackendTransaction($order, Operation::CREDIT, $transaction)
        );
        $this->assertInstanceOf(
            MaestroTransaction::class,
            $payment->getBackendTransaction($order, Operation::CANCEL, $transaction)
        );
    }
}
