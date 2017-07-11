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

    /**
     * @return string|null
     */
    public function getNameIdFormat(){
        return $this->get('nameIdFormat');
    }

    /**
     * @return string|null
     */
    public function getAttributes(){
        return $this->get('attributes');
    }

    /**
     * @return string|null
     */
    public function getNameQualifier(){
        return $this->get('NameQualifier');
    }

    /**
     * @return bool
     */
    public function isAuthnRequestSignRequired()
    {
        return $this->get('signAuthnRequestEnable', true);
    }

    /**
     * @return bool
     */
    public function isResponseSign()
    {
        return $this->get('signResponse', true);
    }

    /**
     * @return bool
     */
    public function isAssertionSign()
    {
        return $this->get('signAssertion', false);
    }
}
