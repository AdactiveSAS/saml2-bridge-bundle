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
}
