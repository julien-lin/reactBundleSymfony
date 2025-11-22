<?php

namespace ReactBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('react_bundle');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('build_dir')
                    ->defaultValue('build')
                    ->info('Répertoire de sortie pour les assets compilés')
                ->end()
                ->scalarNode('assets_dir')
                    ->defaultValue('assets')
                    ->info('Répertoire source des assets React')
                ->end()
            ->end();

        return $treeBuilder;
    }
}

