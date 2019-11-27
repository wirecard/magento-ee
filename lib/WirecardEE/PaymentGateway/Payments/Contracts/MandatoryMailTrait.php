<?php


namespace WirecardEE\PaymentGateway\Payments\Contracts;


use WirecardEE\PaymentGateway\Exception\InsufficientDataException;

/**
 *
 * Trait MandatoryMailTrait
 *
 * @package WirecardEE\PaymentGateway\Payments\Contracts
 */
trait MandatoryMailTrait
{
    protected $mail;

    /**
     * @throws InsufficientDataException
     */
    protected function validateMail()
    {
        if (empty($this->mail)) {
            throw new InsufficientDataException('Email address is not set');
        }
    }

    /**
     * @return string
     *
     * @throws InsufficientDataException
     */
    protected function getValidatedMail()
    {
        $this->validateMail();

        return $this->mail;
    }

}
