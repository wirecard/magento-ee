<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use Psr\Log\LoggerInterface;
use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\Action;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Actions\RedirectAction;
use WirecardEE\PaymentGateway\Data\BasketMapper;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Data\UserMapper;
use WirecardEE\PaymentGateway\Exception\UnknownActionException;
use WirecardEE\PaymentGateway\Service\NotificationHandler;
use WirecardEE\PaymentGateway\Service\PaymentFactory;
use WirecardEE\PaymentGateway\Service\PaymentHandler;
use WirecardEE\PaymentGateway\Service\ReturnHandler;
use WirecardEE\PaymentGateway\Service\TransactionManager;

class WirecardEE_PaymentGateway_GatewayController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $paymentName = $this->getRequest()->getParam('method');
        $payment     = (new PaymentFactory())->create($paymentName);
        $handler     = new PaymentHandler(\Mage::app()->getStore(), $this->getLogger());
        $order       = $this->getCheckoutSession()->getLastRealOrder();

        $this->getHelper()->validateBasket();

        $action = $handler->execute(
            new TransactionManager($this->getLogger()),
            new OrderSummary(
                $payment,
                $order,
                new BasketMapper($order, $payment->getTransaction()),
                new UserMapper(
                    $order,
                    Mage::helper('paymentgateway')->getClientIp(),
                    Mage::app()->getLocale()->getLocaleCode()
                ),
                $this->getHelper()->getDeviceFingerprintId($payment->getPaymentConfig()->getTransactionMAID())
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

        $this->getHelper()->validateBasket();

        try {
            $response = $returnHandler->handleRequest(
                $request,
                new TransactionService($payment->getTransactionConfig(), $this->getLogger())
            );

            $transactionManager = new TransactionManager($this->getLogger());
            $transactionManager->createTransaction(
                $this->getCheckoutSession()->getLastRealOrder(),
                $response
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
        $this->getHelper()->destroyDeviceFingerprintId();

        return $returnHandler->handleSuccess($response, $this->getUrl('checkout/onepage/success'));
    }

    public function notifyAction()
    {
        $notificationHandler = new NotificationHandler($this->getLogger());
        $request             = $this->getRequest();
        $payment             = (new PaymentFactory())->create($request->getParam('method'));

        try {
            $backendService = new BackendService($payment->getTransactionConfig());
            $notification   = $backendService->handleNotification($request->getRawBody());

            $notificationHandler->handleResponse($notification, $backendService);
        } catch (\Exception $e) {
            $this->logException('Notification handling failed', $e);
        }
    }

    public function cancelAction()
    {
        if ($order = $this->getCheckoutSession()->getLastRealOrder()) {
            if ($order->getStatus() === Mage_Sales_Model_Order::STATE_CANCELED) {
                return;
            }

            $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());

            if ($quote->getId()) {
                $quote->setIsActive(1)
                      ->setReservedOrderId(null)
                      ->save();
                $this->getCheckoutSession()->replaceQuote($quote);
            }
        }

        return $this->_redirect('checkout/onepage');
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

    private function logException($message, \Exception $exception)
    {
        $this->getLogger()->error(
            $message . ' - ' . get_class($exception) . ': ' . $exception->getMessage()
        );
    }

    protected function getOrderPaymentTransaction()
    {
        return Mage::getModel('sales/order_payment_transaction');
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

    protected function getHelper()
    {
        return Mage::helper('paymentgateway');
    }

    protected function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }
}
