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

## Utilisation

### Dans vos templates Twig

```twig
{% extends '@React/react_base.html.twig' %}

{% block body %}
    {{ react_component('ExampleComponent', {
        title: 'Mon titre',
        message: 'Mon message'
    }) }}
{% endblock %}

{% block javascripts %}
    {{ vite_entry_script_tags('app') }}
{% endblock %}
```

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
├── React/
│   ├── Components/                  # Composants React
│   └── hooks/                       # Hooks React
├── assets/
│   └── js/
│       └── app.jsx                   # Point d'entrée
├── Resources/
│   ├── config/
│   │   └── services.yaml
│   └── views/                       # Templates Twig
├── composer.json
├── package.json
└── vite.config.js
```

## Créer un nouveau composant

1. Créez votre composant dans `vendor/julien-lin/react-bundle-symfony/React/Components/MyComponent.jsx`
2. Exportez-le dans `React/index.js`
3. Ajoutez-le dans `componentMap` dans `assets/js/app.jsx`
4. Utilisez-le avec `{{ react_component('MyComponent', {...}) }}`

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
- Vérifiez que le composant est exporté dans `React/index.js` (ou `assets/React/index.js` si vous utilisez votre propre structure)
- Vérifiez que le composant est ajouté dans le `componentMap` dans `app.jsx`
- Vérifiez l'orthographe du nom du composant dans Twig (sensible à la casse)

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
