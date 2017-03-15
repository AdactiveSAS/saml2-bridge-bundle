<?php

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Builder;


class LogoutResponseBuilder extends AbstractResponseBuilder
{
    /**
     * @var \SAML2_LogoutResponse
     */
    protected $response;

    /**
     * @return \SAML2_LogoutResponse
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return void
     */
    protected function createResponseInstance()
    {
        $this->response = new \SAML2_LogoutResponse();
    }
}