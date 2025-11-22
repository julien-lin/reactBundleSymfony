import React from 'react';
import { createRoot } from 'react-dom/client';

// Import des composants React depuis l'index
import { ExampleComponent } from '../../React';

/**
 * Point d'entrée principal pour React
 * Monte tous les composants React présents dans la page
 */
function initReactComponents() {
    // Trouve tous les conteneurs de composants React
    const containers = document.querySelectorAll('[data-react-component]');

    containers.forEach((container) => {
        const componentName = container.getAttribute('data-react-component');
        const propsJson = container.getAttribute('data-react-props');
        
        let props = {};
        if (propsJson) {
            try {
                props = JSON.parse(propsJson);
            } catch (e) {
                console.error('Erreur lors du parsing des props pour', componentName, e);
            }
        }

        // Mapping des noms de composants vers les composants React
        const componentMap = {
            'ExampleComponent': ExampleComponent,
            // Ajoutez ici vos autres composants
        };

        const Component = componentMap[componentName];

        if (!Component) {
            console.error(`Composant React "${componentName}" non trouvé. Composants disponibles:`, Object.keys(componentMap));
            return;
        }

        // Crée le root React et monte le composant
        const root = createRoot(container);
        root.render(React.createElement(Component, props));
    });
}

// Initialise les composants React quand le DOM est prêt
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReactComponents);
} else {
    initReactComponents();
}

// Support pour Turbo (si utilisé)
if (typeof Turbo !== 'undefined') {
    document.addEventListener('turbo:load', initReactComponents);
    document.addEventListener('turbo:render', initReactComponents);
}

