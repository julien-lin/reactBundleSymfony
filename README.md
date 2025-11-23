# ReactBundle - Bundle Symfony pour React

Bundle Symfony indépendant permettant d'intégrer React avec Vite dans vos templates Twig, remplaçant Stimulus.

[![GitHub](https://img.shields.io/github/license/julien-lin/reactBundleSymfony)](https://github.com/julien-lin/reactBundleSymfony)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/symfony-6.0%20%7C%207.0-green)](https://symfony.com/)
[![GitHub Sponsors](https://img.shields.io/github/sponsors/julien-lin?logo=github&color=ea4aaa)](https://github.com/sponsors/julien-lin)

## 💝 Soutenir le projet

Si ce bundle vous est utile, envisagez de [devenir sponsor](https://github.com/sponsors/julien-lin) pour soutenir le développement et la maintenance de ce projet open source.

## 📦 Installation

### Via Composer

```bash
composer require julien-lin/react-bundle-symfony
```

Le script d'installation Composer installera automatiquement les dépendances npm.

### Configuration

1. Le bundle s'enregistre automatiquement via Symfony Flex.

2. Configurez le bundle dans `config/packages/react.yaml` :
```yaml
react:
    build_dir: 'build'
    assets_dir: 'assets'
```

3. Si les dépendances npm n'ont pas été installées automatiquement :
```bash
cd vendor/julien-lin/react-bundle-symfony
npm install
```

4. Créez la structure de fichiers dans votre projet Symfony (si elle n'existe pas déjà) :

```bash
# Créez le dossier pour vos composants React
mkdir -p assets/React/Components

# Créez le fichier index.js pour exporter vos composants
touch assets/React/index.js
```

5. Configurez `assets/React/index.js` (point d'entrée pour vos composants) :

```javascript
/**
 * Point d'entrée pour tous les composants React du projet
 * Exportez ici tous vos composants créés dans React/Components/
 */

// Exemple :
// export { default as MonComposant } from './Components/MonComposant';

// Ajoutez vos exports ici au fur et à mesure
```

6. Configurez `assets/js/app.jsx` (doit importer depuis `../React`) :

```jsx
import React from 'react';
import { createRoot } from 'react-dom/client';

// Import de tous vos composants depuis l'index
import * as ReactComponents from '../React';

// ... reste du code (généralement déjà configuré)
```

## Utilisation

### Prérequis : Structure des fichiers

Avant d'utiliser le bundle, assurez-vous d'avoir la structure suivante dans votre projet Symfony :

```
assets/
├── React/
│   ├── Components/          # Créez vos composants ici
│   └── index.js             # Exportez vos composants ici
└── js/
    └── app.jsx              # Point d'entrée (déjà configuré)
```

### Dans vos templates Twig

```twig
{% extends '@React/react_base.html.twig' %}

{% block body %}
    {# Utilisez react_component avec le nom exact de votre composant #}
    {{ react_component('MonComposant', {
        title: 'Mon titre',
        message: 'Mon message',
        count: 42,
        items: ['item1', 'item2']
    }) }}
{% endblock %}

{% block javascripts %}
    {{ vite_entry_script_tags('app') }}
{% endblock %}
```

**Important** : Le nom du composant dans `react_component()` doit correspondre exactement au nom utilisé dans l'export de `assets/React/index.js`.

### Build des assets

#### Développement avec HMR
```bash
php bin/console react:build --dev
```

#### Production
```bash
php bin/console react:build
```

## Structure du bundle

```
ReactBundle/
├── src/
│   ├── ReactBundle.php              # Classe principale
│   ├── DependencyInjection/         # Configuration
│   ├── Service/                     # Services
│   ├── Twig/                        # Extensions Twig
│   ├── Command/                     # Commandes Symfony
│   └── Composer/                    # Scripts Composer
├── Resources/
│   ├── config/
│   │   └── services.yaml
│   └── views/                       # Templates Twig
├── composer.json
├── package.json
└── vite.config.js
```

## Structure recommandée dans votre projet Symfony

Créez vos composants React **dans votre projet Symfony**, pas dans le bundle :

```
votre-projet-symfony/
├── assets/
│   ├── React/
│   │   ├── Components/              # Vos composants React ici
│   │   │   ├── MyComponent.jsx
│   │   │   ├── Navbar.jsx
│   │   │   └── ...
│   │   └── index.js                 # Export centralisé de tous les composants
│   └── js/
│       └── app.jsx                  # Point d'entrée (importe depuis React/)
├── public/
│   └── build/                       # Assets compilés par Vite
└── config/
    └── packages/
        └── react.yaml               # Configuration du bundle
```

## Créer un nouveau composant React

### Workflow rapide

```
1. Créer le fichier          → assets/React/Components/MonComposant.jsx
2. Exporter dans index.js    → assets/React/index.js
3. Rebuild les assets        → php bin/console react:build
4. Utiliser dans Twig        → {{ react_component('MonComposant', {...}) }}
```

### Étape 1 : Créer le fichier du composant

Créez votre composant dans `assets/React/Components/VotreComposant.jsx` :

```jsx
import React from 'react';

const VotreComposant = ({ title, message, onAction }) => {
    return (
        <div style={{ padding: '20px', border: '1px solid #ccc' }}>
            <h2>{title}</h2>
            <p>{message}</p>
            {onAction && (
                <button onClick={onAction}>Action</button>
            )}
        </div>
    );
};

export default VotreComposant;
```

### Étape 2 : Exporter le composant dans `index.js`

Ajoutez l'export dans `assets/React/index.js` :

```javascript
// ... autres exports existants

// Votre nouveau composant
export { default as VotreComposant } from './Components/VotreComposant';
```

**Important** : Le nom utilisé dans l'export (`VotreComposant`) doit correspondre exactement au nom que vous utiliserez dans Twig.

### Étape 3 : Utiliser le composant dans un template Twig

Dans votre template Twig :

```twig
{% extends '@React/react_base.html.twig' %}

{% block body %}
    {# Utilisez le nom exact de l'export #}
    {{ react_component('VotreComposant', {
        title: 'Mon titre',
        message: 'Mon message personnalisé'
    }) }}
{% endblock %}

{% block javascripts %}
    {{ vite_entry_script_tags('app') }}
{% endblock %}
```

### Étape 4 : Rebuild les assets

Après avoir créé ou modifié un composant :

```bash
# En développement (avec HMR)
php bin/console react:build --dev

# En production
php bin/console react:build
```

## Exemple complet

### 1. Créer `assets/React/Components/ProductCard.jsx`

```jsx
import React from 'react';

const ProductCard = ({ name, price, image, onAddToCart }) => {
    return (
        <div style={{
            border: '1px solid #ddd',
            borderRadius: '8px',
            padding: '20px',
            textAlign: 'center'
        }}>
            <img 
                src={image} 
                alt={name}
                style={{ width: '100%', borderRadius: '4px', marginBottom: '10px' }}
            />
            <h3>{name}</h3>
            <p style={{ fontSize: '1.5rem', fontWeight: 'bold', color: '#ff6b6b' }}>
                {price} €
            </p>
            <button 
                onClick={onAddToCart}
                style={{
                    padding: '10px 20px',
                    backgroundColor: '#ff6b6b',
                    color: 'white',
                    border: 'none',
                    borderRadius: '4px',
                    cursor: 'pointer'
                }}
            >
                Ajouter au panier
            </button>
        </div>
    );
};

export default ProductCard;
```

### 2. Exporter dans `assets/React/index.js`

```javascript
// ... autres exports

export { default as ProductCard } from './Components/ProductCard';
```

### 3. Utiliser dans Twig

```twig
{% extends '@React/react_base.html.twig' %}

{% block body %}
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
        {% for product in products %}
            {{ react_component('ProductCard', {
                name: product.name,
                price: product.price,
                image: product.image,
                onAddToCart: '() => alert("Ajouté au panier!")'
            }) }}
        {% endfor %}
    </div>
{% endblock %}

{% block javascripts %}
    {{ vite_entry_script_tags('app') }}
{% endblock %}
```

### Notes importantes

- ✅ **Créez vos composants dans `assets/React/Components/`** (dans votre projet, pas dans le bundle)
- ✅ **Exportez-les dans `assets/React/index.js`** avec le nom exact que vous utiliserez dans Twig
- ✅ **Le nom est sensible à la casse** : `ProductCard` ≠ `productcard` ≠ `Productcard`
- ✅ **Les props sont passées en JSON** : utilisez des types simples (string, number, boolean, array, object)
- ✅ **Les fonctions JavaScript** peuvent être passées comme chaînes (ex: `'() => alert("test")'`)
- ✅ **Rebuild après chaque modification** : `php bin/console react:build` (ou `--dev` pour HMR)

## Migration depuis Stimulus

1. Identifiez vos contrôleurs Stimulus
2. Créez des composants React équivalents
3. Remplacez `data-controller="..."` par `{{ react_component(...) }}`
4. Testez individuellement

## Configuration avancée

### Personnaliser le serveur Vite

Dans `config/packages/react.yaml` :
```yaml
react:
    build_dir: 'build'
    assets_dir: 'assets'
```

### Variables d'environnement

Vous pouvez définir `VITE_SERVER_URL` dans votre `.env` pour personnaliser l'URL du serveur Vite en développement :

```env
VITE_SERVER_URL=http://localhost:5173
```

Ou dans `config/packages/react.yaml` :

```yaml
react:
    vite_server: 'http://localhost:5173'
```

### Dépannage

#### Les composants ne s'affichent pas
- Vérifiez que `{{ vite_entry_script_tags('app') }}` est présent dans votre template
- Vérifiez la console du navigateur pour les erreurs JavaScript
- Assurez-vous que les assets sont compilés : `php bin/console react:build`
- Vérifiez que le manifest.json existe dans `public/build/.vite/`

#### Erreur "Component not found"
- Vérifiez que le composant est exporté dans `assets/React/index.js` de votre projet Symfony
- Vérifiez que le nom dans l'export correspond exactement au nom utilisé dans Twig (sensible à la casse)
- Vérifiez que le fichier du composant existe dans `assets/React/Components/`
- Vérifiez que vous avez rebuild les assets : `php bin/console react:build`
- Consultez la console du navigateur pour voir la liste des composants disponibles

#### HMR ne fonctionne pas
- Vérifiez que le serveur Vite est démarré : `php bin/console react:build --dev`
- Vérifiez que le port 3000 (ou celui configuré) n'est pas utilisé
- Vérifiez la configuration dans `vite.config.js`
- Vérifiez que `VITE_SERVER_URL` est correctement configuré

#### Erreurs npm/Node.js
- Vérifiez que Node.js >= 18.0.0 est installé : `node --version`
- Vérifiez que npm est installé : `npm --version`
- Si vous utilisez nvm, assurez-vous que l'environnement est correctement chargé

#### Erreurs de chemin (Windows)
- Le bundle supporte maintenant Windows avec `DIRECTORY_SEPARATOR`
- Si vous rencontrez des problèmes, vérifiez les permissions des dossiers
- Assurez-vous que les chemins dans `vite.config.js` sont corrects

## Support

- Documentation complète : voir `QUICKSTART.md`
- Guide d'installation : voir `INSTALLATION.md`
- Signaler un bug : [GitHub Issues](https://github.com/julien-lin/reactBundleSymfony/issues)
- Devenir sponsor : [GitHub Sponsors](https://github.com/sponsors/julien-lin)

## Licence

MIT
