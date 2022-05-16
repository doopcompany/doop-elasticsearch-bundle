<?php

namespace Doop\ElasticsearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('doop_elasticsearch');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->booleanNode('massive_search_hooks_enabled')
                    ->defaultTrue()
                ->end()
                ->arrayNode('hosts')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('indices')
                ->useAttributeAsKey('name')
                ->prototype('array')
                ->children()
                    ->arrayNode('body')
                        ->prototype('variable')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}