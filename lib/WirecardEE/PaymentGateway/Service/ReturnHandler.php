<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Service;

use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\Action;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Actions\RedirectAction;
use WirecardEE\PaymentGateway\Actions\ViewAction;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessReturnInterface;
use WirecardEE\PaymentGateway\Payments\PaymentInterface;

/**
 * Responsible for handling return actions. Payments may implement their own way of handling returns by implementing
 * the `ProcessReturnInterface` interface.
 *
 * @since 1.0.0
 */
class ReturnHandler extends Handler
{
    /**
     * @param PaymentInterface                   $payment
     * @param \Mage_Core_Controller_Request_Http $request
     * @param TransactionService                 $transactionService
     *
     * @return FailureResponse|InteractionResponse|Response|SuccessResponse
     *
     * @since 1.0.0
     */
    public function handleRequest(
        PaymentInterface $payment,
        \Mage_Core_Controller_Request_Http $request,
        TransactionService $transactionService
    ) {
        if ($payment instanceof ProcessReturnInterface) {
            $response = $payment->processReturn($transactionService, $request);
            if ($response) {
                return $response;
            }
        }

        $params = $request->getParams();
        if (! empty($params['jsresponse'])) {
            return $transactionService->processJsResponse(
                $request->getParams(),
                \Mage::getUrl('paymentgateway/gateway/return', [
                    'method' => $payment->getName(),
                ])
            );
        }

        return $transactionService->handleResponse($request->getParams());
    }

    /**
     * @param Response                $response
     * @param \Mage_Sales_Model_Order $order
     *
     * @return Action
     *
     * @throws \Mage_Core_Exception
     * @since 1.0.0
     */
    public function handleResponse(Response $response, \Mage_Sales_Model_Order $order)
    {
        if ($response instanceof FormInteractionResponse) {
            $this->transactionManager->createTransaction(TransactionManager::TYPE_RETURN, $order, $response);

            return new ViewAction('paymentgateway/redirect', [
                'method'     => $response->getMethod(),
                'formFields' => $response->getFormFields(),
                'url'        => $response->getUrl(),
            ]);
        }
        if ($response instanceof InteractionResponse) {
            $this->transactionManager->createTransaction(TransactionManager::TYPE_RETURN, $order, $response);

            return new RedirectAction($response->getRedirectUrl());
        }
        return $this->handleFailure($response);
    }

    /**
     * @param $response
     *
     * @return ErrorAction
     *
     * @since 1.0.0
     */
    protected function handleFailure($response)
    {
        if ($response instanceof FailureResponse) {
            $orderId = $response->getCustomFields()->get('order-id');
            if ($orderId) {
                /** @var \Mage_Sales_Model_Order $order */
                $order = \Mage::getModel('sales/order')->load($orderId);
                if ($order) {
                    $order->addStatusHistoryComment(
                        'Canceled due to failure response',
                        \Mage_Sales_Model_Order::STATE_CANCELED
                    );
                }
            }
        }

        $message = 'Unexpected response';
        $context = [get_class($response)];

        if ($response instanceof FailureResponse) {
            $message = 'Failure response';
        }
        if ($response instanceof Response) {
            $context = $response->getData();
        }

        $this->logger->error('Return handling failed: ' . $message, $context);
        return new ErrorAction(ErrorAction::FAILURE_RESPONSE, $message);
    }
}
