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
 * @since 1.0.0
 */
class SupportMail
{
    const SUPPORT_MAIL = 'shop-systems-support@wirecard.com';

    const WHITELISTED_FIELDS = array(
        'baseUrl',
        'transactionMAID',
        'transactionOperation',
        'sendBasket',
        'fraudPrevention',
        'orderIdentification',
        'threeDMAID',
        'threeDMinLimit',
        'threeDMinLimitCurrency',
        'sslMaxLimit',
        'sslMaxLimitCurrency',
        'vaultEnabled',
        'allowAddressChanges',
        'threeDUsageOnTokens',
        'creditorStreet',
        'creditorZip',
        'creditorCity',
        'creditorCountry',
        'backendTransactionMaid',
        'minAmount',
        'maxAmount',
        'acceptedCurrencies',
        'shippingCountries',
        'billingCountries',
        'allowDifferentBillingShipping'
    );

    /**
     * @var PaymentFactory
     */
    private $paymentFactory;

    /**
     * @param PaymentFactory $paymentFactory
     *
     * @since 1.0.0
     */
    public function __construct(PaymentFactory $paymentFactory)
    {
        $this->paymentFactory = $paymentFactory;
    }

    /**
     * Sends Email to Wirecard Support
     *
     * @param string $senderAddress
     * @param string $message
     * @param string $replyTo
     *
     * @return \Zend_Mail
     * @throws \WirecardEE\PaymentGateway\Exception\UnknownPaymentException
     * @throws \Zend_Mail_Exception
     *
     * @since 1.0.0
     */
    public function create($senderAddress, $message, $replyTo = null)
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

        $mail = new \Zend_Mail();
        $mail->setFrom($senderAddress);
        $mail->addTo($this->getRecipientMail(), 'Wirecard Support');
        if ($replyTo) {
            $mail->setReplyTo($replyTo);
        }
        $mail->setSubject('Magento support request');
        $mail->setBodyText($message);
        return $mail;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    private function getRecipientMail()
    {
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
            'os' => php_uname(),
            'server' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'unknown',
            'php' => phpversion(),
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
            'name' => 'Magento ' . \Mage::getEdition(),
            'version' => \Mage::getVersion(),
        ];
    }

    /**
     * @return array
     * @throws \WirecardEE\PaymentGateway\Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    protected function getPluginInfo()
    {
        /** @var \Mage_Payment_Model_Config $paymentConfig */
        $paymentConfig = \Mage::getModel('payment/config');
        $paymentName = 'wirecardee_paymentgateway_';
        $activeMethods = [];

        /** @var \WirecardEE_PaymentGateway_Model_Payment $paymentMethod */
        foreach ($paymentConfig->getActiveMethods() as $paymentMethod) {
            $paymentInfo = $paymentMethod->getData();
            if (strpos($paymentInfo['id'], $paymentName) === 0) {
                $activeMethods[] = substr($paymentInfo['id'], strlen($paymentName));
            }
        }

        /** @var \WirecardEE_PaymentGateway_Helper_Data $paymentHelper */
        $paymentHelper = \Mage::helper('paymentgateway');
        return [
            'name' => $paymentHelper->getPluginName(),
            'version' => $paymentHelper->getPluginVersion(),
            'payments' => $this->getWhitelistedConfigFields($activeMethods),
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
        $plugins = [];

        /** @var \Mage_Core_Model_Config_Element $plugin */
        foreach ($modules as $key => $plugin) {
            $plugins[] = [
                'name' => $key,
                'version' => isset($plugin->version) ? (string)$plugin->version : null,
                'active' => $plugin->is('active') ? 'Yes' : 'No',
                'codePool' => isset($plugin->codePool) ? (string)$plugin->codePool : null,
            ];
        }

        return $plugins;
    }

    /**
     * Returns array of whitelisted payment configuration fields
     *
     * @param $activeMethods
     * @return array
     * @throws \WirecardEE\PaymentGateway\Exception\UnknownPaymentException
     * @since 2.0.1
     */
    private function getWhitelistedConfigFields($activeMethods)
    {
        $paymentConfigs = [];
        foreach ($this->paymentFactory->getSupportedPayments() as $payment) {
            $configFields = $payment->getPaymentConfig()->toArray();
            $paymentConfigs[$payment->getName()] = array_intersect_key(
                $configFields,
                array_flip(self::WHITELISTED_FIELDS)
            );
            $paymentConfigs[$payment->getName()]['active'] = in_array($payment->getName(), $activeMethods);
        }

        return $paymentConfigs;
    }
}
