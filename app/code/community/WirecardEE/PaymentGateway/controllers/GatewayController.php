<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\Action;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Actions\RedirectAction;
use WirecardEE\PaymentGateway\Data\BasketMapper;
use WirecardEE\PaymentGateway\Data\OrderSummary;
use WirecardEE\PaymentGateway\Exception\UnknownActionException;
use WirecardEE\PaymentGateway\Service\PaymentFactory;
use WirecardEE\PaymentGateway\Service\PaymentHandler;

class WirecardEE_PaymentGateway_GatewayController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $paymentName  = $this->getRequest()->getParam('method');
        $payment      = (new PaymentFactory())->create($paymentName);
        $handler      = new PaymentHandler();
        $order        = $this->getCheckoutSession()->getLastRealOrder();

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
                $this->getUrl('paymentgateway/gateway/redirect'),
                $this->getUrl('paymentgateway/gateway/cancel')
            ),
            $this->getUrl('paymentgateway/gateway/notify')
        );

        return $this->handleAction($action);
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

    public function redirectAction()
    {
        exit('Welcome back!');
    }

    public function notifyAction()
    {
        exit('Notify');
    }

    protected function getLogger()
    {
        return Mage::registry('logger');
    }

    protected function getUrl($route)
    {
        return Mage::getUrl($route);
    }

    protected function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }
}
