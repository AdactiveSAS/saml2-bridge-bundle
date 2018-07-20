<?php

/**
 * Copyright 2014 SURFnet bv
 *
 * Modifications copyright (C) 2017 Adactive SAS
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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
                    ->isRequired()
                    ->children()
                        ->scalarNode('metadata_route')
                            ->isRequired()
                            ->info('The name of the route to provide metadata')
                        ->end()
                        ->arrayNode('identity_provider')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('sso_route')
                                    ->info('The name of the route to generate the SSO URL')
                                ->end()
                                ->scalarNode('login_route')
                                    ->info('The name of the route to generate the Login URL')
                                ->end()
                                ->scalarNode('sls_route')
                                    ->info('The name of the route to generate the SLS URL')
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
