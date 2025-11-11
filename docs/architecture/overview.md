# Architecture Overview

Vue d'ensemble de l'architecture technique du plugin WP-Pierre.

## Table des Matières

- [Structure du Code](#structure-du-code)
- [Composants Principaux](#composants-principaux)
- [Intégration WordPress](#intégration-wordpress)
- [Flux de Données](#flux-de-données)
- [Sécurité](#sécurité)
- [Performance](#performance)
- [Interfaces et Traits](#interfaces-et-traits)
- [Fonctions Helper](#fonctions-helper)

## Structure du Code

Le plugin suit une architecture modulaire organisée en namespaces PSR-4 :

```text
src/Pierre/
├── Plugin.php              # Point d'entrée principal
├── Admin/                   # Interface d'administration
│   ├── AdminController.php
│   └── Handlers/
├── Frontend/                # Interface publique
│   └── DashboardController.php
├── Surveillance/           # Système de surveillance
│   ├── CronManager.php
│   ├── ProjectWatcher.php
│   └── TranslationScraper.php
├── Notifications/          # Système de notifications
│   ├── SlackNotifier.php
│   ├── MessageBuilder.php
│   └── Digest.php
├── Teams/                  # Gestion des équipes
│   ├── RoleManager.php
│   ├── TeamRepository.php
│   └── UserProjectLink.php
├── Settings/               # Gestion des réglages
│   └── Settings.php
├── Security/               # Sécurité et chiffrement
│   ├── SecurityManager.php
│   ├── Encryption.php
│   └── CSRFProtection.php
├── Performance/            # Optimisations
│   ├── CacheManager.php
│   └── PerformanceOptimizer.php
└── Logging/                # Système de logging
    └── Logger.php
```

## Composants Principaux

### 1. Plugin Class (`src/Pierre/Plugin.php`)

Point d'entrée principal et gestionnaire du cycle de vie du plugin.

**Responsabilités** :
- Initialisation du plugin et chargement des composants
- Hooks d'activation/désactivation/désinstallation
- Création/suppression des tables de base de données
- Chargement du text domain
- Gestion des hooks WordPress

**Méthodes principales** :
- `init()` : Initialise tous les composants
- `activate()` : Crée les tables, configure les capabilities, planifie les cron
- `deactivate()` : Nettoie les événements cron
- `uninstall()` : Supprime toutes les données

### 2. Système de Surveillance (`src/Pierre/Surveillance/`)

#### CronManager
Gère les événements WordPress cron pour la surveillance et le nettoyage.

**Intervalles personnalisés** :
- `pierre_5min`, `pierre_15min`, `pierre_30min`, `pierre_60min`, `pierre_120min`
- `pierre_daily` : Nettoyage quotidien
- `pierre_weekly` : Rafraîchissement hebdomadaire des locales

**Hooks** :
- `pierre_surveillance_check` : Vérification périodique des traductions
- `pierre_cleanup_old_data` : Nettoyage des données anciennes
- `pierre_refresh_locales_cache` : Rafraîchissement du cache des locales
- `pierre_run_digest` : Envoi des digests

#### TranslationScraper
Récupère les données de traduction depuis l'API translate.wordpress.org.

**Fonctionnalités** :
- Cache avec transients WordPress (1 heure)
- Détection dynamique du segment (`wp`, `wp-plugins`, `wp-themes`, `meta`, `apps`)
- Backoff par projet (respect `Retry-After` 429, fallback 300s)
- Retry automatique sur erreurs 5xx/erreur réseau
- Suivi de progression via transients

**Méthodes principales** :
- `scrape_typed_project()` : Récupère les données d'un projet typé
- `scrape_multiple_projects()` : Traite plusieurs projets en lot
- `make_api_request()` : Effectue les requêtes HTTP sécurisées

#### ProjectWatcher
Logique principale de surveillance implémentant `WatcherInterface`.

**Responsabilités** :
- Surveillance des changements de projets
- Déclenchement des notifications
- Analyse des statistiques de traduction

**Méthodes principales** :
- `start_surveillance()` : Démarre une surveillance complète
- `analyze_and_notify()` : Analyse et envoie les notifications
- `watch_project()` : Surveille un projet spécifique

### 3. Système de Notifications (`src/Pierre/Notifications/`)

#### SlackNotifier
Implémente `NotifierInterface` pour l'intégration avec Slack.

**Fonctionnalités** :
- Support des webhooks Slack
- Format Block Kit + Attachments (compatibilité)
- Chiffrement des URLs de webhook
- Gestion des erreurs et retry

**Méthodes principales** :
- `send_notification()` : Envoie une notification
- `test_notification()` : Teste la configuration
- `is_ready()` : Vérifie si le système est prêt

#### MessageBuilder
Construit les messages Slack à partir de templates prédéfinis.

**Templates disponibles** :
- Nouveaux strings
- Mises à jour de complétion
- Besoin d'attention
- Erreurs

#### Digest
Gère le regroupement et l'envoi des notifications en mode digest.

**Modes** :
- `interval` : Envoi toutes les X minutes
- `fixed_time` : Envoi à une heure fixe (HH:MM)

### 4. Gestion des Équipes (`src/Pierre/Teams/`)

#### RoleManager
Gère les capabilities WordPress et les rôles personnalisés.

**Capabilities** :
- 7 capabilities standards
- 4 meta capabilities dynamiques

Voir [Capabilities & Permissions](../team-management/capabilities.md) pour plus de détails.

#### TeamRepository
Opérations de base de données pour les assignations utilisateur-projet.

**Table** : `{$wpdb->prefix}pierre_user_projects`

**Méthodes principales** :
- `assign_user_to_project()` : Assigne un utilisateur à un projet
- `get_user_assignments()` : Récupère les assignations d'un utilisateur
- `remove_user_from_project()` : Supprime une assignation

#### UserProjectLink
Logique métier pour les assignations utilisateur-projet.

**Responsabilités** :
- Validation des assignations
- Vérification des permissions
- Gestion de l'historique

### 5. Contrôleurs (`src/Pierre/Admin/` & `src/Pierre/Frontend/`)

#### AdminController
Gère l'interface d'administration WordPress.

**Pages** :
- Dashboard
- Locales
- Projects
- Teams
- Reports
- Settings

**Handlers AJAX** : Gestion des actions asynchrones (~60 endpoints)

**Handlers** :
- `TeamsHandler` : Gestion des équipes

Voir [Interface Admin](../admin/) et [Endpoints AJAX](../api/ajax-endpoints/) pour plus de détails.

#### DashboardController
Gère le tableau de bord public avec routage.

**URLs** :
- `/pierre/` : Tableau de bord principal
- `/pierre/locale/{locale}/` : Vue par locale
- `/pierre/locale/{locale}/project/{type}/{slug}/` : Vue par projet

**Routage** : Utilise WordPress rewrite rules

### 6. Système de Découverte (`src/Pierre/Discovery/`)

#### ProjectsCatalog
Gère le catalogue de projets disponibles pour la découverte.

**Fonctionnalités** :
- Construction du catalogue depuis translate.wordpress.org
- Cache paginé des projets
- Recherche et filtrage
- Export/import de la bibliothèque

**Méthodes principales** :
- `rebuild()` : Reconstruit le catalogue
- `fetch()` : Récupère une page du catalogue
- `search()` : Recherche dans le catalogue

### 7. Services (`src/Pierre/Services/`)

#### NotificationService
Service centralisé pour la gestion des notifications.

**Responsabilités** :
- Orchestration des notifications
- Gestion des digests
- Regroupement par locale
- Application des seuils

### 8. Container (`src/Pierre/Container.php`)

Système d'injection de dépendances simple.

**Fonctionnalités** :
- Stockage de services
- Résolution de dépendances
- Injection dans les contrôleurs

**Services enregistrés** :
- `SlackNotifier`
- `ProjectWatcher`
- `CronManager`
- `RoleManager`
- `TeamRepository`

### 9. Traits (`src/Pierre/Traits/` & `src/Pierre/Notifications/`)

#### StatusTrait
Trait fournissant des fonctionnalités de gestion de statut.

**Utilisé par** : Classes nécessitant un suivi de statut

#### SlackDebugTrait
Trait fournissant du logging debug pour les classes Slack.

**Méthodes** :
- `is_debug()` : Vérifie si debug activé
- `log_debug()` : Log un message

**Utilisé par** : Classes liées à Slack

Voir [Interfaces et Traits](interfaces-traits.md) pour plus de détails.

### 10. Admin Handlers (`src/Pierre/Admin/Handlers/`)

#### TeamsHandler
Handler pour la gestion des équipes.

**Responsabilités** :
- Rendu de la page Teams
- Formatage des rôles et capabilities
- Préparation des données pour les templates

### 11. Settings Fields (`src/Pierre/Admin/SettingsFields.php`)

Gestion des champs de réglages.

**Responsabilités** :
- Définition des champs de formulaire
- Validation et sanitization
- Rendu des champs

## Intégration WordPress

### Hooks et Actions

**Cycle de vie** :
- `register_activation_hook()` → `Plugin::activate()`
- `register_deactivation_hook()` → `Plugin::deactivate()`
- `register_uninstall_hook()` → `Plugin::uninstall()`

**Cron** :
- `cron_schedules` : Enregistrement des intervalles personnalisés
- `pierre_surveillance_check` : Surveillance périodique
- `pierre_cleanup_old_data` : Nettoyage quotidien

**Admin** :
- `admin_menu` : Création du menu admin
- `admin_bar_menu` : Liens dans la barre d'admin
- `admin_notices` : Affichage des notices

### Base de Données

#### Table personnalisée : `pierre_user_projects`

```sql
CREATE TABLE pierre_user_projects (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    user_id bigint(20) unsigned NOT NULL,
    project_type varchar(50) NOT NULL,
    project_slug varchar(100) NOT NULL,
    locale_code varchar(10) NOT NULL,
    role varchar(50) NOT NULL,
    assigned_by bigint(20) unsigned NOT NULL,
    assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
    is_active tinyint(1) DEFAULT 1,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY project_slug (project_slug),
    KEY locale_code (locale_code)
);
```

#### Options WordPress

- `pierre_settings` : Configuration principale
- `pierre_watched_projects` : Liste des projets surveillés
- `pierre_locale_managers` : Assignations Locale Managers
- `pierre_gte` : Assignations GTE
- `pierre_pte` : Assignations PTE
- `pierre_encryption_key` : Clé de chiffrement (autoload=false)

## Flux de Données

### Surveillance

1. **Cron déclenche** `pierre_surveillance_check`
2. **ProjectWatcher** récupère la liste des projets surveillés
3. **TranslationScraper** récupère les données depuis l'API
4. **ProjectWatcher** compare avec les données précédentes
5. **NotificationService** envoie les notifications si nécessaire

### Notifications

1. **Détection d'un changement** dans ProjectWatcher
2. **MessageBuilder** construit le message selon le template
3. **SlackNotifier** envoie via webhook Slack
4. **Logging** des résultats (succès/échec)

## Sécurité

### SecurityManager
Gestionnaire centralisé de sécurité.

**Fonctionnalités** :
- Audit de sécurité
- Vérification des versions WordPress
- Détection de vulnérabilités

### Encryption
Système de chiffrement pour les données sensibles.

**Méthodes** :
- `encrypt()` : Chiffre une chaîne
- `decrypt()` : Déchiffre une chaîne

**Algorithme** : defuse/php-encryption (recommandé par WordPress) avec fallback OpenSSL

### CSRFProtection
Protection contre les attaques CSRF.

**Fonctionnalités** :
- Génération de tokens
- Validation des tokens
- Rate limiting
- Logging des tentatives échouées

### SecurityAuditor
Audit de sécurité du plugin.

**Fonctionnalités** :
- Vérification des bonnes pratiques
- Détection de problèmes de sécurité
- Recommandations

### Chiffrement

- Utilisation de `defuse/php-encryption` (recommandé par WordPress)
- Fallback OpenSSL avec IV uniques
- Clés stockées de manière sécurisée dans les options

### Validation

- Toutes les entrées utilisateur sont sanitizées
- Nonces WordPress pour toutes les actions AJAX
- Vérification des capabilities avant chaque action
- Validation des URLs avant utilisation

### Protection CSRF

- Nonces WordPress pour toutes les requêtes
- Vérification du referrer
- Rate limiting pour prévenir les abus

## Performance

### CacheManager
Gestionnaire de cache centralisé.

**Fonctionnalités** :
- Cache API (15 minutes)
- Cache base de données (5 minutes)
- Cache dashboard (2 minutes)
- Invalidation par groupe ou pattern
- Support object cache si disponible

**Méthodes principales** :
- `get()` : Récupère depuis le cache
- `set()` : Stocke dans le cache
- `delete()` : Supprime du cache
- `flush()` : Vide le cache

### PerformanceOptimizer
Optimiseur de performance.

**Fonctionnalités** :
- Traitement par lots
- Optimisation des requêtes WordPress
- Gestion de la mémoire
- Cache intelligent

**Configuration** : Via `performance-config.php`

### Cache

- **Transients WordPress** : Cache des réponses API (1 heure)
- **Object Cache** : Support si disponible via `wp_using_ext_object_cache()`
- **Mémoïsation** : Cache en mémoire pour les settings

### Optimisations

- Traitement par lots pour les opérations en masse
- Backoff intelligent pour éviter la surcharge de l'API
- Nettoyage automatique des données anciennes
- Requêtes SQL optimisées avec index appropriés

## Interfaces et Traits

### WatcherInterface
Interface pour les composants de surveillance.

**Implémentations** : `ProjectWatcher`

Voir [Interfaces et Traits](interfaces-traits.md) pour plus de détails.

### NotifierInterface
Interface pour les composants de notification.

**Implémentations** : `SlackNotifier`

Voir [Interfaces et Traits](interfaces-traits.md) pour plus de détails.

## Fonctions Helper

### `pierre()`
Fonction principale retournant l'instance du plugin (singleton).

### `pierre_decrypt_webhook()`
Décrypte une URL de webhook chiffrée.

Voir [Fonctions Helper](helpers.md) pour plus de détails.

---

*Pierre says: This architecture ensures my plugin is maintainable, secure, and performant! 🪨*

