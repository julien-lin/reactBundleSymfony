<?php

namespace ReactBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ViteExtension extends AbstractExtension
{
    private bool $isDev;
    private string $viteServer;
    private string $buildDir;

    public function __construct(bool $isDev = false, string $viteServer = 'http://localhost:3000', string $buildDir = 'build')
    {
        $this->isDev = $isDev;
        $this->viteServer = $viteServer;
        $this->buildDir = $buildDir;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vite_entry_script_tags', [$this, 'renderViteScriptTags'], ['is_safe' => ['html']]),
            new TwigFunction('vite_entry_link_tags', [$this, 'renderViteLinkTags'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Génère les balises script pour Vite
     */
    public function renderViteScriptTags(string $entry): string
    {
        // Chemin vers public/build/ depuis le bundle (vendor/ ou src/)
        $bundlePath = $this->getBundlePath();

        // Calculer le projet root : si dans vendor/, remonter de 3 niveaux, sinon 2
        $vendorSeparator = DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
        if (strpos($bundlePath, $vendorSeparator) !== false) {
            $projectRoot = dirname($bundlePath, 3); // vendor/vendor/package -> racine
        } else {
            $projectRoot = dirname($bundlePath, 2); // src/ReactBundle -> racine
        }

        // Normaliser les chemins
        $projectRoot = $this->normalizePath($projectRoot);
        $buildDir = $this->normalizePath($this->buildDir);

        // Le manifest peut être dans .vite/ ou directement dans build/
        $manifestPath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $buildDir . DIRECTORY_SEPARATOR . '.vite' . DIRECTORY_SEPARATOR . 'manifest.json';
        if (!file_exists($manifestPath)) {
            $manifestPath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $buildDir . DIRECTORY_SEPARATOR . 'manifest.json';
        }
        $manifestExists = file_exists($manifestPath);

        // Si le manifest existe, utiliser le build de production (même en dev)
        if ($manifestExists) {
            $manifest = json_decode(file_get_contents($manifestPath), true);

            // Chercher l'entrée dans le manifest (peut être 'app' ou 'js/app.jsx')
            $manifestKey = $entry;
            if (!isset($manifest[$manifestKey])) {
                // Essayer avec le chemin complet
                $manifestKey = $entry === 'app' ? 'js/app.jsx' : $entry;
                if (!isset($manifest[$manifestKey])) {
                    return sprintf('<!-- Entry "%s" not found in manifest. Available keys: %s -->', $entry, implode(', ', array_keys($manifest)));
                }
            }

            $entryData = $manifest[$manifestKey];
            $html = sprintf(
                '<script type="module" src="/%s/%s"></script>',
                $this->buildDir,
                $entryData['file']
            );

            // Ajouter les imports CSS si présents
            if (isset($entryData['css'])) {
                foreach ($entryData['css'] as $css) {
                    $html .= sprintf(
                        '<link rel="stylesheet" href="/%s/%s">',
                        $this->buildDir,
                        $css
                    );
                }
            }

            return $html;
        }

        // Si pas de manifest et en dev, essayer le serveur Vite
        if ($this->isDev) {
            $viteUrl = rtrim($this->viteServer, '/');
            return sprintf(
                '<script type="module" src="%s/@vite/client"></script><script type="module" src="%s/%s"></script>',
                $viteUrl,
                $viteUrl,
                $entry === 'app' ? 'js/app.jsx' : $entry
            );
        }

        // Sinon, erreur
        return sprintf('<!-- Vite manifest not found: %s -->', $manifestPath);
    }

    /**
     * Génère les balises link pour Vite (CSS)
     */
    public function renderViteLinkTags(string $entry): string
    {
        if ($this->isDev) {
            return ''; // En dev, Vite injecte les CSS automatiquement
        }

        // Chemin vers public/build/ depuis le bundle (vendor/ ou src/)
        $bundlePath = $this->getBundlePath();

        // Calculer le projet root : si dans vendor/, remonter de 3 niveaux, sinon 2
        $vendorSeparator = DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
        if (strpos($bundlePath, $vendorSeparator) !== false) {
            $projectRoot = dirname($bundlePath, 3); // vendor/vendor/package -> racine
        } else {
            $projectRoot = dirname($bundlePath, 2); // src/ReactBundle -> racine
        }

        // Normaliser les chemins
        $projectRoot = $this->normalizePath($projectRoot);
        $buildDir = $this->normalizePath($this->buildDir);

        // Le manifest peut être dans .vite/ ou directement dans build/
        $manifestPath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $buildDir . DIRECTORY_SEPARATOR . '.vite' . DIRECTORY_SEPARATOR . 'manifest.json';
        if (!file_exists($manifestPath)) {
            $manifestPath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $buildDir . DIRECTORY_SEPARATOR . 'manifest.json';
        }

        if (!file_exists($manifestPath)) {
            return '';
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        // Chercher l'entrée dans le manifest (peut être 'app' ou 'js/app.jsx')
        $manifestKey = $entry;
        if (!isset($manifest[$manifestKey])) {
            $manifestKey = $entry === 'app' ? 'js/app.jsx' : $entry;
            if (!isset($manifest[$manifestKey])) {
                return '';
            }
        }

        if (!isset($manifest[$manifestKey]['css'])) {
            return '';
        }

        $html = '';
        foreach ($manifest[$manifestKey]['css'] as $css) {
            $html .= sprintf(
                '<link rel="stylesheet" href="/%s/%s">',
                $this->buildDir,
                $css
            );
        }

        return $html;
    }

    private function getBundlePath(): string
    {
        // Utiliser la réflexion pour trouver le chemin réel du bundle
        $reflection = new \ReflectionClass(\ReactBundle\ReactBundle::class);
        $bundlePath = dirname($reflection->getFileName(), 2);

        // Normaliser les séparateurs de chemin pour Windows
        $bundlePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $bundlePath);

        // Si le bundle est dans vendor/, vérifier que c'est bien le bon chemin
        if (strpos($bundlePath, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) {
            // Vérifier que package.json existe pour confirmer que c'est le bon chemin
            if (file_exists($bundlePath . DIRECTORY_SEPARATOR . 'package.json')) {
                return $bundlePath;
            }
        }

        // Sinon, on est dans le développement local (src/ReactBundle)
        // Vérifier que package.json existe
        if (file_exists($bundlePath . DIRECTORY_SEPARATOR . 'package.json')) {
            return $bundlePath;
        }

        // Fallback : remonter depuis le répertoire actuel
        return dirname(__DIR__, 2);
    }

    /**
     * Normalise un chemin pour être compatible avec tous les OS
     */
    private function normalizePath(string $path): string
    {
        return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
    }
}
