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
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use WirecardEE\PaymentGateway\Mapper\ResponseMapper;

class ResponseMapperTest extends TestCase
{
    public function testSuccessResponse()
    {
        $respone = $this->createMock(SuccessResponse::class);
        $respone->method('getRequestId')->willReturn('req-id');
        $respone->method('getTransactionType')->willReturn('auth');
        $respone->method('getTransactionId')->willReturn('trans-id');
        $respone->method('getParentTransactionId')->willReturn('parent-id');
        $respone->method('getProviderTransactionId')->willReturn('prov-trans-id');
        $respone->method('getProviderTransactionReference')->willReturn('prov-trans-ref');
        $respone->method('getRequestedAmount')->willReturn($amount = new Amount(1, 'EUR'));
        $respone->method('getData')->willReturn(['data']);
        $respone->method('getBasket')->willReturn($basket = new Basket());
        $respone->method('getPaymentMethod')->willReturn('method');

        $mapper = new ResponseMapper($respone);
        $this->assertSame('req-id', $mapper->getRequestId());
        $this->assertSame('auth', $mapper->getTransactionType());
        $this->assertSame('trans-id', $mapper->getTransactionId());
        $this->assertSame('parent-id', $mapper->getParentTransactionId());
        $this->assertSame('prov-trans-id', $mapper->getProviderTransactionId());
        $this->assertSame('prov-trans-ref', $mapper->getProviderTransactionReference());
        $this->assertSame($amount, $mapper->getRequestedAmount());
        $this->assertSame(['data'], $mapper->getData());
        $this->assertSame($basket, $mapper->getBasket());
        $this->assertSame('method', $mapper->getPaymentMethod());
    }

    public function testResponse()
    {
        $respone = $this->createMock(Response::class);
        $respone->method('getRequestId')->willReturn('req-id');
        $respone->method('getTransactionType')->willReturn('auth');
        $respone->method('getRequestedAmount')->willReturn($amount = new Amount(1, 'EUR'));
        $respone->method('getData')->willReturn($data = [
            'provider-transaction-reference-id' => 'prov-trans-ref',
            'payment-methods.0.name'            => 'pay-method',
        ]);
        $respone->method('getBasket')->willReturn($basket = new Basket());

        $mapper = new ResponseMapper($respone);
        $this->assertSame('req-id', $mapper->getRequestId());
        $this->assertSame('auth', $mapper->getTransactionType());
        $this->assertSame(null, $mapper->getTransactionId());
        $this->assertSame(null, $mapper->getParentTransactionId());
        $this->assertSame(null, $mapper->getProviderTransactionId());
        $this->assertSame('prov-trans-ref', $mapper->getProviderTransactionReference());
        $this->assertSame($amount, $mapper->getRequestedAmount());
        $this->assertSame($data, $mapper->getData());
        $this->assertSame($basket, $mapper->getBasket());
        $this->assertSame('pay-method', $mapper->getPaymentMethod());
    }
}
