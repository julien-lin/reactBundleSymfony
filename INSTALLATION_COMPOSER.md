# Installation du ReactBundle via Composer

Ce bundle est conçu pour être installé via Composer comme un package indépendant.

## Structure du package Composer

Le bundle suit la structure standard d'un package Composer :

```
ReactBundle/
├── src/                    # Code source PHP (namespace ReactBundle\)
│   ├── ReactBundle.php
│   ├── DependencyInjection/
│   ├── Service/
│   ├── Twig/
│   ├── Command/
│   └── Composer/
├── React/                  # Composants React
├── assets/                 # Assets source
├── Resources/              # Ressources (config, views)
├── composer.json           # Configuration Composer
├── package.json            # Configuration npm
└── vite.config.js          # Configuration Vite
```

## Installation dans un projet Symfony

### 1. Ajouter le bundle à votre composer.json

Si le bundle est publié sur Packagist :
```json
{
    "require": {
        "julien-lin/react-bundle-symfony": "^1.0"
    }
}
```

Si le bundle est en développement local, ajoutez-le comme repository :
```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./src/ReactBundle"
        }
    ],
    "require": {
        "julien-lin/react-bundle-symfony": "@dev"
    }
}
```

### 2. Installer via Composer

```bash
composer require julien-lin/react-bundle-symfony
```

Le script `post-install-cmd` installera automatiquement les dépendances npm.

### 3. Enregistrer le bundle

Si Symfony Flex n'a pas enregistré automatiquement le bundle, ajoutez-le dans `config/bundles.php` :

```php
return [
    // ...
    ReactBundle\ReactBundle::class => ['all' => true],
];
```

### 4. Configurer le bundle

Créez `config/packages/react.yaml` :

```yaml
react:
    build_dir: 'build'
    assets_dir: 'assets'
```

### 5. Installer les dépendances npm (si nécessaire)

Si l'installation automatique a échoué :

```bash
cd vendor/julien-lin/react-bundle-symfony
npm install
```

### 6. Builder les assets

```bash
php bin/console react:build
```

## Développement local

Pour développer le bundle localement avant de le publier :

1. Placez le bundle dans `src/ReactBundle/` de votre projet
2. Ajoutez-le comme repository path dans `composer.json`
3. Installez-le avec `composer require julien-lin/react-bundle-symfony:@dev`

## Publication sur Packagist

Pour publier le bundle sur Packagist :

1. Créez un compte sur [Packagist.org](https://packagist.org)
2. Créez un repository Git (GitHub, GitLab, etc.)
3. Poussez votre code
4. Soumettez le package sur Packagist
5. Configurez un webhook pour la mise à jour automatique

## Personnalisation du namespace

Pour changer le namespace du bundle :

1. Modifiez `composer.json` :
```json
{
    "name": "votre-vendor/votre-nom-bundle",
    "autoload": {
        "psr-4": {
            "VotreVendor\\VotreNomBundle\\": "src/"
        }
    }
}
```

2. Renommez tous les namespaces dans les fichiers PHP
3. Mettez à jour `services.yaml` avec le nouveau namespace

## Notes importantes

- Le bundle installe automatiquement les dépendances npm via le script Composer
- Les assets sont compilés dans `public/build/` par défaut
- Le bundle est compatible avec Symfony 6.0+ et 7.0+
- React 19 est requis pour les composants

