<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Mapper;

use PHPUnit\Framework\TestCase;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardEE\PaymentGateway\Mail\MerchantNotificationMail;
use WirecardEE\Tests\Stubs\PaymentHelperData;

class MerchantNotificationMailTest extends TestCase
{
    public function testAuthorizationMail()
    {
        $notification = $this->createMock(SuccessResponse::class);
        $notification->method('getTransactionType')->willReturn(Transaction::TYPE_AUTHORIZATION);
        $notification->method('getRequestedAmount')->willReturn(new Amount(10, 'EUR'));

        $notifyTransaction = $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class);
        $notifyTransaction->method('getOrder')->willReturn(new \Mage_Sales_Model_Order());

        $notifyMail = new MerchantNotificationMail(new PaymentHelperData());
        $mail       = $notifyMail->create('recipient@example.com', $notification, $notifyTransaction);

        $this->assertInstanceOf(\Zend_Mail::class, $mail);
        $this->assertEquals('owner@example.com', $mail->getFrom());
        $this->assertEquals('', $mail->getReplyTo());
        $this->assertEquals(['recipient@example.com'], $mail->getRecipients());
        $this->assertEquals('Payment notification received', $mail->getSubject());
        $this->assertStringStartsWith('OrderNumber:', $mail->getBodyText(true));
    }

    public function testPurchaseMail()
    {
        $notification = $this->createMock(SuccessResponse::class);
        $notification->method('getTransactionType')->willReturn(Transaction::TYPE_PURCHASE);
        $notification->method('getRequestedAmount')->willReturn(new Amount(10, 'EUR'));

        $notifyTransaction = $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class);
        $notifyTransaction->method('getOrder')->willReturn(new \Mage_Sales_Model_Order());

        $notifyMail = new MerchantNotificationMail(new PaymentHelperData());
        $mail       = $notifyMail->create('recipient@example.com', $notification, $notifyTransaction);

        $this->assertInstanceOf(\Zend_Mail::class, $mail);
        $this->assertEquals('owner@example.com', $mail->getFrom());
        $this->assertEquals('', $mail->getReplyTo());
        $this->assertEquals(['recipient@example.com'], $mail->getRecipients());
        $this->assertEquals('Payment notification received', $mail->getSubject());
        $this->assertStringStartsWith('OrderNumber:', $mail->getBodyText(true));
    }

    public function testNoSenderMail()
    {
        $notification = $this->createMock(SuccessResponse::class);
        $notification->method('getTransactionType')->willReturn(Transaction::TYPE_AUTHORIZATION);

        $notifyTransaction = $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class);

        $notifyMail = new MerchantNotificationMail(new PaymentHelperData());
        $this->assertNull($notifyMail->create('', $notification, $notifyTransaction));
    }

    public function testWrongTransactionTypeMail()
    {
        $notification = $this->createMock(SuccessResponse::class);
        $notification->method('getTransactionType')->willReturn(Transaction::TYPE_CREDIT);

        $notifyTransaction = $this->createMock(\Mage_Sales_Model_Order_Payment_Transaction::class);

        $notifyMail = new MerchantNotificationMail(new PaymentHelperData());
        $this->assertNull($notifyMail->create('recipient@example.com', $notification, $notifyTransaction));
    }
}
