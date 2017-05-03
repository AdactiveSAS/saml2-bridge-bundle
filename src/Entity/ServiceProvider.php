<?php

namespace AdactiveSas\Saml2BridgeBundle\Entity;

class ServiceProvider extends \SAML2_Configuration_ServiceProvider
{
    /**
     * @return string|null
     */
    public function getAssertionConsumerUrl()
    {
        return $this->get('assertionConsumerUrl');
    }

    /**
     * @return string|null
     */
    public function getAssertionConsumerBinding()
    {
        return $this->get('assertionConsumerBinding');
    }

    /**
     * @return string|null
     */
    public function getSingleLogoutUrl(){
        return $this->get('singleLogoutUrl');
    }

    /**
     * @return string|null
     */
    public function getSingleLogoutBinding(){
        return $this->get('singleLogoutBinding');
    }

    public function wantSignedAuthnResponse()
    {
        return $this->get('wantSignedAuthnResponse', true);
    }

    public function wantSignedAssertions()
    {
        return $this->get('wantSignedAssertions', true);
    }

    public function wantSignedLogoutResponse()
    {
        return $this->get('wantSignedLogoutResponse', true);
    }

    public function wantSignedLogoutRequest()
    {
        return $this->get('wantSignedLogoutRequest', true);
    }
}
