<?php

namespace AdactiveSas\Saml2BridgeBundle\Entity;

class IdentityProvider extends \SAML2_Configuration_IdentityProvider
{
    /**
     * @return string
     */
    public function getSsoUrl()
    {
        return $this->get('ssoUrl');
    }

    /**
     * @return string
     */
    public function getSsoBinding(){
        return \SAML2_Const::BINDING_HTTP_REDIRECT;
    }

    /**
     * @return string
     */
    public function getSlsUrl()
    {
        return $this->get('slsUrl');
    }

    /**
     * @return string
     */
    public function getSlsBinding(){
        return \SAML2_Const::BINDING_HTTP_REDIRECT;
    }
}
