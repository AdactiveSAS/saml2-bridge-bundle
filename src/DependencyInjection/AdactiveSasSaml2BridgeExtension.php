<?php

namespace AdactiveSas\Saml2BridgeBundle\DependencyInjection;

use AdactiveSas\Saml2BridgeBundle\Entity\HostedEntities;
use AdactiveSas\Saml2BridgeBundle\SAML2\Provider\HostedIdentityProviderProcessor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class AdactiveSasSaml2BridgeExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $this->parseHostedConfiguration($config['hosted'], $container);
    }

    /**
     * Creates and register MetadataConfiguration object based on the configuration given.
     *
     * @param array $configuration
     * @param ContainerBuilder $container
     */
    private function parseHostedConfiguration(array $configuration, ContainerBuilder $container)
    {
        $container
            ->getDefinition('adactive_sas_saml2_bridge.configuration.hosted_entities')
            ->replaceArgument(2, $configuration['metadata_route']);

        $this->parseHostedIdpConfiguration($configuration['identity_provider'], $container);
    }

    /**
     * @param array            $identityProvider
     * @param ContainerBuilder $container
     */
    private function parseHostedIdpConfiguration(array $identityProvider, ContainerBuilder $container)
    {
        $container
            ->getDefinition('adactive_sas_saml2_bridge.configuration.hosted_entities')
            ->replaceArgument(3, $identityProvider);

        if (!$identityProvider['enabled']) {
            return;
        }

        $hostedIdpDefinition = new Definition(
            HostedIdentityProviderProcessor::class,
            [
                new Reference($identityProvider['service_provider_repository']),
                new Reference("adactive_sas_saml2_bridge.configuration.hosted_entities"),
                new Reference("adactive_sas_saml2_bridge.http.binding_container"),
                new Reference("adactive_sas_saml2_bridge.state.handler"),
                new Reference("event_dispatcher"),
                new Reference("adactive_sas_saml2_bridge.metadata.factory"),
            ]
        );
        $hostedIdpDefinition->addTag('kernel.event_subscriber');
        $container->setDefinition("adactive_sas_saml2_bridge.processor.hosted_idp", $hostedIdpDefinition);
    }
}
