<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;

class PaymentTest extends TestCase
{
    public function testCreditCard()
    {
        $payment = new \WirecardEE_PaymentGateway_Model_CreditCard();
        $this->assertStringEndsWith(
            '/paymentgateway/gateway/index/method/creditcard/',
            $payment->getOrderPlaceRedirectUrl()
        );
        $this->assertEquals('wirecardee_paymentgateway_creditcard', $payment->getCode());
        $this->assertNotEmpty($payment->toOptionArray());
    }

    public function testPayPal()
    {
        $payment = new \WirecardEE_PaymentGateway_Model_Paypal();
        $this->assertStringEndsWith(
            '/paymentgateway/gateway/index/method/paypal/',
            $payment->getOrderPlaceRedirectUrl()
        );
        $this->assertEquals('wirecardee_paymentgateway_paypal', $payment->getCode());
        $this->assertNotEmpty($payment->toOptionArray());
    }

    public function testSepaDirectDebit()
    {
        $address = $this->createMock(\Mage_Sales_Model_Order_Address::class);
        $address->method('__call')->willReturnMap([['getCountryId', [], 'AT']]);

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getBillingAddress')->willReturn($address);
        $paymentInfo = $this->createMock(\Mage_Sales_Model_Order_Payment::class);
        $paymentInfo->method('getOrder')->willReturn($order);

        $request = new \Mage_Core_Controller_Request_Http();
        $request->setParams([
            'wirecardElasticEngine' => [
                'sepaFirstName'      => 'sepaFirst',
                'sepaLastName'       => 'sepaLast',
                'sepaIban'           => 'DE42512308000000060004',
                'sepaConfirmMandate' => 'confirmed',
            ],
        ]);
        \Mage::app()->setRequest($request);

        $payment = new \WirecardEE_PaymentGateway_Model_Sepadirectdebit();
        $payment->setData('info_instance', $paymentInfo);
        $this->assertStringEndsWith(
            '/paymentgateway/gateway/index/method/sepadirectdebit/',
            $payment->getOrderPlaceRedirectUrl()
        );
        $this->assertEquals('wirecardee_paymentgateway_sepadirectdebit', $payment->getCode());
        $this->assertNotEmpty($payment->toOptionArray());

        $this->assertNotEmpty($payment->getMandateText());
        $this->assertEquals($payment, $payment->validate());
    }

    public function testSepaDirectDebitInvalid()
    {
        $address = $this->createMock(\Mage_Sales_Model_Order_Address::class);
        $address->method('__call')->willReturnMap([['getCountryId', [], 'AT']]);

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getBillingAddress')->willReturn($address);
        $paymentInfo = $this->createMock(\Mage_Sales_Model_Order_Payment::class);
        $paymentInfo->method('getOrder')->willReturn($order);

        $request = new \Mage_Core_Controller_Request_Http();
        $request->setParams([
            'wirecardElasticEngine' => [
                'sepaFirstName'      => '',
                'sepaLastName'       => '',
                'sepaIban'           => '',
                'sepaConfirmMandate' => '',
            ],
        ]);
        \Mage::app()->setRequest($request);

        $payment = new \WirecardEE_PaymentGateway_Model_Sepadirectdebit();
        $payment->setData('info_instance', $paymentInfo);
        $this->expectException(\Mage_Core_Exception::class);
        $this->assertEquals($payment, $payment->validate());
    }

    public function testSofort()
    {
        $payment = new \WirecardEE_PaymentGateway_Model_Sofortbanking();
        $this->assertStringEndsWith(
            '/paymentgateway/gateway/index/method/sofortbanking/',
            $payment->getOrderPlaceRedirectUrl()
        );
        $this->assertEquals('wirecardee_paymentgateway_sofortbanking', $payment->getCode());
        $this->assertNotEmpty($payment->toOptionArray());
    }
}