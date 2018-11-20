<?php

namespace WirecardEE\PaymentGateway\Payments\Contracts;

use WirecardEE\PaymentGateway\Data\OrderSummary;

interface ProcessPaymentInterface
{
    public function processPayment(OrderSummary $orderSummary);
}
