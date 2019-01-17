<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Payments;

use PHPUnit\Framework\TestCase;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Device;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Mapper\UserMapper;
use WirecardEE\PaymentGateway\Payments\PayByBankAppPayment;

class PayByBankAppPaymentTest extends TestCase
{
    protected $userAgent = 'User-Agent: Mozilla/5.0 (Linux; Android 6.0; Nexus 6P Build/MDB08L) AppleWebKit/537.36 ' .
            '(KHTML, like Gecko) Chrome/47.0.2526.69 Mobile Safari/537.36';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|OrderSummary
     */
    protected $orderSummaryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UserMapper
     */
    protected $userMapperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TransactionService
     */
    protected $transactionServiceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Redirect
     */
    protected $redirectMock;

    public function setup()
    {
        $this->orderSummaryMock = $this->createMock(OrderSummary::class);
        $this->userMapperMock = $this->createMock(UserMapper::class);
        $this->transactionServiceMock = $this->createMock(TransactionService::class);
        $this->redirectMock = $this->createMock(Redirect::class);

        $this->orderSummaryMock->method('getUserMapper')->willReturn($this->userMapperMock);
    }

    public function testBase()
    {
        $pmnt = new PayByBankAppPayment();
        $this->assertEquals('zapp', $pmnt->getName());
        $this->assertInstanceOf('Wirecard\PaymentSdk\Transaction\PayByBankAppTransaction', $pmnt->getTransaction());
    }

    public function testGetTransactionConfig()
    {
        $pmnt = new PayByBankAppPayment();

        $cfg = $pmnt->getTransactionConfig('GBP');

        $this->assertEquals('https://api-test.wirecard.com', $cfg->getBaseUrl());
        $this->assertEquals('70000-APITEST-AP', $cfg->getHttpUser());
        $this->assertEquals('qD2wzQ_hrc!8', $cfg->getHttpPassword());
        $this->assertEquals('GBP', $cfg->getDefaultCurrency());

        $pbbaConfig = $cfg->get('zapp');
        $this->assertEquals('zapp', $pbbaConfig->getPaymentMethodName());
        $this->assertEquals('70055b24-38f1-4500-a3a8-afac4b1e3249', $pbbaConfig->getMerchantAccountId());
        $this->assertEquals('4a4396df-f78c-44b9-b8a0-b72b108ac465', $pbbaConfig->getSecret());
    }

    public function testGetPaymentConfig()
    {
        $pmnt = new PayByBankAppPayment();

        $cfg = $pmnt->getPaymentConfig();
        $this->assertInstanceOf('WirecardEE\PaymentGateway\Data\PaymentConfig', $cfg);
        $this->assertEquals('pay', $cfg->getTransactionOperation());
        $this->assertTrue($cfg->sendBasket());
        $this->assertTrue($cfg->hasFraudPrevention());
    }

    public function testProcessPayment()
    {
        $pmnt = new PayByBankAppPayment();

        $this->userMapperMock->method('getUserAgent')->willReturn($this->userAgent);

        $pmnt->processPayment($this->orderSummaryMock, $this->transactionServiceMock, $this->redirectMock);

        $transaction = $pmnt->getTransaction();
        $device = $transaction->getDevice();
        $this->assertInstanceOf(Device::class, $device);
        $this->assertEquals('mobile', $device->getType());
        $this->assertEquals('android', $device->getOperatingSystem());

        $cFields = $transaction->getCustomFields();

        $this->assertInstanceOf(CustomFieldCollection::class, $cFields);
        $this->assertCount(3, $cFields);

        $this->assertEquals('PAYMT', $cFields->get('TxType'));
        $this->assertEquals('DELTAD', $cFields->get('DeliveryType'));
    }

    public function testGetBackendTransaction()
    {
        $pmnt = new PayByBankAppPayment();

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Mage_Sales_Model_Order $mageOrderMock */
        $mageOrderMock = $this->createMock(\Mage_Sales_Model_Order::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Mage_Sales_Model_Order_Payment_Transaction $magePmntMock */
        $magePmntMock = $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class);

        $transaction = $pmnt->getBackendTransaction($mageOrderMock, 'refund', $magePmntMock);

        $cFields = $transaction->getCustomFields();

        $this->assertInstanceOf(CustomFieldCollection::class, $cFields);
        $this->assertCount(2, $cFields);

        $this->assertEquals('LATECONFIRMATION', $cFields->get('RefundReasonType'));
        $this->assertEquals('BACS', $cFields->get('RefundMethod'));
    }

    public function testProcessPaymentUnkownUserAgent()
    {
        $pmnt = new PayByBankAppPayment();

        $this->userMapperMock->method('getUserAgent')->willReturn('XXX');

        $pmnt->processPayment($this->orderSummaryMock, $this->transactionServiceMock, $this->redirectMock);

        $transaction = $pmnt->getTransaction();
        $device = $transaction->getDevice();
        $this->assertInstanceOf(Device::class, $device);
        $this->assertEquals('other', $device->getType());
        $this->assertEquals('other', $device->getOperatingSystem());
    }
}
