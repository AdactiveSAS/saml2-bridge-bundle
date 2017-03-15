<?php

namespace AdactiveSas\Saml2BridgeBundle\Exception;


class InvalidSamlRequestException extends LogicException
{
    /**
     * @var string
     */
    protected $samlStatusCode;

    /**
     * InvalidSamlRequestException constructor.
     * @param string $msg
     * @param string $samlStatusCode
     * @param \Exception $previous
     */
    public function __construct($msg, $samlStatusCode, \Exception $previous  = null)
    {
        parent::__construct($msg, 0, $previous);
        $this->samlStatusCode = $samlStatusCode;
    }

    /**
     * @return string
     */
    public function getSamlStatusCode()
    {
        return $this->samlStatusCode;
    }
}