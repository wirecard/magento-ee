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
use WirecardEE\PaymentGateway\Mapper\BasketMapper;
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
     * @param PaymentInterface        $payment
     * @param \Mage_Sales_Model_Order $order
     * @param BasketMapper            $basketMapper
     * @param UserMapper              $userMapper
     * @param string                  $deviceFingerprintId
     *
     * @since 1.0.0
     */
    public function __construct(
        PaymentInterface $payment,
        \Mage_Sales_Model_Order $order,
        BasketMapper $basketMapper,
        UserMapper $userMapper,
        $deviceFingerprintId
    ) {
        $this->payment             = $payment;
        $this->order               = $order;
        $this->deviceFingerprintId = $deviceFingerprintId;
        $this->basketMapper        = $basketMapper;
        $this->userMapper          = $userMapper;
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
     * @return string
     *
     * @since 1.0.0
     */
    public function getCurrency()
    {
        return $this->order->getBaseCurrencyCode();
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getAmount()
    {
        return number_format($this->order->getBaseGrandTotal(), 2);
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
}
