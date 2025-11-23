<?php

namespace ReactBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ReactExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../Resources/config')
        );
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Récupérer VITE_SERVER_URL depuis les variables d'environnement si disponible
        $viteServer = $_ENV['VITE_SERVER_URL'] ?? $_SERVER['VITE_SERVER_URL'] ?? $config['vite_server'] ?? 'http://localhost:3000';

        $container->setParameter('react.build_dir', $config['build_dir'] ?? 'build');
        $container->setParameter('react.assets_dir', $config['assets_dir'] ?? 'assets');
        $container->setParameter('react.vite_server', $viteServer);
    }

    public function getAlias(): string
    {
        return 'react';
    }
}

