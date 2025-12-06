<?php

namespace ReactBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('react');

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
                ->scalarNode('vite_server')
                    ->defaultValue('http://localhost:3000')
                    ->info('URL du serveur Vite en développement (peut être surchargée par VITE_SERVER_URL)')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
