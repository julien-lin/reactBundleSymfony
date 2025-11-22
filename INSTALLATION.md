# Installation complète du ReactBundle

## Étape 1 : Installation des dépendances npm

```bash
cd www/src/ReactBundle
npm install
```

## Étape 2 : Vérification de la configuration

Le bundle est déjà enregistré dans `config/bundles.php`.

Vérifiez la configuration dans `config/packages/react_bundle.yaml` :
```yaml
react_bundle:
    build_dir: 'build'
    assets_dir: 'assets'
```

## Étape 3 : Build initial des assets

```bash
# Depuis le répertoire www
php bin/console react:build
```

Ou directement :
```bash
cd www/src/ReactBundle
npm run build
```

## Étape 4 : Test de l'installation

1. Démarrez le serveur Symfony
2. Visitez `/react-example` pour voir un exemple fonctionnel
3. Vérifiez que les composants React se montent correctement

## Étape 5 : Développement

Pour le développement avec HMR :
```bash
# Terminal 1 : Serveur Symfony
symfony server:start

# Terminal 2 : Serveur Vite
cd www/src/ReactBundle
npm run dev
```

## Structure créée

```
www/src/ReactBundle/
├── ReactBundle.php                    # Classe principale du bundle
├── DependencyInjection/               # Configuration du bundle
│   ├── ReactExtension.php
│   └── Configuration.php
├── Service/
│   └── ReactRenderer.php             # Service pour rendre les composants
├── Twig/
│   ├── ReactExtension.php            # Fonction Twig react_component()
│   └── ViteExtension.php             # Fonction Twig vite_entry_script_tags()
├── Command/
│   └── ReactAssetsBuildCommand.php   # Commande react:build
├── React/
│   ├── Components/
│   │   └── ExampleComponent.jsx      # Composant d'exemple
│   ├── hooks/
│   │   └── useExample.js              # Hook d'exemple
│   └── index.js                       # Export centralisé
├── assets/
│   └── js/
│       └── app.jsx                    # Point d'entrée React
├── Resources/
│   ├── config/
│   │   └── services.yaml              # Configuration des services
│   └── views/
│       ├── react_base.html.twig       # Template de base
│       ├── react_component.html.twig  # Template pour les composants
│       └── example.html.twig          # Exemple d'utilisation
├── package.json                       # Dépendances npm
├── vite.config.js                     # Configuration Vite
└── README.md                          # Documentation
```

## Prochaines étapes

1. Créez vos propres composants React dans `React/Components/`
2. Exportez-les dans `React/index.js`
3. Ajoutez-les dans `componentMap` dans `assets/js/app.jsx`
4. Utilisez-les dans vos templates Twig avec `{{ react_component(...) }}`
5. Migrez progressivement vos contrôleurs Stimulus vers React

## Support

Consultez `README.md` et `QUICKSTART.md` pour plus d'informations.

