<?php

namespace ReactBundle\Service;

use Twig\Environment;

class ReactRenderer
{
    private Environment $twig;
    private string $buildDir;
    private static int $componentCounter = 0;

    public function __construct(Environment $twig, string $buildDir = 'build')
    {
        $this->twig = $twig;
        $this->buildDir = $buildDir;
    }

    /**
     * Génère le HTML pour un composant React
     *
     * @param string $componentName Nom du composant React (ex: 'ExampleComponent')
     * @param array $props Propriétés à passer au composant
     * @param string|null $id ID unique pour le conteneur (généré automatiquement si null)
     * @return string HTML généré
     */
    public function render(string $componentName, array $props = [], ?string $id = null): string
    {
        if ($id === null) {
            $id = 'react-component-' . ++self::$componentCounter . '-' . uniqid();
        }

        // Valider et échapper les props pour la sécurité
        try {
            $escapedProps = json_encode($props, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException(
                    sprintf(
                        'Erreur lors de l\'encodage JSON des props pour le composant "%s": %s',
                        $componentName,
                        json_last_error_msg()
                    )
                );
            }
        } catch (\Exception $e) {
            // En cas d'erreur, utiliser un objet vide et logger l'erreur
            error_log(sprintf(
                'ReactBundle: Erreur lors du rendu du composant "%s": %s',
                $componentName,
                $e->getMessage()
            ));
            $escapedProps = '{}';
        }

        return $this->twig->render('@React/react_component.html.twig', [
            'component_id' => $id,
            'component_name' => $componentName,
            'props' => $escapedProps,
        ]);
    }

    public function getBuildDir(): string
    {
        return $this->buildDir;
    }
}

