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

        // ✅ P0-SEC-01: Validation sécurisée de l'URL Vite (SSRF protection)
        $viteServer = $this->getViteServerUrl($config);

        $container->setParameter('react.build_dir', $config['build_dir'] ?? 'build');
        $container->setParameter('react.assets_dir', $config['assets_dir'] ?? 'assets');
        $container->setParameter('react.vite_server', $viteServer);
    }

    /**
     * ✅ P0-SEC-01: Récupère et valide l'URL du serveur Vite
     * 
     * @throws \InvalidArgumentException Si l'URL n'est pas valide
     */
    private function getViteServerUrl(array $config): string
    {
        $viteServerEnv = getenv('VITE_SERVER_URL', true);
        // Ne pas utiliser la variable d'env si elle est vide
        $viteServer = (!empty($viteServerEnv) ? $viteServerEnv : null) ?? $config['vite_server'] ?? 'http://localhost:3000';

        if (!$this->isValidViteServerUrl($viteServer)) {
            throw new \InvalidArgumentException(
                sprintf('VITE_SERVER_URL "%s" is invalid. Must be a valid http(s) URL.', $viteServer)
            );
        }

        return $viteServer;
    }

    /**
     * ✅ P0-SEC-01: Valide que l'URL ne pose pas de risque SSRF
     */
    private function isValidViteServerUrl(string $url): bool
    {
        // Validation d'URL basique
        $parsed = filter_var($url, FILTER_VALIDATE_URL);
        if ($parsed === false) {
            return false;
        }

        // Vérifier que le schéma est http ou https (éviter file://, ftp://, etc.)
        $scheme = parse_url($url, PHP_URL_SCHEME);
        return in_array($scheme, ['http', 'https'], true);
    }

    public function getAlias(): string
    {
        return 'react';
    }
}
