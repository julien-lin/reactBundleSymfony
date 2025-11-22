# Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.2] - 2025-01-XX

### Amélioré
- Détection automatique de npm dans plusieurs chemins communs (plus user-friendly)
- Vérification automatique de l'installation des dépendances npm avant le build
- Proposition d'installation automatique des dépendances npm si manquantes
- Messages d'erreur plus clairs avec instructions pour résoudre les problèmes

## [1.0.1] - 2025-01-XX

### Corrigé
- Correction de l'alias de l'extension : changement de `react_bundle` vers `react` pour respecter la convention Symfony
- Mise à jour des paramètres de configuration pour utiliser `react.*` au lieu de `react_bundle.*`
- Mise à jour de la documentation pour refléter le changement de nom de fichier de configuration

## [1.0.0] - 2025-01-XX

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
- Support GitHub Sponsors
- Documentation complète (README, QUICKSTART, INSTALLATION)

### Sécurité
- Échappement JSON sécurisé des props React

