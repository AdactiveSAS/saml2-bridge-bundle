<?php

namespace AdactiveSas\Saml2BridgeBundle\Entity;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use SAML2_Configuration_PrivateKey as PrivateKey;

class HostedEntities
{
    /**
     * @var HostedServiceProvider
     */
    private $serviceProvider;

    /**
     * @var array
     */
    private $serviceProviderConfiguration;

    /**
     * @var HostedIdentityProvider
     */
    private $identityProvider;

    /**
     * @var array
     */
    private $identityProviderConfiguration;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    private $router;

    /**
     * @param RouterInterface $router
     * @param RequestStack    $requestStack
     * @param array           $serviceProviderConfiguration
     * @param array           $identityProviderConfiguration
     */
    public function __construct(
        RouterInterface $router,
        RequestStack $requestStack,
        array $serviceProviderConfiguration = null,
        array $identityProviderConfiguration = null
    ) {
        $this->router                        = $router;
        $this->requestStack                  = $requestStack;
        $this->serviceProviderConfiguration  = $serviceProviderConfiguration;
        $this->identityProviderConfiguration = $identityProviderConfiguration;
    }

    /**
     * @return null|HostedServiceProvider
     */
    public function getServiceProvider()
    {
        if (!empty($this->serviceProvider)) {
            return $this->serviceProvider;
        }

        if (!$this->serviceProviderConfiguration['enabled']) {
            return null;
        }

        $configuration = $this->createStandardEntityConfiguration($this->serviceProviderConfiguration);
        $configuration['assertionConsumerUrl'] = $this->generateUrl(
            $this->serviceProviderConfiguration['assertion_consumer_route']
        );

        return $this->serviceProvider = new HostedServiceProvider($configuration);
    }

    /**
     * @return null|HostedIdentityProvider
     */
    public function getIdentityProvider()
    {
        if (!empty($this->identityProvider)) {
            return $this->identityProvider;
        }

        if (!$this->identityProviderConfiguration['enabled']) {
            return null;
        }

        $configuration = $this->createStandardEntityConfiguration($this->identityProviderConfiguration);
        $configuration['ssoUrl'] = $this->generateUrl(
            $this->identityProviderConfiguration['sso_route']
        );
        $configuration['loginUrl'] = $this->generateUrl(
            $this->identityProviderConfiguration['login_route']
        );
        $configuration['logoutUrl'] = $this->generateUrl(
            $this->identityProviderConfiguration['logout_route']
        );

        return $this->identityProvider = new HostedIdentityProvider($configuration);
    }

    /**
     * @param array $entityConfiguration
     * @return array
     */
    private function createStandardEntityConfiguration($entityConfiguration)
    {
        $privateKey = new PrivateKey($entityConfiguration['private_key'], PrivateKey::NAME_DEFAULT);

        return [
            'entityId'                   => $this->generateUrl($entityConfiguration['metadata_route']),
            'certificateFile'            => $entityConfiguration['public_key'],
            'privateKeys'                => [$privateKey],
            'blacklistedAlgorithms'      => [],
            'assertionEncryptionEnabled' => false
        ];
    }

    /**
     * @param string|array $routeDefinition
     * @return string
     */
    private function generateUrl($routeDefinition)
    {
        $route      = is_array($routeDefinition) ? $routeDefinition['route'] : $routeDefinition;
        $parameters = is_array($routeDefinition) ? $routeDefinition['parameters'] : [];

        $context = $this->router->getContext();

        $context->fromRequest($this->requestStack->getMasterRequest());

        $url = $this->router->generate($route, $parameters, RouterInterface::ABSOLUTE_URL);

        $context->fromRequest($this->requestStack->getCurrentRequest());

        return $url;
    }
}
