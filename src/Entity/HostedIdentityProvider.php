<?php

namespace AdactiveSas\Saml2BridgeBundle\Entity;


class HostedIdentityProvider extends IdentityProvider
{
    /**
     * @return string
     */
    public function getLoginUrl()
    {
        return $this->get('loginUrl');
    }

    /**
     * @return string
     */
    public function getLogoutUrl()
    {
        return $this->get('logoutUrl');
    }
}