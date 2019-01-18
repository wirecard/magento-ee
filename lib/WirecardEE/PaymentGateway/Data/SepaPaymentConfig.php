<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Data;

/**
 * SEPA specific payment configuration.
 *
 * @since   1.0.0
 */
class SepaPaymentConfig extends SepaCreditTransferPaymentConfig
{
    /**
     * @var bool
     */
    protected $showBic;

    /**
     * @var string
     */
    protected $creditorId;

    /**
     * @var string
     */
    protected $creditorName;

    /**
     * @var string
     */
    protected $creditorStreet;

    /**
     * @var string
     */
    protected $creditorZip;
    /**
     * @var string
     */
    protected $creditorCity;
    /**
     * @var string
     */
    protected $creditorCountry;

    /**
     * @param bool $showBic
     *
     * @since 1.0.0
     */
    public function setShowBic($showBic)
    {
        $this->showBic = $showBic;
    }

    /**
     * @return bool if true, the BIC form field on checkout page will be displayed
     *
     * @since 1.0.0
     */
    public function showBic()
    {
        return (bool)$this->showBic;
    }

    /**
     * @param string
     *
     * @since 1.0.0
     */
    public function setCreditorId($creditorId)
    {
        $this->creditorId = $creditorId;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getCreditorId()
    {
        return $this->creditorId;
    }

    /**
     * @param string
     *
     * @since 1.0.0
     */
    public function setCreditorName($creditorName)
    {
        $this->creditorName = $creditorName;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getCreditorName()
    {
        return $this->creditorName;
    }

    /**
     * @param string
     *
     * @since 1.0.0
     */
    public function setCreditorStreet($creditorStreet)
    {
        $this->creditorStreet = $creditorStreet;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getCreditorStreet()
    {
        return $this->creditorStreet;
    }

    /**
     * @param string
     *
     * @since 1.0.0
     */
    public function setCreditorZip($creditorZip)
    {
        $this->creditorZip = $creditorZip;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getCreditorZip()
    {
        return $this->creditorZip;
    }

    /**
     * @param string
     *
     * @since 1.0.0
     */
    public function setCreditorCity($creditorCity)
    {
        $this->creditorCity = $creditorCity;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getCreditorCity()
    {
        return $this->creditorCity;
    }

    /**
     * @param string
     *
     * @since 1.0.0
     */
    public function setCreditorCountry($creditorCountry)
    {
        $this->creditorCountry = $creditorCountry;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getCreditorCountry()
    {
        return $this->creditorCountry;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'showBic'                => $this->showBic(),
                'creditorId'             => $this->getCreditorId(),
                'creditorName'           => $this->getCreditorName(),
                'creditorStreet'         => $this->getCreditorStreet(),
                'creditorZip'            => $this->getCreditorZip(),
                'creditorCity'           => $this->getCreditorCity(),
                'creditorCountry'        => $this->getCreditorCountry(),
                'backendTransactionMaid' => $this->getBackendTransactionMAID(),
                'backendCreditorId'      => $this->getBackendCreditorId(),
            ]
        );
    }
}
