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

        // Échapper les props pour la sécurité
        $escapedProps = json_encode($props, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return $this->twig->render('@ReactBundle/react_component.html.twig', [
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

