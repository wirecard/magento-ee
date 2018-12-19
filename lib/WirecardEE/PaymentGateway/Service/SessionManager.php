<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Service;

/**
 * @since 1.0.0
 */
class SessionManager
{
    const PAYMENT_DATA = 'WirecardElasticEnginePaymentData';

    private $session;

    /**
     * @param \Mage_Core_Model_Session $session
     *
     * @since 1.0.0
     */
    public function __construct(\Mage_Core_Model_Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param $paymentData
     *
     * @since 1.0.0
     */
    public function storePaymentData($paymentData)
    {
        $this->session->setData(self::PAYMENT_DATA, $paymentData);
    }
    /**
     * @return array|null
     *
     * @since 1.0.0
     */
    public function getPaymentData()
    {
        if (! $this->session->getData(self::PAYMENT_DATA)) {
            return null;
        }
        return $this->session->getData(self::PAYMENT_DATA);
    }
}
