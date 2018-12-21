<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Mail;

use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * @since 1.0.0
 */
class MerchantNotificationMail
{
    /**
     * @var \WirecardEE_PaymentGateway_Helper_Data
     */
    private $paymentHelper;

    /**
     * @param \WirecardEE_PaymentGateway_Helper_Data $paymentHelper
     *
     * @since 1.0.0
     */
    public function __construct(\WirecardEE_PaymentGateway_Helper_Data $paymentHelper)
    {
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Send payment notification e-mail to merchant, if transaction is "authorization" or "purchase" and
     * notify_mail has been entered in plugin settings.
     *
     * @param SuccessResponse                             $notification
     * @param \Mage_Sales_Model_Order_Payment_Transaction $notifyTransaction
     *
     * @throws \Zend_Mail_Exception
     *
     * @since 1.0.0
     */
    public function send(SuccessResponse $notification, \Mage_Sales_Model_Order_Payment_Transaction $notifyTransaction)
    {
        $notifyMailAddress = \Mage::getStoreConfig('wirecardee_paymentgateway/settings/notify_mail');
        if (! $notifyMailAddress
            || ($notification->getTransactionType() !== Transaction::TYPE_AUTHORIZATION
                && $notification->getTransactionType() !== Transaction::TYPE_PURCHASE)
        ) {
            return;
        }

        $mail = new \Zend_Mail();
        $mail->setFrom(
            \Mage::getStoreConfig('trans_email/ident_general/email'),
            \Mage::getStoreConfig('trans_email/ident_general/name')
        );
        $mail->addTo($notifyMailAddress);
        $mail->setSubject($this->getSubject());
        $mail->setBodyText($this->getMessage($notification, $notifyTransaction));
        $mail->send();
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    private function getSubject()
    {
        return $this->paymentHelper->__('Payment notification received');
    }

    /**
     * @param SuccessResponse                             $notification
     * @param \Mage_Sales_Model_Order_Payment_Transaction $notifyTransaction
     *
     * @return string
     *
     * @since 1.0.0
     */
    private function getMessage(
        SuccessResponse $notification,
        \Mage_Sales_Model_Order_Payment_Transaction $notifyTransaction
    ) {
        $orderNumber     = $notifyTransaction->getOrder()->getId() ?: '-';
        $transactionId   = $notification->getTransactionId();
        $transactionType = $notification->getTransactionType();
        $amount          = $notification->getRequestedAmount()->getValue();
        $currency        = $notification->getRequestedAmount()->getCurrency();

        $orderNumberLabel     = $this->paymentHelper->__('OrderNumber');
        $transactionIdLabel   = $this->paymentHelper->__('TransactionId');
        $transactionTypeLabel = $this->paymentHelper->__('TransactionType');
        $amountLabel          = $this->paymentHelper->__('Amount');

        $message = $orderNumberLabel . ': ' . $orderNumber . PHP_EOL;
        $message .= $transactionIdLabel . ': ' . $transactionId . PHP_EOL;
        $message .= $transactionTypeLabel . ': ' . $transactionType . PHP_EOL;
        $message .= $amountLabel . ': ' . $amount . ' ' . $currency . PHP_EOL;

        $message .= PHP_EOL . PHP_EOL;
        $message .= $this->paymentHelper->__('Response', 'Response data ID') . ': ' . PHP_EOL;
        $message .= print_r($notification->getData(), true);

        return $message;
    }
}
