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
        if ($this->isDev) {
            // En dev, Vite sert directement depuis le serveur
            return sprintf(
                '<script type="module" src="%s/@vite/client"></script><script type="module" src="%s/%s"></script>',
                rtrim($this->viteServer, '/'),
                rtrim($this->viteServer, '/'),
                $entry === 'app' ? 'js/app.jsx' : $entry
            );
        }

        // Chemin vers public/build/ depuis le bundle (vendor/ ou src/)
        $bundlePath = $this->getBundlePath();
        $projectRoot = dirname($bundlePath, 2); // Remonter de vendor/vendor/package ou src/ReactBundle
        $manifestPath = $projectRoot . '/public/' . $this->buildDir . '/manifest.json';
        
        if (!file_exists($manifestPath)) {
            return sprintf('<!-- Vite manifest not found: %s -->', $manifestPath);
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        
        if (!isset($manifest[$entry])) {
            return sprintf('<!-- Entry "%s" not found in manifest -->', $entry);
        }

        $entryData = $manifest[$entry];
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
        $projectRoot = dirname($bundlePath, 2); // Remonter de vendor/vendor/package ou src/ReactBundle
        $manifestPath = $projectRoot . '/public/' . $this->buildDir . '/manifest.json';
        
        if (!file_exists($manifestPath)) {
            return '';
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        
        if (!isset($manifest[$entry]) || !isset($manifest[$entry]['css'])) {
            return '';
        }

        $html = '';
        foreach ($manifest[$entry]['css'] as $css) {
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
        
        // Si le bundle est dans vendor/, vérifier que c'est bien le bon chemin
        if (strpos($bundlePath, '/vendor/') !== false) {
            // Vérifier que package.json existe pour confirmer que c'est le bon chemin
            if (file_exists($bundlePath . '/package.json')) {
                return $bundlePath;
            }
        }
        
        // Sinon, on est dans le développement local (src/ReactBundle)
        // Vérifier que package.json existe
        if (file_exists($bundlePath . '/package.json')) {
            return $bundlePath;
        }
        
        // Fallback : remonter depuis le répertoire actuel
        return dirname(__DIR__, 2);
    }
}

