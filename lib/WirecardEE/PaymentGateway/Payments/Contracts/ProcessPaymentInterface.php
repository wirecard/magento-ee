<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Payments\Contracts;

use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\Action;
use WirecardEE\PaymentGateway\Data\OrderSummary;

/**
 * @since 1.0.0
 */
interface ProcessPaymentInterface
{
    /**
     * Payment specific processing. This method either returns an `Action` (which is directly returned to the Handler)
     * or `null`. Returning `null` leads to the handler executing the transaction via the `TransactionService`. In case
     * of returning an `Action` execution of the transaction (via the `TransactionService`) probably needs to get
     * called manually within this method.
     *
     * @see   PaymentHandler
     *
     * @param OrderSummary       $orderSummary
     * @param TransactionService $transactionService
     * @param Redirect           $redirect
     *
     * @return Action|null
     *
     * @since 1.0.0
     */
    public function processPayment(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Redirect $redirect
    );
}
