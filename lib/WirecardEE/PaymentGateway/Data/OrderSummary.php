<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Data;

use Wirecard\PaymentSdk\Entity\Device;
use WirecardEE\PaymentGateway\Mapper\AccountInfoMapper;
use WirecardEE\PaymentGateway\Mapper\BasketMapper;
use WirecardEE\PaymentGateway\Mapper\RiskInfoMapper;
use WirecardEE\PaymentGateway\Mapper\UserMapper;
use WirecardEE\PaymentGateway\Payments\PaymentInterface;

/**
 * The `OrderSummary` is passed to the `PaymentHandler` which processes the actual payment, based on the information
 * stored in the summary (e.g. used payment method, consumer information, etc..).
 *
 * @since 1.0.0
 */
class OrderSummary
{
    /**
     * @var PaymentInterface
     */
    protected $payment;

    /**
     * @var \Mage_Sales_Model_Order
     */
    protected $order;

    /**
     * @var string
     */
    protected $deviceFingerprintId;

    /**
     * @var BasketMapper
     */
    protected $basketMapper;

    /**
     * @var UserMapper
     */
    protected $userMapper;

    /**
     * @var AccountInfoMapper
     * @since 1.2.4
     */
    protected $accountInfoMapper;

    /**
     * @var RiskInfoMapper
     * @since 1.2.4
     */
    protected $riskInfoMapper;
    /**
     * @var array
     */
    protected $additionalPaymentData;

    /**
     * @param PaymentInterface $payment
     * @param \Mage_Sales_Model_Order $order
     * @param BasketMapper $basketMapper
     * @param UserMapper $userMapper
     * @param AccountInfoMapper $accountInfoMapper
     * @param RiskInfoMapper $riskInfoMapper
     * @param string $deviceFingerprintId
     * @param array $additionalPaymentData
     *
     * @since 1.0.0
     */
    public function __construct(
        PaymentInterface $payment,
        \Mage_Sales_Model_Order $order,
        BasketMapper $basketMapper,
        UserMapper $userMapper,
        AccountInfoMapper $accountInfoMapper,
        RiskInfoMapper $riskInfoMapper,
        $deviceFingerprintId,
        $additionalPaymentData = []
    ) {
        $this->payment               = $payment;
        $this->order                 = $order;
        $this->deviceFingerprintId   = $deviceFingerprintId;
        $this->basketMapper          = $basketMapper;
        $this->userMapper            = $userMapper;
        $this->accountInfoMapper     = $accountInfoMapper;
        $this->riskInfoMapper        = $riskInfoMapper;
        $this->additionalPaymentData = $additionalPaymentData;
    }

    /**
     * @return \Mage_Sales_Model_Order
     *
     * @since 1.0.0
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return PaymentInterface
     *
     * @since 1.0.0
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @return BasketMapper
     *
     * @since 1.0.0
     */
    public function getBasketMapper()
    {
        return $this->basketMapper;
    }

    /**
     * @return UserMapper
     *
     * @since 1.0.0
     */
    public function getUserMapper()
    {
        return $this->userMapper;
    }

    /**
     * @return AccountInfoMapper
     *
     * @since 1.2.4
     */
    public function getAccountInfoMapper()
    {
        return $this->accountInfoMapper;
    }

    /**
     * @return RiskInfoMapper
     *
     * @since 1.2.4
     */
    public function getRiskInfoMapper()
    {
        return $this->riskInfoMapper;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getDeviceFingerprintId()
    {
        return $this->deviceFingerprintId;
    }

    /**
     * @return Device
     *
     * @since 1.0.0
     */
    public function getWirecardDevice()
    {
        $device = new Device();
        $device->setFingerprint($this->getDeviceFingerprintId());
        return $device;
    }

    /**
     * @return array
     */
    public function getAdditionalPaymentData()
    {
        return $this->additionalPaymentData;
    }
}
