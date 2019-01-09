<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Functional\Controller;

use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction;
use Wirecard\PaymentSdk\Transaction\SofortTransaction;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Actions\RedirectAction;
use WirecardEE\PaymentGateway\Actions\ViewAction;
use WirecardEE\PaymentGateway\Exception\UnknownPaymentException;
use WirecardEE\PaymentGateway\Service\SessionManager;
use WirecardEE\Tests\Stubs\PaymentHelperData;
use WirecardEE\Tests\Test\MagentoTestCase;

class WirecardEEPaymentGatewayControllerTest extends MagentoTestCase
{
    public function setUp()
    {
        $this->requireFile('controllers/GatewayController.php');
    }

    public function testIndexActionWithNoParams()
    {
        /** @var \Zend_Controller_Request_Abstract|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMockForAbstractClass(\Zend_Controller_Request_Abstract::class);
        /** @var \Zend_Controller_Response_Abstract|\PHPUnit_Framework_MockObject_MockObject $response */
        $response = $this->getMockForAbstractClass(\Zend_Controller_Response_Abstract::class);

        $controller = new \WirecardEE_PaymentGateway_GatewayController($request, $response);

        $this->expectException(UnknownPaymentException::class);
        $this->expectExceptionMessage("Unknown payment ''");
        $controller->indexAction();
    }

    public function testIndexActionWithUnknownPaymentMethod()
    {
        /** @var \Zend_Controller_Request_Abstract|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMockForAbstractClass(\Zend_Controller_Request_Abstract::class);
        /** @var \Zend_Controller_Response_Abstract|\PHPUnit_Framework_MockObject_MockObject $response */
        $response = $this->getMockForAbstractClass(\Zend_Controller_Response_Abstract::class);

        $request->setParams([
            'method' => 'foo',
        ]);

        $controller = new \WirecardEE_PaymentGateway_GatewayController($request, $response);

        $this->expectException(UnknownPaymentException::class);
        $this->expectExceptionMessage("Unknown payment 'foo'");
        $controller->indexAction();
    }

    private function prepareForAction($method, $expectTransaction = true)
    {
        /** @var \Mage_Core_Controller_Request_Http|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = new \Mage_Core_Controller_Request_Http();
        $request->setParams(['method' => $method]);

        /** @var \Mage_Core_Controller_Response_Http|\PHPUnit_Framework_MockObject_MockObject $response */
        $response = $this->createMock(\Mage_Core_Controller_Response_Http::class);

        $controller = new \WirecardEE_PaymentGateway_GatewayController($request, $response);
        \Mage::app()->setRequest($request);

        /** @var \Mage_Directory_Model_Currency|\PHPUnit_Framework_MockObject_MockObject $order */
        $currency = $this->createMock(\Mage_Directory_Model_Currency::class);
        $currency->method('getCode')->willReturn('EUR');

        /** @var \Mage_Sales_Model_Order_Address|\PHPUnit_Framework_MockObject_MockObject $order */
        $address = $this->createMock(\Mage_Sales_Model_Order_Address::class);
        $address->method('__call')->willReturnMap([
            ['getFirstname', [], 'firstname'],
            ['getLastname', [], 'lastname'],
            ['getCountryId', [], 'AT'],
            ['getCity', [], 'Graz'],
            ['getPostcode', [], '8010'],
        ]);
        $address->method('getStreet1')->willReturn('Mainstreet');
        $address->method('getStreet2')->willReturn('');
        $address->method('getRegion')->willReturn('Steiermark');

        $payment = new \Mage_Sales_Model_Order_Payment();

        $item = new \Mage_Sales_Model_Order_Item();
        $item->setName('fooitem');
        $item->setSku('123');
        $item->setDescription('bar');
        $item->setPriceInclTax(20);
        $item->setQtyOrdered(1);
        $item->setTaxAmount(2);
        $item->setTaxPercent("10.0");

        /** @var \Mage_Sales_Model_Order|\PHPUnit_Framework_MockObject_MockObject $order */
        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('__call')->willReturnMap([
            ['getBaseGrandTotal', [], 25],
            ['getBaseCurrencyCode', [], 'EUR'],
            ['getShippingInclTax', [], 5],
            ['getShippingDescription', [], 'Shipping'],
            ['getShippingTaxAmount', [], 1],
        ]);
        $order->method('getAllVisibleItems')->willReturn([$item]);
        $order->method('getBaseCurrency')->willReturn($currency);
        $order->method('getBillingAddress')->willReturn($address);
        $order->method('getShippingAddress')->willReturn($address);
        $order->method('getPayment')->willReturn($payment);
        $order->method('getRealOrderId')->willReturn('145000001');
        $order->method('getId')->willReturn("1");

        /** @var \Mage_Checkout_Model_Session|\PHPUnit_Framework_MockObject_MockObject $checkoutSession */
        $checkoutSession = $this->createMock(\Mage_Checkout_Model_Session::class);
        $checkoutSession->method('getLastRealOrder')->willReturn($order);

        /** @var \Mage_Core_Model_Session|\PHPUnit_Framework_MockObject_MockObject $checkoutSession */
        $coreSession = $this->createMock(\Mage_Core_Model_Session::class);

        $transaction = null;
        if ($expectTransaction) {
            /** @var \Mage_Sales_Model_Order_Payment_Transaction|\PHPUnit_Framework_MockObject_MockObject $session */
            $transaction = $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class);
            $transaction->expects($this->once())->method('setOrderPaymentObject')->with($payment);
            $transaction->expects($this->once())->method('setAdditionalInformation');
            $transaction->expects($this->once())->method('save');
            $transaction->method('loadByTxnId')->willReturn($transaction);

            $this->replaceMageModel('sales/order_payment_transaction', $transaction);
        }

        $this->replaceMageSingleton('checkout/session', $checkoutSession);
        $this->replaceMageSingleton('core/session', $coreSession);
        $this->replaceMageHelper('paymentgateway', new PaymentHelperData());

        return [$controller, $order, $transaction, $coreSession];
    }

    public function testIndexActionWithCreditCard()
    {
        list($controller, $order, $transaction, $coreSession) = $this->prepareForAction(CreditCardTransaction::NAME);

        $transaction->expects($this->once())->method('setTxnType')->with('capture');
        $transaction->expects($this->once())->method('setOrder')->with($order);

        $coreSession->method('getData')->willReturnMap([
            [\WirecardEE_PaymentGateway_Helper_Data::DEVICE_FINGERPRINT_ID, false, md5('test')],
        ]);
        $coreSession->method('getMessages')->willReturn(new \Mage_Core_Model_Message_Collection());

        /** @var ViewAction $action */
        $action = $controller->indexAction();
        $this->assertInstanceOf(ViewAction::class, $action);
        $this->assertEquals('paymentgateway/seamless', $action->getBlockName());
        $assignments = $action->getAssignments();
        $this->assertArrayHasKey('wirecardUrl', $assignments);
        $this->assertArrayHasKey('wirecardRequestData', $assignments);
        $this->assertArrayHasKey('url', $assignments);
        $this->assertEquals('https://api-test.wirecard.com', $assignments['wirecardUrl']);
        $this->assertStringEndsWith('paymentgateway/gateway/return/method/creditcard/', $assignments['url']);
    }

    public function testIndexActionWithPayPal()
    {
        list($controller, , $transaction, $coreSession) = $this->prepareForAction(PayPalTransaction::NAME);

        $transaction->expects($this->once())->method('setTxnType')->with('payment');

        $coreSession->method('getData')->willReturnMap([
            [\WirecardEE_PaymentGateway_Helper_Data::DEVICE_FINGERPRINT_ID, false, md5('test')],
        ]);

        /** @var RedirectAction $action */
        $action = $controller->indexAction();
        $this->assertInstanceOf(RedirectAction::class, $action);
        $this->assertStringStartsWith(
            'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&useraction=commit&token=',
            $action->getUrl()
        );
    }

    public function testIndexActionWithSepaDirectDebit()
    {
        list($controller, , $transaction, $coreSession) = $this->prepareForAction(
            SepaDirectDebitTransaction::NAME
        );

        $transaction->expects($this->once())->method('setTxnType')->with('payment');

        $coreSession->method('getData')->willReturnMap([
            [
                SessionManager::PAYMENT_DATA,
                false,
                [
                    'sepaFirstName' => 'sepaFirst',
                    'sepaLastName'  => 'sepaLast',
                    'sepaIban'      => 'DE42512308000000060004',
                ],
            ],
            [\WirecardEE_PaymentGateway_Helper_Data::DEVICE_FINGERPRINT_ID, false, md5('test')],
        ]);
        $coreSession->method('getMessages')->willReturn(new \Mage_Core_Model_Message_Collection());

        /** @var ViewAction $action */
        $action = $controller->indexAction();
        $this->assertInstanceOf(ViewAction::class, $action);
        $this->assertEquals('paymentgateway/redirect', $action->getBlockName());
        $assignments = $action->getAssignments();
        $this->assertArrayHasKey('method', $assignments);
        $this->assertArrayHasKey('url', $assignments);
        $this->assertEquals('POST', $assignments['method']);
        $this->assertStringEndsWith('paymentgateway/gateway/return/method/sepadirectdebit/', $assignments['url']);
    }

    public function testInsufficientDataExceptionIndexActionWithSepaDirectDebit()
    {
        list($controller, , , $coreSession) = $this->prepareForAction(
            SepaDirectDebitTransaction::NAME,
            false
        );

        $coreSession->method('getData')->willReturnMap([
            [\WirecardEE_PaymentGateway_Helper_Data::DEVICE_FINGERPRINT_ID, false, md5('test')],
        ]);
        $coreSession->method('getMessages')->willReturn(new \Mage_Core_Model_Message_Collection());

        /** @var ErrorAction $error */
        $error = $controller->indexAction();
        $this->assertInstanceOf(ErrorAction::class, $error);
        $this->assertEquals('Transaction processing failed', $error->getMessage());
    }

    public function testIndexActionWithSofort()
    {
        list($controller, , $transaction, $coreSession) = $this->prepareForAction(SofortTransaction::NAME);

        $transaction->expects($this->once())->method('setTxnType')->with('payment');

        $coreSession->method('getData')->willReturnMap([
            [\WirecardEE_PaymentGateway_Helper_Data::DEVICE_FINGERPRINT_ID, false, md5('test')],
        ]);

        /** @var RedirectAction $action */
        $action = $controller->indexAction();
        $this->assertInstanceOf(RedirectAction::class, $action);
        $this->assertStringStartsWith(
            'https://www.sofort.com/payment/go/',
            $action->getUrl()
        );
    }

    public function testReturnActionWithNoParams()
    {
        /** @var \Zend_Controller_Request_Abstract|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMockForAbstractClass(\Zend_Controller_Request_Abstract::class);
        /** @var \Zend_Controller_Response_Abstract|\PHPUnit_Framework_MockObject_MockObject $response */
        $response = $this->getMockForAbstractClass(\Zend_Controller_Response_Abstract::class);

        $controller = new \WirecardEE_PaymentGateway_GatewayController($request, $response);

        $this->expectException(UnknownPaymentException::class);
        $this->expectExceptionMessage("Unknown payment ''");
        $controller->returnAction();
    }
}
