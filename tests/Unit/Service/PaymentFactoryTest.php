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
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardEE\PaymentGateway\Data\PaymentConfig;
use WirecardEE\PaymentGateway\Exception\UnknownPaymentException;
use WirecardEE\PaymentGateway\Payments\PaymentInterface;
use WirecardEE\PaymentGateway\Payments\PaypalPayment;
use WirecardEE\PaymentGateway\Service\PaymentFactory;
use WirecardEE\Tests\Test\MagentoTestCase;

class PaymentFactoryTest extends MagentoTestCase
{
    public function testSupportedPayments()
    {
        $factory = new PaymentFactory();
        $this->assertNotEmpty($factory->getSupportedPayments());

        $operation  = new \ReflectionClass(Operation::class);
        $operations = $operation->getConstants();
        array_push($operations, null);

        foreach ($factory->getSupportedPayments() as $payment) {
            $this->assertInstanceOf(PaymentInterface::class, $payment);
            $this->assertNotEmpty($payment->getName());
            $this->assertInstanceOf(Transaction::class, $payment->getTransaction());
            $this->assertInstanceOf(Config::class, $payment->getTransactionConfig('EUR'));
            $this->assertInstanceOf(PaymentConfig::class, $payment->getPaymentConfig());
            $this->assertNotEmpty($payment->getPaymentConfig()->toArray());
            $this->assertTrue(in_array($payment->getCancelOperation(), $operations, true));
            $this->assertTrue(in_array($payment->getCaptureOperation(), $operations, true));
            $this->assertTrue(in_array($payment->getRefundOperation(), $operations, true));
        }
    }

    public function testIsSupportedPayments()
    {
        $magePayment = new \Mage_Sales_Model_Order_Payment();
        $factory     = new PaymentFactory();
        $this->assertFalse($factory->isSupportedPayment($magePayment));

        $magePayment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);
        $magePayment->method('getData')->willReturn('wirecardee_paymentgateway_paypal');
        $this->assertTrue($factory->isSupportedPayment($magePayment));

        $magePayment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);
        $magePayment->method('getData')->willReturn('foobar_payment');
        $this->assertFalse($factory->isSupportedPayment($magePayment));

        $magePayment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);
        $magePayment->method('getData')->willReturn('paypal');
        $this->assertFalse($factory->isSupportedPayment($magePayment));
    }

    public function testCreateFromMagePayment()
    {
        $factory = new PaymentFactory();

        $magePayment = $this->createMock(\Mage_Sales_Model_Order_Payment::class);
        $magePayment->method('getData')->willReturn('wirecardee_paymentgateway_paypal');
        $this->assertInstanceOf(PaypalPayment::class, $factory->createFromMagePayment($magePayment));
    }

    public function testPaypalInstance()
    {
        $factory = new PaymentFactory();
        $this->assertInstanceOf(PaypalPayment::class, $factory->create(PaypalPayment::NAME));
    }

    public function testUnknownPaymentException()
    {
        $factory = new PaymentFactory();
        $this->expectException(UnknownPaymentException::class);
        $factory->create('foobar');
    }
}
