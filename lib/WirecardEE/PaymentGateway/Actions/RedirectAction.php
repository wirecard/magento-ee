<?php

namespace WirecardEE\PaymentGateway\Actions;

/**
 * Returned by Handlers if a redirect is required.
 */
class RedirectAction implements Action
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @param string $url
     *
     * @since 1.0.0
     */
    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getUrl()
    {
        return $this->url;
    }
}
