<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Service;

use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use WirecardEE\PaymentGateway\Service\Logger;
use WirecardEE\PaymentGateway\Service\NotificationHandler;
use WirecardEE\PaymentGateway\Service\TransactionManager;
use WirecardEE\Tests\Test\MagentoTestCase;

class NotificationHandlerTest extends MagentoTestCase
{
    public function testHandleResponse()
    {
        $transactionManager = $this->createMock(TransactionManager::class);

        $failureResponse = $this->createMock(FailureResponse::class);
        $backendService  = $this->createMock(BackendService::class);

        $notificationHandler = new NotificationHandler($transactionManager, new Logger());
        $this->assertNull($notificationHandler->handleResponse($failureResponse, $backendService));

        $unexpectedResponse = $this->createMock(InteractionResponse::class);
        $this->assertNull($notificationHandler->handleResponse($unexpectedResponse, $backendService));
    }

    public function testHandleSuccessWithInvalidOrderId()
    {
        $transactionManager = $this->createMock(TransactionManager::class);

        $backendService  = $this->createMock(BackendService::class);
        $successResponse = $this->createMock(SuccessResponse::class);
        $successResponse->method('getCustomFields')->willReturn(new CustomFieldCollection());

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('load')->willReturnSelf();
        $this->replaceMageModel('sales/order', $order);

        $notificationHandler = new NotificationHandler($transactionManager, new Logger());

        $this->expectException(\Exception::class);

        $notificationHandler->handleResponse($successResponse, $backendService);
    }

    public function testHandleSuccessWithOrderNumberFromResponse()
    {
        $transactionManager = $this->createMock(TransactionManager::class);

        $backendService  = $this->createMock(BackendService::class);
        $successResponse = $this->createMock(SuccessResponse::class);
        $successResponse->method('getCustomFields')->willReturn(new CustomFieldCollection());
        $successResponse->method('getData')->willreturn(['order-number' => 1]);

        $order = $this->createMock(\Mage_Sales_Model_Order::class);
        $order->method('load')->willReturnSelf();
        $order->method('getId')->willReturn(null);
        $order->method('getAllVisibleItems')->willReturn([]);
        $order->expects($this->once())->method('loadByIncrementId')->willReturnSelf();
        $this->replaceMageModel('sales/order', $order);

        $notificationHandler = new NotificationHandler($transactionManager, new Logger());
        $notificationHandler->handleResponse($successResponse, $backendService);
    }
}
