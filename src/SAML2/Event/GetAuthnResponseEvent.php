<?php

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Event;


use AdactiveSas\Saml2BridgeBundle\Entity\HostedIdentityProvider;
use AdactiveSas\Saml2BridgeBundle\Entity\ServiceProvider;
use AdactiveSas\Saml2BridgeBundle\SAML2\Builder\AuthnResponseBuilder;
use AdactiveSas\Saml2BridgeBundle\SAML2\State\SamlStateHandler;
use Symfony\Component\EventDispatcher\Event;

class GetAuthnResponseEvent extends Event
{
    /**
     * @var ServiceProvider
     */
    protected $serviceProvider;

    /**
     * @var HostedIdentityProvider
     */
    protected $hostedIdentityProvider;

    /**
     * @var SamlStateHandler
     */
    protected $samlStateHandler;

    /**
     * @var AuthnResponseBuilder
     */
    protected $authnResponseBuilder;

    /**
     * AuthenticationEvent constructor.
     * @param ServiceProvider $serviceProvider
     * @param HostedIdentityProvider $hostedIdentityProvider
     * @param SamlStateHandler $samlStateHandler
     * @param AuthnResponseBuilder $authnResponseBuilder
     */
    public function __construct(
        ServiceProvider $serviceProvider,
        HostedIdentityProvider $hostedIdentityProvider,
        SamlStateHandler $samlStateHandler,
        AuthnResponseBuilder $authnResponseBuilder
    )
    {
        $this->serviceProvider = $serviceProvider;
        $this->hostedIdentityProvider = $hostedIdentityProvider;
        $this->samlStateHandler = $samlStateHandler;
        $this->authnResponseBuilder = $authnResponseBuilder;
    }

    /**
     * @return ServiceProvider
     */
    public function getServiceProvider()
    {
        return $this->serviceProvider;
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
     * @return AuthnResponseBuilder
     */
    public function getAuthnResponseBuilder()
    {
        return $this->authnResponseBuilder;
    }
}