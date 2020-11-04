<?php

namespace Hola\AmpToolboxBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('amp_toolbox');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('error')
                    ->children()
                        ->booleanNode('log_enabled')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}