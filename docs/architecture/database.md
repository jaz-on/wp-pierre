# Database Schema

Schéma de base de données et structure des options WordPress.

## Table Personnalisée

### `pierre_user_projects`

Table pour stocker les assignations utilisateur-projet.

```sql
CREATE TABLE {$wpdb->prefix}pierre_user_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    project_type ENUM('plugin','theme','meta','app') NOT NULL,
    project_slug VARCHAR(200) NOT NULL,
    locale_code VARCHAR(10) NOT NULL,
    role ENUM('locale_manager','gte','pte','contributor','validator') NOT NULL,
    assigned_by BIGINT NOT NULL,
    assigned_at DATETIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    KEY user_id (user_id),
    KEY project_slug (project_slug),
    KEY locale_code (locale_code)
) {$charset_collate};
```

**Colonnes** :
- `id` : Identifiant unique
- `user_id` : ID de l'utilisateur WordPress
- `project_type` : Type de projet (plugin, theme, meta, app)
- `project_slug` : Slug du projet
- `locale_code` : Code de locale (ex: `fr`, `es_ES`)
- `role` : Rôle dans l'équipe de traduction
- `assigned_by` : ID de l'utilisateur qui a fait l'assignation
- `assigned_at` : Date et heure de l'assignation
- `is_active` : Statut actif/inactif

**Index** :
- `user_id` : Recherche rapide par utilisateur
- `project_slug` : Recherche rapide par projet
- `locale_code` : Recherche rapide par locale

## Options WordPress

### `pierre_settings`

Configuration principale du plugin (tableau associatif).

**Structure** :
```php
[
    'surveillance_interval' => 15, // minutes
    'surveillance_enabled' => true,
    'auto_start_surveillance' => true,
    'global_webhook' => [
        'webhook_url' => 'encrypted_url',
        'types' => ['new_strings', 'completion_update', 'needs_attention'],
        'thresholds' => [...],
        'mode' => 'immediate' | 'digest',
        'digest' => [
            'type' => 'interval' | 'fixed_time',
            'interval_minutes' => 60,
            'fixed_time' => '09:00'
        ]
    ],
    'locales' => [
        'fr' => [
            'webhook' => [...],
            'override' => false,
            // ...
        ]
    ],
    'projects_discovery_library' => [
        ['type' => 'plugin', 'slug' => 'woocommerce'],
        // ...
    ]
]
```

### `pierre_watched_projects`

Liste des projets surveillés (tableau associatif).

**Format** :
```php
[
    [
        'type' => 'plugin',
        'slug' => 'woocommerce',
        'locale' => 'fr',
        'added_at' => '2024-01-01 12:00:00'
    ],
    // ...
]
```

### `pierre_locale_managers`

Assignations des Locale Managers par locale.

**Format** :
```php
[
    'fr' => [123, 456], // IDs utilisateurs
    'es' => [789],
    // ...
]
```

### `pierre_gte`

Assignations des GTE par locale.

**Format** : Identique à `pierre_locale_managers`

### `pierre_pte`

Assignations des PTE par locale et projet.

**Format** :
```php
[
    'fr' => [
        'plugin:woocommerce' => [123, 456],
        'theme:twentytwentyfour' => [789],
        // ...
    ],
    // ...
]
```

### `pierre_encryption_key`

Clé de chiffrement pour les webhooks (autoload=false pour sécurité).

**Format** : Chaîne ASCII-safe de defuse/php-encryption

### Options Principales Supplémentaires

#### `pierre_version`

Version du plugin installée.

**Format** : String (ex: `1.0.0`)

**Utilisation** : Suivi de version pour migrations futures

#### `pierre_caps_initialized`

Timestamp d'initialisation des capabilities WordPress.

**Format** : Integer (timestamp Unix)

**Utilisation** : Flag pour éviter la réinitialisation multiple des capabilities

#### `pierre_cache_version`

Version du cache pour invalidation.

**Format** : Integer

**Utilisation** : Incrémenté lors de changements nécessitant l'invalidation du cache

#### `pierre_last_run_now_surveillance`

Timestamp de la dernière exécution manuelle de surveillance.

**Format** : Integer (timestamp Unix)

**Utilisation** : Suivi des exécutions manuelles (cooldown)

#### `pierre_last_run_now_cleanup`

Timestamp de la dernière exécution manuelle de nettoyage.

**Format** : Integer (timestamp Unix)

**Utilisation** : Suivi des exécutions manuelles de nettoyage

#### `pierre_projects_catalog_meta`

Métadonnées du catalogue de projets.

**Format** : Array associatif

**Structure** :
```php
[
    'last_rebuild' => '2024-01-01 12:00:00',
    'total_items' => 1000,
    'version' => 1,
    ...
]
```

#### `pierre_projects_catalog_errors`

Erreurs rencontrées lors de la construction du catalogue.

**Format** : Array d'erreurs

**Structure** :
```php
[
    [
        'type' => 'plugin',
        'slug' => 'example',
        'error' => 'Error message',
        'timestamp' => '2024-01-01 12:00:00'
    ],
    ...
]
```

#### `pierre_projects_discovery`

Bibliothèque de projets pour la découverte.

**Format** : Array de projets

**Structure** :
```php
[
    ['type' => 'plugin', 'slug' => 'woocommerce'],
    ['type' => 'theme', 'slug' => 'twentytwentyfour'],
    ...
]
```

#### `pierre_security_logs`

Logs de sécurité et d'audit.

**Format** : Array de logs

**Structure** :
```php
[
    [
        'event_type' => 'csrf_validation_failed',
        'timestamp' => '2024-01-01 12:00:00',
        'ip' => '127.0.0.1',
        'user_id' => 1,
        'details' => [...]
    ],
    ...
]
```

#### `pierre_settings_schema_version`

Version du schéma des réglages.

**Format** : Integer

**Utilisation** : Migration automatique des réglages lors de changements de schéma

### Options avec Patterns (LIKE Queries)

Ces options utilisent des patterns pour stocker plusieurs valeurs :

#### `pierre_last_forced_scan_{project_type}_{project_slug}_{locale_code}`

Timestamp du dernier scan forcé pour un projet spécifique.

**Format** : Integer (timestamp Unix)

**Exemple** : `pierre_last_forced_scan_plugin_woocommerce_fr`

#### `pierre_catalog_fetch_{type}_{page}`

Cache des pages du catalogue récupérées.

**Format** : Array de données de catalogue

**Exemple** : `pierre_catalog_fetch_plugin_1`, `pierre_catalog_fetch_theme_2`

#### `pierre_projects_catalog_{type}_{page}`

Pages du catalogue stockées comme options (pour pagination).

**Format** : Array de projets

**Exemple** : `pierre_projects_catalog_plugin_1`, `pierre_projects_catalog_theme_1`

**Note** : Ces options sont supprimées lors de l'uninstall via pattern matching.

### Options de Runtime

**Métriques** :
- `pierre_last_surv_run` : Timestamp de la dernière surveillance
- `pierre_last_surv_duration_ms` : Durée de la dernière surveillance (ms)
- `pierre_last_digest_run` : Timestamp du dernier digest
- `pierre_last_digest_duration_ms` : Durée du dernier digest (ms)
- `pierre_last_cleanup_run` : Timestamp du dernier nettoyage

**Cache** :
- `pierre_segments_cache` : Cache de résolution des segments API (format: `['wp' => 'wp', 'woocommerce' => 'wp-plugins', ...]`)
- `pierre_projects_catalog_*` : Cache du catalogue de projets (voir ci-dessus)
- Transients `pierre_*` : Cache temporaire des données API

**État** :
- `pierre_caps_initialized` : Timestamp d'initialisation des capabilities (voir ci-dessus)
- `pierre_cache_version` : Version du cache pour invalidation (voir ci-dessus)

## Transients

Les transients sont utilisés pour le cache temporaire :

**Format** : `pierre_*`

**Transients Identifiés** :

- `pierre_project_{type}_{slug}_{locale}` : Cache des données de projet (1 heure)
  - Exemple : `pierre_project_plugin_woocommerce_fr`
- `pierre_surv_progress` : Progression de la surveillance en cours (15 minutes)
  - Format : `['processed' => 5, 'total' => 10, 'ts' => timestamp]`
- `pierre_surv_abort` : Flag d'arrêt de la surveillance (5 minutes)
  - Format : `1` (si actif)
- `pierre_last_surv_errors` : Erreurs de la dernière surveillance (24 heures)
  - Format : Array d'erreurs
- `pierre_scraper_backoff_until` : Backoff global du scraper (300 secondes)
- `pierre_scraper_backoff_until_{type}_{slug}` : Backoff par projet (300 secondes)
  - Exemple : `pierre_scraper_backoff_until_plugin_woocommerce`
- `pierre_digest_queue_{locale}` : File d'attente des digests par locale (12 heures)
  - Exemple : `pierre_digest_queue_fr`
  - Format : Array de notifications en attente
- `pierre_catalog_progress` : Progression de la reconstruction du catalogue (2 heures)
  - Format : `['started' => timestamp, 'processed' => 500, 'total' => 1000]`
- `pierre_locales_fetch_running` : Flag de fetch des locales en cours (15 minutes)
  - Format : Timestamp du début
- `pierre_slack_workspaces` : Liste des workspaces Slack disponibles (12 heures)
  - Format : Array de workspaces
- `pierre_wp_latest_version` : Dernière version WordPress connue (1 heure)
  - Format : String (ex: `6.4`)
- `pierre_admin_notice` : Notice admin à afficher (transient)
- `pierre_admin_error` : Erreur admin à afficher (transient)
- `pierre_catalog_{cache_key}_timeout` : Timeout pour le cache du catalogue (15 minutes)
- `pierre_log_{signature}` : Throttling des logs debug (60 secondes)
  - Format : `1` (si déjà loggé)
  - Signature : MD5 du message + contexte

**Durée** : Variable selon le type
- Cache API : 1 heure
- Progression : 15 minutes
- Backoff : 300 secondes (5 minutes)
- Digests : 12 heures
- Catalogue : 2 heures
- Workspaces : 12 heures
- Logs throttling : 60 secondes

## Migration et Maintenance

### Création de la table

La table est créée automatiquement lors de l'activation via `dbDelta()`.

### Nettoyage

Le cron quotidien nettoie automatiquement :
- Transients expirés (> 7 jours)
- Erreurs de surveillance anciennes (> 24 heures)

### Sauvegarde

**Recommandé** : Exporter l'option `pierre_settings` via WP-CLI ou phpMyAdmin pour sauvegarde/restauration.

```bash
wp option get pierre_settings --format=json > pierre-settings-backup.json
```

---

*Pierre says: Understanding the database structure helps you maintain and troubleshoot my plugin! 🪨*

