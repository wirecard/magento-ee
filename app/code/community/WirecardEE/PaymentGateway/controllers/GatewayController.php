<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use WirecardEE\PaymentGateway\Actions\Action;
use WirecardEE\PaymentGateway\Service\Logger;
use WirecardEE\PaymentGateway\Service\PaymentFactory;
use WirecardEE\PaymentGateway\Service\PaymentHandler;

class WirecardEE_PaymentGateway_GatewayController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $paymentName  = $this->getRequest()->getParam('method');
        $payment      = (new PaymentFactory())->create($paymentName);
        $handler      = new PaymentHandler();

        $action = $handler->execute(
            $payment,
            Mage::getSingleton('checkout/session')->getLastRealOrder(),
            new \Wirecard\PaymentSdk\TransactionService(
                $payment->getTransactionConfig(),
                new Logger()
            )
        );

        return $this->handleAction($action);
    }

    protected function handleAction(Action $action)
    {
    }
}
