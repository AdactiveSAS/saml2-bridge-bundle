<?php

namespace AdactiveSas\Saml2BridgeBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
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

        if (!is_string($identityProvider['service_provider_repository'])) {
            throw new InvalidConfigurationException(
                'adactive_sas_saml2_bridge.hosted.identity_provider.service_provider_repository configuration value should be a string'
            );
        }

        $container->setParameter(
            'adactive_sas_saml2_bridge.configuration.service_provider_repository.alias',
            $identityProvider['service_provider_repository']
        );
    }
}
