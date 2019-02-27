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
use WirecardEE\PaymentGateway\Mail\UnmatchedTransactionMail;
use WirecardEE\Tests\Test\Stubs\PaymentHelperData;

class UnmatchedTransactionMailTest extends TestCase
{
    public function testAuthorizationMail()
    {
        $notification = $this->createMock(SuccessResponse::class);
        $notification->method('getTransactionType')->willReturn(Transaction::TYPE_AUTHORIZATION);
        $notification->method('getRequestedAmount')->willReturn(new Amount(10, 'EUR'));
        $notification->method('getData')->willReturn([]);

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getRealOrderId')->willReturn('1450010101');

        $notifyMail = new UnmatchedTransactionMail(new PaymentHelperData());
        $mail       = $notifyMail->create('recipient@example.com', $notification);

        $this->assertInstanceOf(\Zend_Mail::class, $mail);
        $this->assertEquals('owner@example.com', $mail->getFrom());
        $this->assertEquals('', $mail->getReplyTo());
        $this->assertEquals(['recipient@example.com'], $mail->getRecipients());
        $this->assertNotEmpty($mail->getSubject());
        $this->assertRegExp('/Array/', $mail->getBodyText(true));
    }

    public function testPurchaseMail()
    {
        $notification = $this->createMock(SuccessResponse::class);
        $notification->method('getTransactionType')->willReturn(Transaction::TYPE_PURCHASE);
        $notification->method('getRequestedAmount')->willReturn(new Amount(10, 'EUR'));
        $notification->method('getData')->willReturn([]);

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('getRealOrderId')->willReturn('1450010101');

        $notifyMail = new UnmatchedTransactionMail(new PaymentHelperData());
        $mail       = $notifyMail->create('recipient@example.com', $notification);

        $this->assertInstanceOf(\Zend_Mail::class, $mail);
        $this->assertEquals('owner@example.com', $mail->getFrom());
        $this->assertEquals('', $mail->getReplyTo());
        $this->assertEquals(['recipient@example.com'], $mail->getRecipients());
        $this->assertNotEmpty($mail->getSubject());
        $this->assertRegExp('/Array/', $mail->getBodyText(true));
    }

    public function testNoSenderMail()
    {
        $notification = $this->createMock(SuccessResponse::class);
        $notification->method('getTransactionType')->willReturn(Transaction::TYPE_AUTHORIZATION);

        $notifyMail = new UnmatchedTransactionMail(new PaymentHelperData());
        $this->assertNull($notifyMail->create('', $notification));
    }

    public function testWrongTransactionTypeMail()
    {
        $notification = $this->createMock(SuccessResponse::class);
        $notification->method('getTransactionType')->willReturn(Transaction::TYPE_CREDIT);

        $notifyMail = new UnmatchedTransactionMail(new PaymentHelperData());
        $this->assertNull($notifyMail->create('recipient@example.com', $notification));
    }
}
