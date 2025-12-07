<?php

declare(strict_types=1);

namespace ReactBundle\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Twig\Environment;

class ReactRenderer
{
    private Environment $twig;
    private string $buildDir;
    private LoggerInterface $logger;
    private static int $componentCounter = 0;

    public function __construct(Environment $twig, string $buildDir = 'build', ?LoggerInterface $logger = null)
    {
        $this->twig = $twig;
        $this->buildDir = $buildDir;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Génère le HTML pour un composant React
     *
     * @param string $componentName Nom du composant React (ex: 'ExampleComponent')
     * @param array $props Propriétés à passer au composant
     * @param string|null $id ID unique pour le conteneur (généré automatiquement si null)
     * @return string HTML généré
     * @throws \InvalidArgumentException Si le nom du composant est invalide
     */
    public function render(string $componentName, array $props = [], ?string $id = null): string
    {
        // ✅ P1-PERF-01: Ajouter métriques de performance
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // ✅ P0-SEC-02: Valider le nom du composant
        if (!$this->isValidComponentName($componentName)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid component name: %s. Must contain only alphanumeric characters, hyphens, and underscores.',
                    $componentName
                )
            );
        }

        if ($id === null) {
            $id = 'react-component-' . ++self::$componentCounter . '-' . uniqid();
        }

        // ✅ P0-XSS-01: Valider et échapper les props pour la sécurité
        try {
            // Encoder en JSON normal (sans flags d'échappement HTML)
            $jsonProps = json_encode($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException(
                    sprintf(
                        'Erreur lors de l\'encodage JSON des props pour le composant "%s": %s',
                        $componentName,
                        json_last_error_msg()
                    )
                );
            }

            // ✅ P0-XSS-01: Utiliser htmlspecialchars() comme standard pour l'échappement HTML
            // Cela échappe correctement pour un attribut HTML
            $escapedProps = htmlspecialchars(
                $jsonProps,
                ENT_QUOTES | ENT_HTML5,
                'UTF-8',
                false  // Ne pas double-encoder
            );
        } catch (\Exception $e) {
            // ✅ P1-LOG-01: Utiliser un vrai logger au lieu de error_log()
            $this->logger->error('Erreur lors du rendu du composant', [
                'component' => $componentName,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            $escapedProps = '{}';
        }

        $html = $this->twig->render('@React/react_component.html.twig', [
            'component_id' => $id,
            'component_name' => $componentName,
            'props' => $escapedProps,
        ]);

        // ✅ P1-PERF-01: Logger les métriques de rendu
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $duration = ($endTime - $startTime) * 1000; // en millisecondes
        $memoryUsed = ($endMemory - $startMemory) / 1024; // en KB

        $this->logger->info('React component rendered', [
            'component' => $componentName,
            'duration_ms' => round($duration, 2),
            'memory_kb' => round($memoryUsed, 2),
            'props_count' => count($props),
            'html_length' => strlen($html),
        ]);

        return $html;
    }

    /**
     * ✅ P0-SEC-02: Valide que le nom du composant respecte les règles de sécurité
     */
    private function isValidComponentName(string $name): bool
    {
        // Permettre seulement alphanumériques, traits d'union et underscores
        return preg_match('/^[a-zA-Z0-9_-]+$/', $name) === 1 && strlen($name) <= 255;
    }

    /**
     * Injecte le logger (utile pour les tests)
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getBuildDir(): string
    {
        return $this->buildDir;
    }
}
