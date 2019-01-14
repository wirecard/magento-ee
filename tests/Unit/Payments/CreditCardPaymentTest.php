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
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\Transaction\Operation;
use WirecardEE\PaymentGateway\Data\CreditCardPaymentConfig;
use WirecardEE\PaymentGateway\Payments\CreditCardPayment;
use WirecardEE\Tests\Test\MagentoTestCase;

class CreditCardPaymentTest extends MagentoTestCase
{
    public function testPayment()
    {
        $payment     = new CreditCardPayment();
        $transaction = $payment->getTransaction();
        $this->assertInstanceOf(CreditCardTransaction::class, $transaction);
        $this->assertSame($transaction, $payment->getTransaction());

        $this->assertInstanceOf(Config::class, $payment->getTransactionConfig('EUR'));
        $this->assertInstanceOf(CreditCardPaymentConfig::class, $payment->getPaymentConfig());

        $order       = $this->createMock(\Mage_Sales_Model_Order::class);
        $transaction = $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class);
        $this->assertInstanceOf(
            CreditCardTransaction::class,
            $payment->getBackendTransaction($order, Operation::CREDIT, $transaction)
        );
        $this->assertInstanceOf(
            CreditCardTransaction::class,
            $payment->getBackendTransaction($order, Operation::CANCEL, $transaction)
        );
    }
}
