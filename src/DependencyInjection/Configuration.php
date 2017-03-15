<?php

namespace AdactiveSas\Saml2BridgeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('adactive_sas_saml2_bridge');

        $this->addHostedSection($rootNode);

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition $node
     */
    private function addHostedSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('hosted')
                    ->children()
                        ->arrayNode('identity_provider')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('metadata_route')
                                    ->info('The name of the route to provide metadata')
                                ->end()
                                ->scalarNode('sso_route')
                                    ->info('The name of the route to generate the SSO URL')
                                ->end()
                                ->scalarNode('login_route')
                                    ->info('The name of the route to generate the Login URL')
                                ->end()
                                ->scalarNode('logout_route')
                                    ->info('The name of the route to generate the Logout URL')
                                ->end()
                                ->scalarNode('service_provider_repository')
                                    ->info(
                                        'Name of the service that is the Entity Repository. Must implement the '
                                        . ' AdactiveSas\Saml2BridgeBundle\Entity\ServiceProviderRepository interface.'
                                    )
                                ->end()
                                ->scalarNode('public_key')
                                    ->info('The absolute path to the public key used to sign Responses to AuthRequests with')
                                ->end()
                                ->scalarNode('private_key')
                                    ->info('The absolute path to the private key used to sign Responses to AuthRequests with')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();
    }
}
