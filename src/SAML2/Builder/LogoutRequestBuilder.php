<?php

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Builder;


class LogoutRequestBuilder extends AbstractRequestBuilder
{
    /**
     * @var \SAML2_LogoutRequest
     */
    protected $request;

    /**
     * @return \SAML2_LogoutRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return void
     */
    protected function createRequestInstance()
    {
        $this->request = new \SAML2_LogoutRequest();
    }

    /**
     * @param $value
     * @param $format
     * @return $this
     */
    public function setNameId($value, $format) {
        $this->request->setNameId([
            "Value" => $value,
            "Format" => $format
        ]);

        return $this;
    }
}