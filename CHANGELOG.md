# Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### 🚀 Phase 3 - Production Ready (6 décembre 2025)

#### ✅ Sécurité - Améliorations Critiques
- ✅ Validation complète XSS via htmlspecialchars() avec ENT_QUOTES | ENT_HTML5
- ✅ Tests de sécurité complets (11 tests XSS + 4 tests SSRF/URL validation)
- ✅ Validation des noms de composants React (regex)
- ✅ Aucune utilisation de filtre |raw dans les templates Twig

#### ✅ Qualité du Code - 100% Complet
- ✅ `declare(strict_types=1)` ajouté à tous les 8 fichiers PHP
- ✅ PSR-12 compliance: 0 erreurs
- ✅ Duplication de code éliminée via BundlePathResolver
- ✅ 100% de type hints sur les méthodes publiques

#### ✅ Tests - Suite Complète
- ✅ 112 tests passants (170 assertions)
- ✅ Couverture: Sécurité, Intégration, Configuration, Commands
- ✅ Phase 1: 64 tests sécurité
- ✅ Phase 2: 39 tests intégration
- ✅ Phase 3: 9 tests BundlePathResolver

#### 📊 Audit Final de Production
- ✅ Zéro appels `error_log()` détectés
- ✅ Zéro utilisations de |raw dans Twig
- ✅ htmlspecialchars protection validée
- ✅ Audit de sécurité OWASP complété
- ✅ Score production: 7.4/10 ↑ (était 5.7/10)

### 🔨 Techniques
- Ajout de `declare(strict_types=1)` pour une meilleure sécurité de type
- Service `BundlePathResolver` pour centraliser la résolution des chemins
- Tests PHPUnit avec #[DataProvider] attributes
- Infrastructure de test complète avec 112 tests

## [1.0.8] - 2025-11-23

### Corrigé
- Correction critique de l'encodage JSON des props dans les attributs HTML
- Utilisation de guillemets simples pour l'attribut `data-react-props` afin de préserver les guillemets doubles du JSON
- Échappement correct des caractères HTML tout en préservant la validité du JSON
- Les composants React reçoivent maintenant correctement leurs props depuis Twig

### Ajouté
- Guide complet pour ajouter des packages npm (`ADDING_NPM_PACKAGES.md`)
- Support multilingue pour la documentation (README.md en anglais, README.fr.md en français)
- Exemples d'utilisation avec `react-icons` et autres packages npm populaires

### Amélioré
- Documentation README améliorée avec guide détaillé pour créer des composants React
- Section "Adding npm Packages" dans le README
- Workflow visuel pour la création de composants

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

