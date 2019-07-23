<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Functional\Controller;

use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\AlipayCrossborderTransaction;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\Transaction\IdealTransaction;
use Wirecard\PaymentSdk\Transaction\EpsTransaction;
use Wirecard\PaymentSdk\Transaction\GiropayTransaction;
use Wirecard\PaymentSdk\Transaction\MaestroTransaction;
use Wirecard\PaymentSdk\Transaction\MasterpassTransaction;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction;
use Wirecard\PaymentSdk\Transaction\SofortTransaction;
use Wirecard\PaymentSdk\Transaction\UpiTransaction;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Actions\RedirectAction;
use WirecardEE\PaymentGateway\Actions\ViewAction;
use WirecardEE\PaymentGateway\Exception\UnknownPaymentException;
use WirecardEE\PaymentGateway\Service\SessionManager;
use WirecardEE\Tests\Test\Stubs\PaymentHelperData;
use WirecardEE\Tests\Test\MagentoTestCase;

class WirecardEEPaymentGatewayControllerTest extends MagentoTestCase
{
    public function setUp()
    {
        $this->requireFile('controllers/GatewayController.php');
    }

    public function testIndexActionWithNoParams()
    {
        $request  = $this->getMockForAbstractClass(\Zend_Controller_Request_Abstract::class);
        $response = $this->getMockForAbstractClass(\Zend_Controller_Response_Abstract::class);

        $controller = new \WirecardEE_PaymentGateway_GatewayController($request, $response);

        $this->expectException(UnknownPaymentException::class);
        $this->expectExceptionMessage("Unknown payment ''");
        $controller->indexAction();
    }

    public function testIndexActionWithUnknownPaymentMethod()
    {
        $request  = $this->getMockForAbstractClass(\Zend_Controller_Request_Abstract::class);
        $response = $this->getMockForAbstractClass(\Zend_Controller_Response_Abstract::class);
        $request->setParams([
            'method' => 'foo',
        ]);

        $controller = new \WirecardEE_PaymentGateway_GatewayController($request, $response);

        $this->expectException(UnknownPaymentException::class);
        $this->expectExceptionMessage("Unknown payment 'foo'");
        $controller->indexAction();
    }

    private function prepareForIndexAction($method, $expectTransaction = true)
    {
        $request = new \Mage_Core_Controller_Request_Http();
        $request->setParams(['method' => $method]);

        $response = $this->createMock(\Mage_Core_Controller_Response_Http::class);

        $controller = new \WirecardEE_PaymentGateway_GatewayController($request, $response);
        \Mage::app()->setRequest($request);

        $currency = $this->createMock(\Mage_Directory_Model_Currency::class);
        $currency->method('getCode')->willReturn('EUR');

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

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $payment->setOrder($order);
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

        $checkoutSession = $this->createMock(\Mage_Checkout_Model_Session::class);
        $checkoutSession->method('getLastRealOrder')->willReturn($order);

        $coreSession = $this->createMock(\Mage_Core_Model_Session::class);

        $transaction = null;
        if ($expectTransaction) {
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
        list($controller, $order, $transaction, $coreSession) = $this->prepareForIndexAction(CreditCardTransaction::NAME);

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
        list($controller, , $transaction, $coreSession) = $this->prepareForIndexAction(PayPalTransaction::NAME);

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
        list($controller, , $transaction, $coreSession) = $this->prepareForIndexAction(
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
        list($controller, , , $coreSession) = $this->prepareForIndexAction(
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
        $this->assertNotEmpty($error->getMessage());
    }

    public function testIndexActionWithSofort()
    {
        list($controller, , $transaction, $coreSession) = $this->prepareForIndexAction(SofortTransaction::NAME);

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

    public function testIndexActionWithIdeal()
    {
        list($controller, , $transaction, $coreSession) = $this->prepareForIndexAction(IdealTransaction::NAME);

        $transaction->expects($this->once())->method('setTxnType')->with('payment');

        $coreSession->method('getData')->willReturnMap([
            [SessionManager::PAYMENT_DATA, false, ['idealBank' => 'INGBNL2A']],
            [\WirecardEE_PaymentGateway_Helper_Data::DEVICE_FINGERPRINT_ID, false, md5('test')],
        ]);

        /** @var RedirectAction $action */
        $action = $controller->indexAction();
        $this->assertInstanceOf(RedirectAction::class, $action);
        $this->assertStringStartsWith(
            'https://idealtest.secure-ing.com/ideal/',
            $action->getUrl()
        );
    }

    public function testIndexActionWithEps()
    {
        list($controller, , $transaction, $coreSession) = $this->prepareForIndexAction(EpsTransaction::NAME);

        $transaction->expects($this->once())->method('setTxnType')->with('payment');

        $coreSession->method('getData')->willReturnMap([
            [SessionManager::PAYMENT_DATA, false, ['epsBic' => 'BWFBATW1XXX']],
            [\WirecardEE_PaymentGateway_Helper_Data::DEVICE_FINGERPRINT_ID, false, md5('test')],
        ]);

        /** @var RedirectAction $action */
        $action = $controller->indexAction();
        $this->assertInstanceOf(RedirectAction::class, $action);
        $this->assertStringStartsWith(
            'https://www.banking.co.at/appl/ebp/logout/so/loginPrepare/eps.html',
            $action->getUrl()
        );
    }

    public function testIndexActionWithMasterpass()
    {
        list($controller, , $transaction, $coreSession) = $this->prepareForIndexAction(MasterpassTransaction::NAME);

        $transaction->expects($this->once())->method('setTxnType')->with('payment');

        $coreSession->method('getData')->willReturnMap([
            [\WirecardEE_PaymentGateway_Helper_Data::DEVICE_FINGERPRINT_ID, false, md5('test')],
        ]);

        /** @var RedirectAction $action */
        $action = $controller->indexAction();
        $this->assertInstanceOf(RedirectAction::class, $action);
        $this->assertContains(
            '/engine/notification/masterpass/lightBoxPaymentPage',
            $action->getUrl()
        );
    }

    public function testIndexActionWithPoi()
    {
        list($controller, , , $coreSession) = $this->prepareForIndexAction('poi', false);

        $coreSession->method('getData')->willReturnMap([
            [\WirecardEE_PaymentGateway_Helper_Data::DEVICE_FINGERPRINT_ID, false, md5('test')],
        ]);
        $coreSession->method('getMessages')->willReturn(new \Mage_Core_Model_Message_Collection());

        /** @var ViewAction $action */
        $action = $controller->indexAction();
        $this->assertInstanceOf(ViewAction::class, $action);
        $this->assertNotEmpty($action->getAssignments());
        $this->assertContains('redirect', $action->getBlockName());
    }

    public function testIndexActionWithPia()
    {
        list($controller, , , $coreSession) = $this->prepareForIndexAction('pia', false);

        $coreSession->method('getData')->willReturnMap([
            [\WirecardEE_PaymentGateway_Helper_Data::DEVICE_FINGERPRINT_ID, false, md5('test')],
        ]);
        $coreSession->method('getMessages')->willReturn(new \Mage_Core_Model_Message_Collection());

        /** @var ViewAction $action */
        $action = $controller->indexAction();
        $this->assertInstanceOf(ViewAction::class, $action);
        $this->assertNotEmpty($action->getAssignments());
        $this->assertContains('redirect', $action->getBlockName());
    }

    /*public function testIndexActionWithGiropay()
    {
        list($controller, , $transaction, $coreSession) = $this->prepareForIndexAction(GiropayTransaction::NAME);

        $transaction->expects($this->once())->method('setTxnType')->with('payment');

        $coreSession->method('getData')->willReturnMap([
            [
                SessionManager::PAYMENT_DATA,
                false,
                [
                    'giropayBic' => 'GENODETT488',
                ],
            ],
            [\WirecardEE_PaymentGateway_Helper_Data::DEVICE_FINGERPRINT_ID, false, md5('test')],
        ]);
        $coreSession->method('getMessages')->willReturn(new \Mage_Core_Model_Message_Collection());*/

        /** @var RedirectAction $action */
        /*$action = $controller->indexAction();
        $this->assertInstanceOf(RedirectAction::class, $action);
        $this->assertStringStartsWith(
            'https://giropaytest1.fiducia.de/ShopSystem/bank',
            $action->getUrl()
        );
    }*/

    public function testIndexActionWithAlipay()
    {
        list($controller, , $transaction, $coreSession) = $this->prepareForIndexAction(AlipayCrossborderTransaction::NAME);

        $transaction->expects($this->once())->method('setTxnType')->with('payment');

        $coreSession->method('getData')->willReturnMap([
            [\WirecardEE_PaymentGateway_Helper_Data::DEVICE_FINGERPRINT_ID, false, md5('test')],
        ]);
        $coreSession->method('getMessages')->willReturn(new \Mage_Core_Model_Message_Collection());

        /** @var RedirectAction $action */
        $action = $controller->indexAction();
        $this->assertInstanceOf(RedirectAction::class, $action);
        $this->assertContains('alipaydev.com/gateway', $action->getUrl());
    }

    public function testIndexActionWithMaestro()
    {
        list($controller, $order, $transaction, $coreSession) = $this->prepareForIndexAction(MaestroTransaction::NAME);

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
        $this->assertEquals('https://api-wdcee-test.wirecard.com', $assignments['wirecardUrl']);
        $this->assertStringEndsWith('paymentgateway/gateway/return/method/maestro/', $assignments['url']);
    }

    public function testIndexActionWithUnionpay()
    {
        list($controller, $order, $transaction, $coreSession) = $this->prepareForIndexAction(UpiTransaction::NAME);

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
        $this->assertStringEndsWith('paymentgateway/gateway/return/method/unionpayinternational/', $assignments['url']);
    }

    public function testInsufficientDataExceptionIndexActionWithGiropay()
    {
        list($controller, , , $coreSession) = $this->prepareForIndexAction(
            GiropayTransaction::NAME,
            false
        );

        $coreSession->method('getData')->willReturnMap([
            [\WirecardEE_PaymentGateway_Helper_Data::DEVICE_FINGERPRINT_ID, false, md5('test')],
        ]);
        $coreSession->method('getMessages')->willReturn(new \Mage_Core_Model_Message_Collection());

        /** @var ErrorAction $error */
        $error = $controller->indexAction();
        $this->assertInstanceOf(ErrorAction::class, $error);
        $this->assertNotEmpty($error->getMessage());
    }

    public function testReturnActionWithNoParams()
    {
        $request  = $this->getMockForAbstractClass(\Zend_Controller_Request_Abstract::class);
        $response = $this->getMockForAbstractClass(\Zend_Controller_Response_Abstract::class);

        $controller = new \WirecardEE_PaymentGateway_GatewayController($request, $response);

        $this->expectException(UnknownPaymentException::class);
        $this->expectExceptionMessage("Unknown payment ''");
        $controller->returnAction();
    }

    private function prepareForReturnAction($params)
    {
        $request = new \Mage_Core_Controller_Request_Http();
        $request->setParams($params);

        $response = $this->createMock(\Mage_Core_Controller_Response_Http::class);

        $controller = new \WirecardEE_PaymentGateway_GatewayController($request, $response);
        \Mage::app()->setRequest($request);

        $currency = $this->createMock(\Mage_Directory_Model_Currency::class);
        $currency->method('getCode')->willReturn('EUR');

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getBaseCurrency')->willReturn($currency);
        $order->method('getRealOrderId')->willReturn('145000001');
        $order->method('getId')->willReturn("1");

        $checkoutSession = $this->createMock(\Mage_Checkout_Model_Session::class);
        $checkoutSession->method('getLastRealOrder')->willReturn($order);

        $coreSession = $this->createMock(\Mage_Core_Model_Session::class);

        $this->replaceMageSingleton('checkout/session', $checkoutSession);
        $this->replaceMageSingleton('core/session', $coreSession);
        $this->replaceMageHelper('paymentgateway', new PaymentHelperData());

        return [$controller, $order, $coreSession];
    }

    public function testReturnActionPayloadFailure()
    {
        list($controller) = $this->prepareForReturnAction(['method' => CreditCardTransaction::NAME]);

        /** @var ErrorAction $error */
        $error = $controller->returnAction();
        $this->assertInstanceOf(ErrorAction::class, $error);
        $this->assertEquals('Return processing failed', $error->getMessage());
    }

    public function testReturnActionWithCreditCard()
    {
        $payload = json_decode(file_get_contents(__DIR__ . '/../fixtures/creditcard_return.json'), true);
        list($controller) = $this->prepareForReturnAction($payload);

        /** @var RedirectAction $action */
        $action = $controller->returnAction();
        $this->assertInstanceOf(RedirectAction::class, $action);
        $this->assertStringEndsWith('/checkout/onepage/success/', $action->getUrl());
    }

    public function testCancelAction()
    {
        $request  = new \Mage_Core_Controller_Request_Http();
        $response = $this->createMock(\Mage_Core_Controller_Response_Http::class);

        $controller = new \WirecardEE_PaymentGateway_GatewayController($request, $response);
        \Mage::app()->setRequest($request);

        $this->assertTrue($controller->cancelAction());
    }

    public function testDeleteCreditCardTokenAction()
    {
        $customerSession = $this->createMock(\Mage_Customer_Model_Session::class);
        $customerSession->method('getCustomerId')->willReturn(1);

        $this->replaceMageSingleton('customer/session', $customerSession);

        $vaultToken = $this->createMock(\WirecardEE_PaymentGateway_Model_CreditCardVaultToken::class);
        $vaultToken->method('isEmpty')->willReturn(false);
        $vaultToken->method('getCustomerId')->willReturn(1);
        $vaultToken->expects($this->once())->method('delete');

        $this->replaceMageModel('paymentgateway/creditCardVaultToken', $vaultToken);

        $request  = new \Mage_Core_Controller_Request_Http();
        $response = $this->createMock(\Mage_Core_Controller_Response_Http::class);

        $controller = new \WirecardEE_PaymentGateway_GatewayController($request, $response);
        \Mage::app()->setRequest($request);

        $this->assertInstanceOf(\Mage_Core_Controller_Varien_Action::class, $controller->deleteCreditCardTokenAction());
    }

    public function testFailureAction()
    {
        $request  = new \Mage_Core_Controller_Request_Http();
        $response = $this->createMock(\Mage_Core_Controller_Response_Http::class);

        $controller = new \WirecardEE_PaymentGateway_GatewayController($request, $response);
        \Mage::app()->setRequest($request);

        $order           = $this->createMock(\Mage_Sales_Model_Order::class);
        $checkoutSession = $this->createMock(\Mage_Checkout_Model_Session::class);
        $checkoutSession->method('getLastRealOrder')->willReturn($order);

        $checkoutSession->expects($this->once())->method('setData');

        $this->replaceMageSingleton('checkout/session', $checkoutSession);

        $this->assertInstanceOf(\Mage_Core_Controller_Varien_Action::class, $controller->failureAction());
    }

    public function testCancelFailsAction()
    {
        $request  = new \Mage_Core_Controller_Request_Http();
        $response = $this->createMock(\Mage_Core_Controller_Response_Http::class);

        $controller = new \WirecardEE_PaymentGateway_GatewayController($request, $response);
        \Mage::app()->setRequest($request);

        $checkoutSession = $this->createMock(\Mage_Checkout_Model_Session::class);
        $checkoutSession->method('getLastRealOrder')->willReturn(null);

        $this->replaceMageSingleton('checkout/session', $checkoutSession);

        $this->assertFalse($controller->cancelAction());
    }

    public function testNotifyActionWithNoParams()
    {
        $request  = $this->getMockForAbstractClass(\Zend_Controller_Request_Abstract::class);
        $response = $this->getMockForAbstractClass(\Zend_Controller_Response_Abstract::class);

        $controller = new \WirecardEE_PaymentGateway_GatewayController($request, $response);

        $this->expectException(UnknownPaymentException::class);
        $this->expectExceptionMessage("Unknown payment ''");
        $controller->notifyAction();
    }

    private function prepareForNotifyAction($method, $payload, $expectInvoice = false, $expectTransaction = false)
    {
        $request = $this->createMock(\Mage_Core_Controller_Request_Http::class);
        $request->method('getParam')->willReturn($method);
        $request->method('getRawBody')->willReturn($payload);

        $response = $this->createMock(\Mage_Core_Controller_Response_Http::class);

        $controller = new \WirecardEE_PaymentGateway_GatewayController($request, $response);
        \Mage::app()->setRequest($request);

        $payment = new \Mage_Sales_Model_Order_Payment();

        $currency = $this->createMock(\Mage_Directory_Model_Currency::class);
        $currency->method('getCode')->willReturn('EUR');

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getPayment')->willReturn($payment);
        $order->method('getBaseCurrency')->willReturn($currency);
        $order->method('getRealOrderId')->willReturn('145000001');
        $order->method('getAllVisibleItems')->willReturn([]);
        $order->method('getId')->willReturn("1");
        $order->method('load')->willReturnSelf();
        $order->method('canInvoice')->willReturn(true);

        if ($expectInvoice) {
            $invoice = $this->createMock(\Mage_Sales_Model_Order_Invoice::class);
            $invoice->method('register')->willReturnSelf();
            $invoice->method('getOrder')->willReturn($order);
            $order->expects($this->once())->method('prepareInvoice')->willReturn($invoice);
        }

        $resourceTransaction = $this->createMock(\Mage_Core_Model_Resource_Transaction::class);
        $resourceTransaction->method('addObject')->willReturn($resourceTransaction);
        $this->replaceMageModel('core/resource_transaction', $resourceTransaction);

        $checkoutSession = $this->createMock(\Mage_Checkout_Model_Session::class);
        $checkoutSession->method('getLastRealOrder')->willReturn($order);

        $coreSession = $this->createMock(\Mage_Core_Model_Session::class);

        $transaction = null;
        if ($expectTransaction) {
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
        $this->replaceMageModel('sales/order', $order);

        return [$controller, $order, $coreSession];
    }

    public function testNotifyActionMalformedResponseFailure()
    {
        list($controller) = $this->prepareForNotifyAction(CreditCardTransaction::NAME, null);

        $this->assertNull($controller->notifyAction());
    }

    public function testNotifyActionWithCreditCard()
    {
        $payload = file_get_contents(__DIR__ . '/../fixtures/creditcard_notify.xml');
        list($controller) = $this->prepareForNotifyAction(CreditCardTransaction::NAME, $payload, true, true);

        /** @var SuccessResponse $notification */
        $notification = $controller->notifyAction();
        $this->assertInstanceOf(SuccessResponse::class, $notification);
        $this->assertEquals('abcdefgh-abcd-1234-4321-abc123cba321', $notification->getTransactionId());
        $this->assertEquals('purchase', $notification->getTransactionType());
    }
}
