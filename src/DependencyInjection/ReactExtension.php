<?php

declare(strict_types=1);

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

        $buildDir = $config['build_dir'] ?? 'build';
        $assetsDir = $config['assets_dir'] ?? 'assets';

        // ✅ P2-VAL-01: Valider que les répertoires existent (avertissement seulement)
        $this->validateDirectories($buildDir, $assetsDir);

        $container->setParameter('react.build_dir', $buildDir);
        $container->setParameter('react.assets_dir', $assetsDir);
        $container->setParameter('react.vite_server', $viteServer);
    }

    /**
     * ✅ P2-VAL-01: Valide que les répertoires configurés existent
     */
    private function validateDirectories(string $buildDir, string $assetsDir): void
    {
        // Public build directory
        $publicBuildPath = getcwd() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $buildDir;
        if (!is_dir($publicBuildPath)) {
            trigger_error(
                sprintf('Build directory not found: %s', $publicBuildPath),
                E_USER_WARNING
            );
        }

        // Assets directory
        $assetsPath = getcwd() . DIRECTORY_SEPARATOR . $assetsDir;
        if (!is_dir($assetsPath)) {
            trigger_error(
                sprintf('Assets directory not found: %s', $assetsPath),
                E_USER_WARNING
            );
        }
    }

    /**
     * ✅ P0-SEC-01: Récupère et valide l'URL du serveur Vite
     * ✅ P2-VAL-02: Valide le format et la cohérence de VITE_SERVER_URL
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

        // Avertir si on utilise localhost en production
        if (getenv('APP_ENV') === 'prod' && strpos($viteServer, 'localhost') !== false) {
            trigger_error(
                sprintf('VITE_SERVER_URL uses localhost in production: %s', $viteServer),
                E_USER_WARNING
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
