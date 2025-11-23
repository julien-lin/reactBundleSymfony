import React from 'react';
import { createRoot } from 'react-dom/client';

// Import des composants React depuis l'index
import { ExampleComponent } from '../../React';

/**
 * ErrorBoundary pour gérer les erreurs React
 */
class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null, errorInfo: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true };
    }

    componentDidCatch(error, errorInfo) {
        console.error('Erreur React dans le composant:', error, errorInfo);
        this.setState({
            error: error,
            errorInfo: errorInfo
        });
    }

    render() {
        if (this.state.hasError) {
            return React.createElement('div', {
                style: {
                    padding: '20px',
                    border: '2px solid #f44336',
                    borderRadius: '4px',
                    backgroundColor: '#ffebee',
                    color: '#c62828',
                    margin: '10px 0'
                }
            }, [
                React.createElement('strong', { key: 'title' }, 'Erreur dans le composant React'),
                React.createElement('p', { key: 'message' }, this.state.error?.toString() || 'Une erreur est survenue'),
                process.env.NODE_ENV === 'development' && this.state.errorInfo && React.createElement('details', { key: 'details' }, [
                    React.createElement('summary', { key: 'summary' }, 'Détails de l\'erreur'),
                    React.createElement('pre', { key: 'stack' }, this.state.errorInfo.componentStack)
                ])
            ]);
        }

        return this.props.children;
    }
}

/**
 * Point d'entrée principal pour React
 * Monte tous les composants React présents dans la page
 */
function initReactComponents() {
    // Trouve tous les conteneurs de composants React
    const containers = document.querySelectorAll('[data-react-component]');

    containers.forEach((container) => {
        // Vérifier si le conteneur a déjà été initialisé
        if (container.dataset.reactInitialized === 'true') {
            return;
        }
        
        const componentName = container.getAttribute('data-react-component');
        const propsJson = container.getAttribute('data-react-props');
        
        let props = {};
        if (propsJson) {
            try {
                props = JSON.parse(propsJson);
            } catch (e) {
                console.error(`Erreur lors du parsing des props pour "${componentName}":`, e);
                console.error('Props JSON:', propsJson);
                // Continuer avec des props vides plutôt que de planter
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
            // Afficher un message d'erreur dans le conteneur
            container.innerHTML = `<div style="padding: 10px; background: #ffebee; color: #c62828; border: 1px solid #f44336; border-radius: 4px;">
                Composant "${componentName}" non trouvé. Vérifiez que le composant est exporté et ajouté au componentMap.
            </div>`;
            return;
        }

        try {
            // Crée le root React et monte le composant avec ErrorBoundary
            const root = createRoot(container);
            root.render(
                React.createElement(ErrorBoundary, null,
                    React.createElement(Component, props)
                )
            );
            
            // Marquer comme initialisé
            container.dataset.reactInitialized = 'true';
        } catch (error) {
            console.error(`Erreur lors du montage du composant "${componentName}":`, error);
            container.innerHTML = `<div style="padding: 10px; background: #ffebee; color: #c62828; border: 1px solid #f44336; border-radius: 4px;">
                Erreur lors du montage du composant "${componentName}": ${error.message}
            </div>`;
        }
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

