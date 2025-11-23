# Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.7] - 2025-11-23

### Ajouté
- Support de la variable d'environnement `VITE_SERVER_URL` pour personnaliser l'URL du serveur Vite
- ErrorBoundary React pour gérer les erreurs de composants
- Vérification automatique de la version Node.js (avertissement si < 18)
- Validation et gestion d'erreur pour les props JSON
- Support complet de Windows avec `DIRECTORY_SEPARATOR`
- Prévention du double montage des composants React
- Amélioration de la gestion des erreurs dans `ReactRenderer`

### Corrigé
- Gestion des chemins pour Windows (utilisation de `DIRECTORY_SEPARATOR`)
- Détection du manifest dans `renderViteLinkTags` avec support des clés alternatives
- Normalisation des chemins dans toutes les méthodes

### Amélioré
- Documentation complétée avec section dépannage détaillée
- Messages d'erreur plus explicites
- Gestion d'erreur plus robuste dans tous les composants

## [1.0.6] - 2025-01-XX

### Corrigé
- Correction du calcul du projet root : remonter de 3 niveaux depuis vendor/ au lieu de 2
- Support du manifest dans `.vite/manifest.json` (structure Vite standard)
- Priorité au build de production si le manifest existe (même en mode dev)
- Amélioration de la détection du manifest avec fallback

## [1.0.5] - 2025-01-XX

### Corrigé
- Correction du namespace Twig : utilisation de `@React` au lieu de `@ReactBundle` (convention Symfony)
- Mise à jour de la documentation et des exemples avec le bon namespace

## [1.0.4] - 2025-01-XX

### Corrigé
- Support complet de nvm (Node Version Manager) : npm trouvé dans nvm charge maintenant correctement l'environnement Node.js
- Correction du problème "env: 'node': No such file or directory" lors de l'utilisation de npm via nvm
- Les commandes npm sont maintenant exécutées via bash avec les variables d'environnement nvm chargées

## [1.0.3] - 2025-01-XX

### Amélioré
- Installation automatique des dépendances npm lors de `composer install/update` (plus user-friendly)
- Vérification si `node_modules` existe déjà pour éviter les réinstallations inutiles
- Messages plus clairs dans le ScriptHandler avec instructions de fallback
- Timeout augmenté pour les installations npm lentes (600s au lieu de 300s)

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

