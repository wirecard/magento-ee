<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Service;

use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Actions\RedirectAction;
use WirecardEE\PaymentGateway\Actions\ViewAction;
use WirecardEE\PaymentGateway\Service\Logger;
use WirecardEE\PaymentGateway\Service\ReturnHandler;
use WirecardEE\PaymentGateway\Service\TransactionManager;
use WirecardEE\Tests\Test\MagentoTestCase;

class ReturnHandlerTest extends MagentoTestCase
{
    public function testHandleFormInteractionResponse()
    {
        $transactionManager = $this->createMock(TransactionManager::class);

        $response = $this->createMock(FormInteractionResponse::class);
        $order = $this->createMock(\Mage_Sales_Model_Order::class);

        $returnHandler = new ReturnHandler($transactionManager, new Logger());
        $this->assertInstanceOf(ViewAction::class, $returnHandler->handleResponse($response, $order));
    }

    public function testHandleInteractionResponse()
    {
        $transactionManager = $this->createMock(TransactionManager::class);

        $response = $this->createMock(InteractionResponse::class);
        $order = $this->createMock(\Mage_Sales_Model_Order::class);

        $returnHandler = new ReturnHandler($transactionManager, new Logger());
        $this->assertInstanceOf(RedirectAction::class, $returnHandler->handleResponse($response, $order));
    }

    public function testHandleFailureResponse()
    {
        $transactionManager = $this->createMock(TransactionManager::class);

        $response = $this->createMock(FailureResponse::class);
        $response->method('getCustomFields')->willReturn(new CustomFieldCollection());
        $response->method('getData')->willReturn([]);
        $order = $this->createMock(\Mage_Sales_Model_Order::class);

        $returnHandler = new ReturnHandler($transactionManager, new Logger());
        $this->assertInstanceOf(ErrorAction::class, $returnHandler->handleResponse($response, $order));
    }
}
