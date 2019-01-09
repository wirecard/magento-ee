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
     * @param string|null                                 $notifyMailAddress
     * @param SuccessResponse                             $notification
     * @param \Mage_Sales_Model_Order_Payment_Transaction $notifyTransaction
     *
     * @return \Zend_Mail|null
     * @throws \Zend_Mail_Exception
     *
     * @since 1.0.0
     */
    public function create(
        $notifyMailAddress,
        SuccessResponse $notification,
        \Mage_Sales_Model_Order_Payment_Transaction $notifyTransaction
    ) {
        if (! $notifyMailAddress
            || ($notification->getTransactionType() !== Transaction::TYPE_AUTHORIZATION
                && $notification->getTransactionType() !== Transaction::TYPE_PURCHASE)
        ) {
            return null;
        }

        $mail = new \Zend_Mail();
        $mail->setFrom(
            \Mage::getStoreConfig('trans_email/ident_general/email'),
            \Mage::getStoreConfig('trans_email/ident_general/name')
        );
        $mail->addTo($notifyMailAddress);
        $mail->setSubject($this->getSubject());
        $mail->setBodyText($this->getMessage($notification, $notifyTransaction));
        return $mail;
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
        $amount  = $notification->getRequestedAmount();
        $infos   = [
            'OrderNumber'     => $notifyTransaction->getOrder()->getRealOrderId() ?: '-',
            'TransactionId'   => $notification->getTransactionId(),
            'TransactionType' => $notification->getTransactionType(),
            'Amount'          => $amount->getValue() . ' ' . $amount->getCurrency(),
        ];
        $message = '';
        foreach ($infos as $label => $value) {
            $message .= $this->paymentHelper->__($label) . ': ' . $value . PHP_EOL;
        }

        $message .= PHP_EOL . PHP_EOL;
        $message .= $this->paymentHelper->__('Response', 'Response data ID') . ': ' . PHP_EOL;
        $message .= print_r($notification->getData(), true);

        return $message;
    }
}
