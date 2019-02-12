<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Mapper;

use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;

/**
 * Maps the Wirecard Response object to make it easier to access certain properties, since you can't be sure about
 * availability of these properties depending on the response type (e.g. there's no parent transaction id on the
 * `InteractionResponse` but the `SuccessResponse` provides it).
 * Additionally some values may be specified within the `Response` but the response type does not provide a proper
 * getter (e.g. provider transaction reference id).
 *
 * @since 1.0.0
 */
class ResponseMapper
{
    /**
     * @var Response
     */
    protected $response;

    /**
     * @param Response $response
     *
     * @since 1.0.0
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getRequestId()
    {
        return $this->response->getRequestId();
    }

    /**
     * @return mixed
     *
     * @since 1.0.0
     */
    public function getTransactionType()
    {
        return $this->response->getTransactionType();
    }

    /**
     * @return null|string
     *
     * @since 1.0.0
     */
    public function getTransactionId()
    {
        if ($this->response instanceof SuccessResponse
            || $this->response instanceof InteractionResponse
            || $this->response instanceof FormInteractionResponse
        ) {
            return $this->response->getTransactionId();
        }

        return null;
    }

    /**
     * @return null|string
     *
     * @since 1.0.0
     */
    public function getParentTransactionId()
    {
        if ($this->response instanceof SuccessResponse) {
            return $this->response->getParentTransactionId();
        }

        return null;
    }

    /**
     * @return null|string
     *
     * @since 1.0.0
     */
    public function getProviderTransactionId()
    {
        if ($this->response instanceof SuccessResponse) {
            return $this->response->getProviderTransactionId();
        }

        return null;
    }

    /**
     * @return null|string
     *
     * @since 1.0.0
     */
    public function getProviderTransactionReferenceId()
    {
        if ($this->response instanceof SuccessResponse) {
            return $this->response->getProviderTransactionReference();
        }
        return $this->getFromData('provider-transaction-reference-id');
    }

    /**
     * @return Amount
     *
     * @since 1.0.0
     */
    public function getRequestedAmount()
    {
        return $this->response->getRequestedAmount();
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getData()
    {
        return $this->response->getData();
    }

    /**
     * @return Basket
     *
     * @since 1.0.0
     */
    public function getBasket()
    {
        return $this->response->getBasket();
    }

    /**
     * @return null|string
     *
     * @since 1.0.0
     */
    public function getPaymentMethod()
    {
        if ($this->response instanceof SuccessResponse) {
            return $this->response->getPaymentMethod();
        }
        return $this->getFromData('payment-methods.0.name');
    }

    /**
     * @return null|string
     *
     * @since 1.1.0
     */
    public function getOrderNumber()
    {
        return $this->getFromData('order-number');
    }

    /**
     * @return string|null
     *
     * @since 1.2.0
     */
    public function getMerchantIban()
    {
        return $this->getFromData('merchant-bank-account.0.iban');
    }

    /**
     * @return string|null
     *
     * @since 1.2.0
     */
    public function getMerchantBic()
    {
        return $this->getFromData('merchant-bank-account.0.bic');
    }

    /**
     * @return string|null
     *
     * @since 1.2.0
     */
    public function getMerchantBankName()
    {
        return $this->getFromData('merchant-bank-account.0.bank-name');
    }

    /**
     * @return string|null
     *
     * @since 1.2.0
     */
    public function getMerchantBankAddress()
    {
        return $this->getFromData('merchant-bank-account.0.branch-address');
    }

    /**
     * @return string|null
     *
     * @since 1.2.0
     */
    public function getMerchantBankCity()
    {
        return $this->getFromData('merchant-bank-account.0.branch-city');
    }

    /**
     * @return string|null
     *
     * @since 1.2.0
     */
    public function getMerchantBankState()
    {
        return $this->getFromData('merchant-bank-account.0.branch-state');
    }

    /**
     * @return array
     *
     * @since 1.2.0
     */
    public function getBankData()
    {
        return [
            'bank_label' => $this->getMerchantBankName(),
            'iban'       => $this->getMerchantIban(),
            'bic'        => $this->getMerchantBic(),
            'address'    => $this->getMerchantBankAddress(),
            'city'       => $this->getMerchantBankCity(),
            'state'      => $this->getMerchantBankState(),
            'ptrid'      => $this->getProviderTransactionReferenceId(),
        ];
    }

    /**
     * @param string $key
     *
     * @return string|null
     *
     * @since 1.2.0
     */
    private function getFromData($key)
    {
        $data = $this->getData();
        if (! is_array($data)) {
            return null;
        }
        if (! isset($data[$key])) {
            return null;
        }
        return $data[$key];
    }
}
