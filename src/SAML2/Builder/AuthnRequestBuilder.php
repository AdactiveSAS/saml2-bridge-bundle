<?php

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Builder;


class AuthnRequestBuilder extends AbstractRequestBuilder
{
    private $request;

    /**
     * @return void
     */
    protected function createRequestInstance()
    {
        $this->request = new \SAML2_AuthnRequest();
    }

    /**
     * @return \SAML2_Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}