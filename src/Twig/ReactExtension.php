<?php

namespace ReactBundle\Twig;

use ReactBundle\Service\ReactRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ReactExtension extends AbstractExtension
{
    private ReactRenderer $reactRenderer;

    public function __construct(ReactRenderer $reactRenderer)
    {
        $this->reactRenderer = $reactRenderer;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('react_component', [$this, 'renderComponent'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Fonction Twig pour rendre un composant React
     *
     * @param string $componentName Nom du composant React
     * @param array $props Propriétés à passer au composant
     * @param string|null $id ID unique pour le conteneur
     * @return string HTML généré
     */
    public function renderComponent(string $componentName, array $props = [], ?string $id = null): string
    {
        return $this->reactRenderer->render($componentName, $props, $id);
    }
}

