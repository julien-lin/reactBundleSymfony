# Guide de démarrage rapide - ReactBundle

## 1. Installation des dépendances

```bash
cd src/ReactBundle
npm install
```

## 2. Configuration

Le bundle est déjà enregistré dans `config/bundles.php`.

La configuration par défaut dans `config/packages/react.yaml` est :
```yaml
react:
    build_dir: 'build'
    assets_dir: 'assets'
```

## 3. Build des assets

### Développement avec HMR (Hot Module Replacement)
```bash
php bin/console react:build --dev
```
Ou directement :
```bash
cd src/ReactBundle
npm run dev
```

### Production
```bash
php bin/console react:build
```
Ou directement :
```bash
cd src/ReactBundle
npm run build
```

## 4. Utilisation dans vos templates Twig

### Exemple basique

```twig
{% extends '@ReactBundle/react_base.html.twig' %}

{% block body %}
    {{ react_component('ExampleComponent', {
        title: 'Mon titre',
        message: 'Mon message'
    }) }}
{% endblock %}

{% block javascripts %}
    {{ vite_entry_script_tags('js/app.jsx') }}
{% endblock %}
```

### Avec un ID personnalisé

```twig
{{ react_component('ExampleComponent', {
    title: 'Titre',
    message: 'Message'
}, 'mon-id-unique') }}
```

## 5. Créer un nouveau composant React

1. Créez votre composant dans `React/Components/MyComponent.jsx` :
```jsx
import React from 'react';

const MyComponent = ({ title, message }) => {
    return (
        <div>
            <h2>{title}</h2>
            <p>{message}</p>
        </div>
    );
};

export default MyComponent;
```

2. Exportez-le dans `React/index.js` :
```js
export { default as MyComponent } from './Components/MyComponent';
```

3. Ajoutez-le dans `assets/js/app.jsx` :
```jsx
import { MyComponent } from '../../React';

const componentMap = {
    'ExampleComponent': ExampleComponent,
    'MyComponent': MyComponent, // Ajoutez ici
};
```

4. Utilisez-le dans Twig :
```twig
{{ react_component('MyComponent', { title: 'Test', message: 'Hello' }) }}
```

## 6. Migration depuis Stimulus

1. Identifiez votre contrôleur Stimulus
2. Créez un composant React équivalent
3. Remplacez `data-controller="..."` par `{{ react_component(...) }}`
4. Testez individuellement

## 7. Dépannage

### Les composants ne se montent pas
- Vérifiez que `vite_entry_script_tags('js/app.jsx')` est présent dans votre template
- Vérifiez la console du navigateur pour les erreurs
- Assurez-vous que les assets sont compilés (`npm run build`)

### Erreur "Component not found"
- Vérifiez que le composant est exporté dans `React/index.js`
- Vérifiez que le composant est ajouté dans `componentMap` dans `app.jsx`
- Vérifiez l'orthographe du nom du composant dans Twig

### HMR ne fonctionne pas
- Vérifiez que le serveur Vite est démarré (`npm run dev`)
- Vérifiez que le port 3000 n'est pas utilisé
- Vérifiez la configuration dans `vite.config.js`

