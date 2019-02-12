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
use Wirecard\PaymentSdk\Transaction\PoiPiaTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * @since 1.2.0
 */
class UnmatchedTransactionMail
{
    /**
     * @var \WirecardEE_PaymentGateway_Helper_Data
     */
    private $paymentHelper;

    /**
     * @param \WirecardEE_PaymentGateway_Helper_Data $paymentHelper
     *
     * @since 1.2.0
     */
    public function __construct(\WirecardEE_PaymentGateway_Helper_Data $paymentHelper)
    {
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Send unmatched transaction e-mail to merchant, if transaction is "authorization" or "purchase" and
     * unmatched_payment_mail has been entered in plugin settings.
     *
     * @param string|null     $mailAddress
     * @param SuccessResponse $notification
     *
     * @return \Zend_Mail|null
     * @throws \Zend_Mail_Exception
     *
     * @since 1.2.0
     */
    public function create(
        $mailAddress,
        SuccessResponse $notification
    ) {
        if (! $mailAddress
            || ! in_array($notification->getTransactionType(), [
                Transaction::TYPE_AUTHORIZATION,
                Transaction::TYPE_PURCHASE,
            ])
        ) {
            return null;
        }

        $mail = new \Zend_Mail();
        $mail->setFrom(
            \Mage::getStoreConfig('trans_email/ident_general/email'),
            \Mage::getStoreConfig('trans_email/ident_general/name')
        );
        $mail->addTo($mailAddress);
        $mail->setSubject($this->getSubject());
        $mail->setBodyText($this->getMessage($notification));
        return $mail;
    }

    /**
     * @return string
     *
     * @since 1.2.0
     */
    private function getSubject()
    {
        return $this->paymentHelper->__('unmatched_payment_mail_subject');
    }

    /**
     * @param SuccessResponse $notification
     *
     * @return string
     *
     * @since 1.2.0
     */
    private function getMessage(SuccessResponse $notification)
    {
        $message = sprintf(
            \Mage::helper('paymentgateway')->__('unmatched_payment_mail_content'),
            PoiPiaTransaction::NAME,
            $notification->getProviderTransactionReference()
        );

        $message .= PHP_EOL . PHP_EOL . print_r($notification->getData(), true);

        return $message;
    }
}
