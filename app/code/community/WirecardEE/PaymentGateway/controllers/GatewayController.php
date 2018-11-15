<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use Psr\Log\LoggerInterface;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\Action;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Actions\RedirectAction;
use WirecardEE\PaymentGateway\Data\BasketMapper;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Exception\UnknownActionException;
use WirecardEE\PaymentGateway\Service\PaymentFactory;
use WirecardEE\PaymentGateway\Service\PaymentHandler;
use WirecardEE\PaymentGateway\Service\ReturnHandler;

class WirecardEE_PaymentGateway_GatewayController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $paymentName = $this->getRequest()->getParam('method');
        $payment     = (new PaymentFactory())->create($paymentName);
        $handler     = new PaymentHandler(\Mage::app()->getStore());
        $order       = $this->getCheckoutSession()->getLastRealOrder();

        $action = $handler->execute(
            new OrderSummary(
                $payment,
                $order,
                new BasketMapper($order, $payment->getTransaction()),
                ''
            ),
            new TransactionService(
                $payment->getTransactionConfig(),
                $this->getLogger()
            ),
            new Redirect(
                $this->getUrl('paymentgateway/gateway/return', ['method' => $paymentName]),
                $this->getUrl('paymentgateway/gateway/cancel', ['method' => $paymentName])
            ),
            $this->getUrl('paymentgateway/gateway/notify', ['method' => $paymentName])
        );

        return $this->handleAction($action);
    }

    public function returnAction()
    {
        $returnHandler = new ReturnHandler($this->getLogger());
        $request       = $this->getRequest();
        $payment       = (new PaymentFactory())->create($request->getParam('method'));

        try {
            $response = $returnHandler->handleRequest(
                $request,
                new TransactionService($payment->getTransactionConfig(), $this->getLogger())
            );

            $action = $response instanceof SuccessResponse
                ? $this->updateOrder($returnHandler, $response)
                : $returnHandler->handleResponse($response);
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());
            $action = new ErrorAction(0, 'Return processing failed');
        }

        return $this->handleAction($action);
    }

    public function updateOrder(ReturnHandler $returnHandler, Response $response)
    {
        return $returnHandler->handleSuccess($response, $this->getUrl('checkout/onepage/success'));
    }

    public function notifyAction()
    {
        exit('Notify');
    }

    protected function handleAction(Action $action)
    {
        if ($action instanceof RedirectAction) {
            return $this->_redirectUrl($action->getUrl());
        }

        if ($action instanceof ErrorAction) {
            exit($action->getMessage());
        }

        throw new UnknownActionException(get_class($action));
    }

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return Mage::registry('logger');
    }

    protected function getUrl($route, $params = [])
    {
        return Mage::getUrl($route, $params);
    }

    protected function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }
}
