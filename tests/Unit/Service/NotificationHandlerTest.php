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
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
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
}
