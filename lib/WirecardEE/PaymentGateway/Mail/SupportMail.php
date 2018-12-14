<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Mail;

use WirecardEE\PaymentGateway\Service\PaymentFactory;

/**
 * @since   1.0.0
 */
class SupportMail
{
    const SUPPORT_MAIL = 'shop-systems-support@wirecard.com';

    /**
     * @var PaymentFactory
     */
    private $paymentFactory;

    /**
     * @param \Enlight_Components_Mail $mail
     * @param EntityManagerInterface   $em
     * @param InstallerService         $installerService
     * @param PaymentFactory           $paymentFactory
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->paymentFactory   = new PaymentFactory();
    }

    /**
     * Sends Email to Wirecard Support
     *
     * @param string                $senderAddress
     * @param string                $message
     * @param string                $replyTo
     *
     * @return \Zend_Mail
     * @throws \Zend_Mail_Exception
     * @throws \WirecardElasticEngine\Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function send($senderAddress, $message, $replyTo = null)
    {
        $message .= PHP_EOL . PHP_EOL . PHP_EOL;
        $message .= '*** Server Info: ***';
        $message .= $this->arrayToText($this->getServerInfo());

        $message .= '*** Shop Info: ***';
        $message .= $this->arrayToText($this->getShopInfo());

        $message .= '*** Plugin Info: ***';
        $message .= $this->arrayToText($this->getPluginInfo());

        $message .= '*** Plugin List: ***';
        $message .= $this->arrayToText($this->getPluginList());

        $mail = \Mage::getModel('core/email');
        $mail->setFrom($senderAddress);
        $mail->setReplyTo($replyTo ?: $senderAddress);
        $mail->setToEmail($this->getRecipientMail());

        $mail->setSubject('Magento support request');
        $mail->setBody($message);

        $mail->setToName('Your Name');
        $mail->send();
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    private function getRecipientMail()
    {
        if (in_array(getenv('SHOPWARE_ENV'), ['dev', 'development', 'testing', 'test'])) {
            return 'test@example.com';
        }

        return self::SUPPORT_MAIL;
    }

    /**
     * Formats array to readable string
     *
     * @param array $array
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function arrayToText($array)
    {
        $result = PHP_EOL;
        foreach ($array as $key => $val) {
            if (is_bool($val)) {
                $result .= $key . ': ' . ($val ? 'true' : 'false') . PHP_EOL;
            } elseif (is_array($val)) {
                $result .= PHP_EOL . '[' . $key . ']';
                $result .= $this->arrayToText($val);
            } else {
                $result .= $key . ': ' . $val . PHP_EOL;
            }
        }
        $result .= PHP_EOL;

        return $result;
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    protected function getServerInfo()
    {
        return [
            'os'     => php_uname(),
            'server' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'unknown',
            'php'    => phpversion(),
        ];
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    protected function getShopInfo()
    {
        return [
            'name'        => 'Magento ' . \Mage::getEdition(),
            'version'     => \Mage::getVersion(),
        ];
    }

    /**
     * @return array
     *
     * @throws \WirecardElasticEngine\Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    protected function getPluginInfo()
    {

        $payments       = $this->paymentFactory->getSupportedPayments();
        $paymentConfigs = [];

        $activePaymentMethods =\Mage::getModel('payment/config')->getActiveMethods();

        $activeWirecardEEMethods = [];
        $wirecardPaymentName = 'wirecardee_paymentgateway_';
        foreach ($activePaymentMethods as $paymentMethod) {
            $paymentInfo = $paymentMethod->get();
            if (strpos($paymentInfo['id'], $wirecardPaymentName) === 0) {
                $activeWirecardEEMethods[] = substr($paymentInfo['id'], strlen($wirecardPaymentName));
            }
        }

        foreach ($payments as $payment) {
            // $paymentModel = $this->em->getRepository(Payment::class)
            //                          ->findOneBy(['name' => $payment->getName()]);

            // if (! $paymentModel) {
            //     continue;
            // }
            $paymentConfigs[$payment->getName()] = array_merge(
                [ 'active'  => in_array($payment->getName(), $activeWirecardEEMethods)],
                $payment->getPaymentConfig()->toArray()
            );
        }

        $modules = \Mage::getConfig()->getNode('modules')->children();

        return [
            'name'     => \Mage::helper('paymentgateway')->getPluginName(),
            'version'  => \Mage::helper('paymentgateway')->getPluginVersion(),
            'payments' => $paymentConfigs,
        ];
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    protected function getPluginList()
    {
        $modules = \Mage::getConfig()->getNode('modules')->children();

        $rows = [];
        foreach ($modules as $key => $plugin) {
            $rows[] = [
                'name'     => $key,
                'version'  => strval($plugin->version),
                'active'   => $plugin->is('active') ? 'Yes' : 'No',
                'codePool' => strval($plugin->codePool),
            ];
        }

        return $rows;
    }
}
