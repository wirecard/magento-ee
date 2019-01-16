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
use WirecardEE\PaymentGateway\Mail\SupportMail;
use WirecardEE\PaymentGateway\Service\PaymentFactory;

class SupportMailTest extends TestCase
{
    public function testMail()
    {
        $paymentFactory = $this->createMock(PaymentFactory::class);
        $paymentFactory->method('getSupportedPayments')->willReturn([]);

        $supportMail = new SupportMail($paymentFactory);
        $mail        = $supportMail->create('sender@example.com', 'Testmessage');

        $this->assertInstanceOf(\Zend_Mail::class, $mail);
        $this->assertEquals('sender@example.com', $mail->getFrom());
        $this->assertEquals('', $mail->getReplyTo());
        $this->assertEquals(['shop-systems-support@wirecard.com'], $mail->getRecipients());
        $this->assertEquals('Magento support request', $mail->getSubject());
        $this->assertStringStartsWith('Testmessage', $mail->getBodyText(true));
    }

    public function testMailReplyTo()
    {
        $paymentFactory = $this->createMock(PaymentFactory::class);
        $paymentFactory->method('getSupportedPayments')->willReturn([]);

        $supportMail = new SupportMail($paymentFactory);
        $mail        = $supportMail->create('sender@example.com', 'Testmessage', 'reply@example.com');

        $this->assertInstanceOf(\Zend_Mail::class, $mail);
        $this->assertEquals('sender@example.com', $mail->getFrom());
        $this->assertEquals('reply@example.com', $mail->getReplyTo());
        $this->assertEquals(['shop-systems-support@wirecard.com'], $mail->getRecipients());
        $this->assertEquals('Magento support request', $mail->getSubject());
        $this->assertStringStartsWith('Testmessage', $mail->getBodyText(true));
    }
}
