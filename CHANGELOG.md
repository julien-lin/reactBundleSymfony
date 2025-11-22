# Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Ajouté
- Bundle Symfony pour intégrer React avec Vite
- Support du Hot Module Replacement (HMR) en développement
- Fonction Twig `react_component()` pour rendre des composants React
- Fonctions Twig `vite_entry_script_tags()` et `vite_entry_link_tags()` pour Vite
- Commande Symfony `react:build` pour builder les assets
- Installation automatique des dépendances npm via scripts Composer
- Support de Turbo (Hotwire)
- Détection automatique du chemin du bundle (vendor/ ou développement local)
- Templates Twig de base pour React
- Composant d'exemple `ExampleComponent`
- Hook React d'exemple `useExample`

### Sécurité
- Échappement JSON sécurisé des props React

