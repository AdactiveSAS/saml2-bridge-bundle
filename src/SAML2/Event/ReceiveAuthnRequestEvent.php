<?php

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Event;

use AdactiveSas\Saml2BridgeBundle\Entity\HostedIdentityProvider;
use AdactiveSas\Saml2BridgeBundle\SAML2\State\SamlStateHandler;
use Symfony\Component\EventDispatcher\Event;

class ReceiveAuthnRequestEvent extends Event
{
    /**
     * @var HostedIdentityProvider
     */
    protected $hostedIdentityProvider;

    /**
     * @var SamlStateHandler
     */
    protected $samlStateHandler;

    /**
     * @var \SAML2_AuthnRequest
     */
    protected $authRequest;

    /**
     * AuthenticationEvent constructor.
     *
     * @param \SAML2_AuthnRequest $authRequest
     * @param HostedIdentityProvider $hostedIdentityProvider
     * @param SamlStateHandler $samlStateHandler
     */
    public function __construct(
        \SAML2_AuthnRequest $authRequest,
        HostedIdentityProvider $hostedIdentityProvider,
        SamlStateHandler $samlStateHandler
    ) {
        $this->hostedIdentityProvider = $hostedIdentityProvider;
        $this->samlStateHandler = $samlStateHandler;
        $this->authRequest = $authRequest;
    }

    /**
     * @return HostedIdentityProvider
     */
    public function getHostedIdentityProvider()
    {
        return $this->hostedIdentityProvider;
    }

    /**
     * @return SamlStateHandler
     */
    public function getSamlStateHandler()
    {
        return $this->samlStateHandler;
    }

    /**
     * @return \SAML2_AuthnRequest
     */
    public function getAuthRequest()
    {
        return $this->authRequest;
    }
}
