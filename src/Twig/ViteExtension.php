<?php

declare(strict_types=1);

namespace ReactBundle\Twig;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ViteExtension extends AbstractExtension
{
    private bool $isDev;
    private string $viteServer;
    private string $buildDir;
    private LoggerInterface $logger;
    private array $manifestCache = []; // ✅ P2-PERF-01: Manifest caching
    private ?string $environment = null;
    private bool $kernelDebug;

    public function __construct(bool $isDev = false, string $viteServer = 'http://localhost:3000', string $buildDir = 'build', ?LoggerInterface $logger = null)
    {
        $this->kernelDebug = $isDev;
        // isDev sera recalculé dans setEnvironment si appelé
        $this->isDev = $isDev;
        $this->viteServer = $viteServer;
        $this->buildDir = $buildDir;
        $this->logger = $logger ?? new NullLogger();
    }
    
    /**
     * Définit l'environnement Symfony pour améliorer la détection du mode dev
     * ✅ Amélioration : Le mode dev est actif si kernel.debug est true ET kernel.environment est 'dev'
     */
    public function setEnvironment(string $environment): void
    {
        $this->environment = $environment;
        // Recalculer isDev : mode dev = debug activé ET environnement = 'dev'
        $this->isDev = $this->kernelDebug && ($environment === 'dev');
        
        $this->logger->debug('ViteExtension environment set', [
            'environment' => $environment,
            'kernel_debug' => $this->kernelDebug,
            'is_dev' => $this->isDev,
        ]);
    }

    /**
     * Récupère l'état du mode dev (pour les tests)
     */
    public function getIsDev(): bool
    {
        return $this->isDev;
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

        // ✅ P2-LOG-03: Log mode et manifest status
        $this->logger->debug('Vite extension rendering script tags', [
            'mode' => $this->isDev ? 'dev' : 'production',
            'entry' => $entry,
            'manifest_exists' => $manifestExists,
            'manifest_path' => $manifestPath,
        ]);

        // En mode dev, prioriser le serveur Vite même si le manifest existe
        // Si pas de manifest et en dev, essayer le serveur Vite
        if ($this->isDev) {
            $viteUrl = rtrim($this->viteServer, '/');
            
            // ✅ P0-IMPROVEMENT: Vérifier optionnellement si le serveur Vite est accessible
            $serverAccessible = $this->checkViteServerAccessibility($viteUrl);
            
            if ($serverAccessible) {
                $this->logger->info('Vite script tags generated in dev mode', [
                    'entry' => $entry,
                    'mode' => 'dev',
                    'vite_server' => $viteUrl,
                ]);
                return sprintf(
                    '<script type="module" src="%s/@vite/client"></script><script type="module" src="%s/%s"></script>',
                    $viteUrl,
                    $viteUrl,
                    $entry === 'app' ? 'js/app.jsx' : $entry
                );
            }
            
            // Si le serveur n'est pas accessible, fallback vers le manifest si disponible
            if (!$serverAccessible && $manifestExists) {
                $this->logger->warning('Vite server not accessible in dev mode, falling back to manifest', [
                    'entry' => $entry,
                    'mode' => 'dev',
                    'vite_server' => $viteUrl,
                    'manifest_path' => $manifestPath,
                ]);
                // Continuer avec le manifest (fallback)
            } elseif (!$serverAccessible) {
                $bundlePath = $this->getBundlePath();
                $vendorSeparator = DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
                if (strpos($bundlePath, $vendorSeparator) !== false) {
                    $projectRoot = dirname($bundlePath, 3);
                } else {
                    $projectRoot = dirname($bundlePath, 2);
                }
                $projectRoot = $this->normalizePath($projectRoot);
                $buildDir = $this->normalizePath($this->buildDir);
                
                $this->logger->warning('Vite server not accessible in dev mode', [
                    'entry' => $entry,
                    'mode' => 'dev',
                    'vite_server' => $viteUrl,
                    'suggestion' => 'Run: php bin/console react:build --dev',
                ]);
                
                // Fallback gracieux : utiliser le manifest si disponible, sinon afficher un message
                $fallbackManifestPath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $buildDir . DIRECTORY_SEPARATOR . 'manifest.json';
                if (file_exists($fallbackManifestPath)) {
                    $this->logger->info('Falling back to production manifest', [
                        'manifest_path' => $fallbackManifestPath,
                    ]);
                    // Réessayer avec le manifest
                    try {
                        $manifest = $this->loadAndValidateManifest($fallbackManifestPath);
                        $manifestKey = $entry === 'app' ? 'js/app.jsx' : $entry;
                        if (isset($manifest[$manifestKey])) {
                            $entryData = $manifest[$manifestKey];
                            return sprintf(
                                '<script type="module" src="/%s/%s"></script>',
                                $this->buildDir,
                                $entryData['file']
                            );
                        }
                    } catch (\Exception $e) {
                        // Ignorer et continuer avec le message d'erreur
                    }
                }
                
                // Afficher un commentaire HTML informatif
                return sprintf(
                    '<!-- Vite server not accessible at %s. Run: npm run dev -->',
                    htmlspecialchars($viteUrl, ENT_QUOTES, 'UTF-8')
                );
            }
        }

        // Si le manifest existe, utiliser le build de production
        if ($manifestExists) {
            try {
                $manifest = $this->loadAndValidateManifest($manifestPath);

                // Chercher l'entrée dans le manifest (peut être 'app' ou 'js/app.jsx')
                $manifestKey = $entry;
                if (!isset($manifest[$manifestKey])) {
                    // Essayer avec le chemin complet
                    $manifestKey = $entry === 'app' ? 'js/app.jsx' : $entry;
                    if (!isset($manifest[$manifestKey])) {
                        $this->logger->warning('Entry not found in Vite manifest', [
                            'entry' => $entry,
                            'available_keys' => array_keys($manifest),
                            'manifest_path' => $manifestPath,
                        ]);
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

                $this->logger->info('Vite script tags generated successfully', [
                    'entry' => $entry,
                    'mode' => 'production',
                    'css_count' => isset($entryData['css']) ? count($entryData['css']) : 0,
                ]);

                return $html;
            } catch (\Exception $e) {
                // ✅ P2-LOG-04: Log manifest loading errors
                $this->logger->error('Error loading Vite manifest', [
                    'error' => $e->getMessage(),
                    'manifest_path' => $manifestPath,
                    'exception_class' => get_class($e),
                ]);
                return sprintf('<!-- Error loading Vite manifest: %s -->', htmlspecialchars($e->getMessage()));
            }
        }

        // Si pas de manifest et en dev, essayer le serveur Vite
        if ($this->isDev) {
            $viteUrl = rtrim($this->viteServer, '/');
            
            // ✅ P0-IMPROVEMENT: Vérifier optionnellement si le serveur Vite est accessible
            $serverAccessible = $this->checkViteServerAccessibility($viteUrl);
            
            if (!$serverAccessible) {
                $bundlePath = $this->getBundlePath();
                $vendorSeparator = DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
                if (strpos($bundlePath, $vendorSeparator) !== false) {
                    $projectRoot = dirname($bundlePath, 3);
                } else {
                    $projectRoot = dirname($bundlePath, 2);
                }
                $projectRoot = $this->normalizePath($projectRoot);
                $buildDir = $this->normalizePath($this->buildDir);
                
                $this->logger->warning('Vite server not accessible in dev mode', [
                    'entry' => $entry,
                    'mode' => 'dev',
                    'vite_server' => $viteUrl,
                    'suggestion' => 'Run: php bin/console react:build --dev',
                ]);
                
                // Fallback gracieux : utiliser le manifest si disponible, sinon afficher un message
                $fallbackManifestPath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $buildDir . DIRECTORY_SEPARATOR . 'manifest.json';
                if (file_exists($fallbackManifestPath)) {
                    $this->logger->info('Falling back to production manifest', [
                        'manifest_path' => $fallbackManifestPath,
                    ]);
                    // Réessayer avec le manifest
                    try {
                        $manifest = $this->loadAndValidateManifest($fallbackManifestPath);
                        $manifestKey = $entry === 'app' ? 'js/app.jsx' : $entry;
                        if (isset($manifest[$manifestKey])) {
                            $entryData = $manifest[$manifestKey];
                            return sprintf(
                                '<script type="module" src="/%s/%s"></script>',
                                $this->buildDir,
                                $entryData['file']
                            );
                        }
                    } catch (\Exception $e) {
                        // Ignorer et continuer avec le message d'erreur
                    }
                }
                
                // Afficher un commentaire HTML informatif
                return sprintf(
                    '<!-- Vite server not accessible at %s. Run: php bin/console react:build --dev -->',
                    htmlspecialchars($viteUrl, ENT_QUOTES, 'UTF-8')
                );
            }
            
            $this->logger->info('Vite script tags generated in dev mode', [
                'entry' => $entry,
                'mode' => 'dev',
                'vite_server' => $viteUrl,
            ]);
            return sprintf(
                '<script type="module" src="%s/@vite/client"></script><script type="module" src="%s/%s"></script>',
                $viteUrl,
                $viteUrl,
                $entry === 'app' ? 'js/app.jsx' : $entry
            );
        }

        // Sinon, erreur
        $this->logger->error('Vite manifest not found and not in dev mode', [
            'manifest_path' => $manifestPath,
            'mode' => 'production',
            'entry' => $entry,
        ]);
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

    /**
     * ✅ P1-ERR-01: Charge et valide le manifest Vite avec gestion d'erreur robuste
     * ✅ P2-PERF-01: Cache le manifest en mémoire pour les appels multiples
     *
     * @throws \RuntimeException Si le manifest est invalide
     * @throws \JsonException Si le JSON est corrompu
     */
    private function loadAndValidateManifest(string $manifestPath): array
    {
        // ✅ P2-PERF-01: Vérifier le cache d'abord
        $cacheKey = md5($manifestPath);
        if (isset($this->manifestCache[$cacheKey])) {
            $this->logger->debug('Manifest loaded from cache', ['path' => $manifestPath]);
            return $this->manifestCache[$cacheKey];
        }

        // Vérifier l'existence du fichier
        if (!file_exists($manifestPath)) {
            throw new \RuntimeException("Vite manifest not found at: $manifestPath");
        }

        // Lire le contenu du fichier
        $content = @file_get_contents($manifestPath);
        if ($content === false) {
            throw new \RuntimeException("Cannot read Vite manifest at: $manifestPath");
        }

        // Décoder le JSON
        $manifest = json_decode($content, true);

        // Valider le JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \JsonException(
                sprintf("Invalid JSON in Vite manifest: %s", json_last_error_msg()),
                json_last_error()
            );
        }

        // Valider que c'est un array
        if (!is_array($manifest)) {
            throw new \RuntimeException("Vite manifest must be a JSON object, got: " . gettype($manifest));
        }

        // ✅ P2-PERF-01: Stocker en cache (limite à 10 manifests max)
        if (count($this->manifestCache) >= 10) {
            array_shift($this->manifestCache); // Supprimer le plus ancien
        }
        $this->manifestCache[$cacheKey] = $manifest;
        $this->logger->debug('Manifest loaded and cached', ['path' => $manifestPath, 'entry_count' => count($manifest)]);

        return $manifest;
    }

    /**
     * ✅ P0-IMPROVEMENT: Vérifie si le serveur Vite est accessible
     * Cette vérification est optionnelle et peut être désactivée pour améliorer les performances
     *
     * @param string $viteUrl URL du serveur Vite
     * @return bool True si le serveur est accessible, false sinon
     */
    private function checkViteServerAccessibility(string $viteUrl): bool
    {
        // Désactiver la vérification si on n'est pas en mode debug strict
        // pour éviter les ralentissements en production
        if (!$this->isDev) {
            return false;
        }

        // Utiliser un cache statique pour éviter de vérifier à chaque requête
        static $cache = [];
        $cacheKey = md5($viteUrl);
        
        // Cache valide pendant 5 secondes
        if (isset($cache[$cacheKey])) {
            [$result, $timestamp] = $cache[$cacheKey];
            if (time() - $timestamp < 5) {
                return $result;
            }
        }

        try {
            // Utiliser file_get_contents avec un timeout court pour éviter les blocages
            $context = stream_context_create([
                'http' => [
                    'timeout' => 1,
                    'method' => 'GET',
                    'header' => "User-Agent: ReactBundle/2.0\r\n",
                ],
            ]);

            $url = rtrim($viteUrl, '/') . '/@vite/client';
            $result = @file_get_contents($url, false, $context);
            
            $isAccessible = $result !== false;
            
            // Mettre en cache le résultat
            $cache[$cacheKey] = [$isAccessible, time()];
            
            return $isAccessible;
        } catch (\Exception $e) {
            $this->logger->debug('Vite server accessibility check failed', [
                'error' => $e->getMessage(),
                'vite_url' => $viteUrl,
            ]);
            
            // Mettre en cache le résultat négatif
            $cache[$cacheKey] = [false, time()];
            
            return false;
        }
    }
}
