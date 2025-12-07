<?php

declare(strict_types=1);

namespace ReactBundle\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * ✅ P3-VAL-01: Valide les artifacts après un build Vite
 */
class BuildArtifactValidator
{
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Valide que les artifacts nécessaires ont été créés après le build
     *
     * @param string $buildPath Chemin vers le répertoire de build
     * @return array{'manifest_exists' => bool, 'js_bundles' => int, 'css_bundles' => int, 'manifest_path' => string}
     * @throws \RuntimeException Si la validation échoue
     */
    public function validateBuildArtifacts(string $buildPath): array
    {
        // Vérifier que le répertoire de build existe
        if (!is_dir($buildPath)) {
            throw new \RuntimeException(sprintf('Build directory does not exist: %s', $buildPath));
        }

        $manifestPath = $buildPath . DIRECTORY_SEPARATOR . 'manifest.json';
        $manifestExists = file_exists($manifestPath);

        // Compter les fichiers JS et CSS
        $jsCount = 0;
        $cssCount = 0;

        if ($dh = opendir($buildPath)) {
            while (($file = readdir($dh)) !== false) {
                if ($file === '.' || $file === '..' || is_dir($buildPath . DIRECTORY_SEPARATOR . $file)) {
                    continue;
                }

                if (pathinfo($file, PATHINFO_EXTENSION) === 'js') {
                    $jsCount++;
                } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'css') {
                    $cssCount++;
                }
            }
            closedir($dh);
        }

        // ✅ P3-VAL-02: Valider que le manifest existe
        if (!$manifestExists) {
            $this->logger->error('Vite manifest.json not found after build', [
                'build_path' => $buildPath,
                'manifest_path' => $manifestPath,
                'js_files' => $jsCount,
                'css_files' => $cssCount,
            ]);
            throw new \RuntimeException(sprintf('Manifest file not found at: %s', $manifestPath));
        }

        // ✅ P3-VAL-03: Avertir si aucun bundle JS n'a été créé
        if ($jsCount === 0) {
            $this->logger->warning('No JavaScript bundles found after build', [
                'build_path' => $buildPath,
            ]);
        }

        // Log succès
        $this->logger->info('Build artifacts validation successful', [
            'build_path' => $buildPath,
            'manifest_path' => $manifestPath,
            'js_bundles' => $jsCount,
            'css_bundles' => $cssCount,
        ]);

        return [
            'manifest_exists' => true,
            'js_bundles' => $jsCount,
            'css_bundles' => $cssCount,
            'manifest_path' => $manifestPath,
        ];
    }

    /**
     * Valide que le manifest.json est un JSON valide
     */
    public function validateManifestFormat(string $manifestPath): array
    {
        if (!file_exists($manifestPath)) {
            throw new \RuntimeException(sprintf('Manifest file not found: %s', $manifestPath));
        }

        $content = file_get_contents($manifestPath);
        if ($content === false) {
            throw new \RuntimeException(sprintf('Cannot read manifest file: %s', $manifestPath));
        }

        $manifest = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                sprintf('Invalid JSON in manifest: %s', json_last_error_msg())
            );
        }

        if (!is_array($manifest)) {
            throw new \RuntimeException('Manifest must be a JSON object');
        }

        $this->logger->debug('Manifest format is valid', [
            'manifest_path' => $manifestPath,
            'entry_count' => count($manifest),
        ]);

        return $manifest;
    }
}
