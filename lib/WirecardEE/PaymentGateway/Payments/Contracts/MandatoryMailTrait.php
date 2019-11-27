<?php


namespace WirecardEE\PaymentGateway\Payments\Contracts;

use WirecardEE\PaymentGateway\Exception\InsufficientDataException;

/**
 * Trait MandatoryMailTrait
 *
 * @package WirecardEE\PaymentGateway\Payments\Contracts
 */
trait MandatoryMailTrait
{
    /** @var string $mail */
    protected $mail;

    /**
     * Validate mail property
     * Throw exception if empty
     *
     * @throws InsufficientDataException
     */
    protected function validateMail()
    {
        if (empty($this->mail)) {
            throw new InsufficientDataException('Email address is not set');
        }
    }

    /**
     * Return validated mail
     *
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
