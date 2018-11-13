<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Service;

use Mage_Sales_Model_Order;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\Action;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Payments\PaymentInterface;

class PaymentHandler
{
    /**
     * @param PaymentInterface       $payment
     * @param Mage_Sales_Model_Order $order
     * @param TransactionService     $transactionService
     *
     * @return Action
     */
    public function execute(
        PaymentInterface $payment,
        Mage_Sales_Model_Order $order,
        TransactionService $transactionService
    )
    {
        $response = $transactionService->process(
            $payment->getTransaction(),
            $payment->getPaymentConfig()->getTransactionOperation()
        );

        return new ErrorAction(0, 'Payment processing failed');
    }

    private function prepareTransaction()
    {
    }

    protected function getDescriptor()
    {
        return '';
    }
}
