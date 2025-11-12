# Pierre - Technical Documentation 🪨

## Overview

Pierre is a modern WordPress plugin that monitors WordPress Polyglots translations and notifies teams via Slack. Built with PHP 8.3+, WordPress 6.0+, and following WordPress Coding Standards + PSR-4.

## Architecture

### Core Components

#### 1. Plugin Class (`src/Pierre/Plugin.php`)
- **Purpose**: Main orchestrator and plugin lifecycle manager
- **Responsibilities**:
  - Plugin initialization and component loading
  - Activation/deactivation/uninstall hooks
  - Database table creation/removal
  - Text domain loading

#### 2. Surveillance System (`src/Pierre/Surveillance/`)

**CronManager** (`CronManager.php`)
- Manages WordPress cron events for surveillance and cleanup
- Custom intervals: 15 minutes (surveillance), daily (cleanup)
- Methods: `schedule_events()`, `clear_events()`, `run_surveillance_check()`

**TranslationScraper** (`TranslationScraper.php`)
- Scrapes translation data from translate.wordpress.org API
- Caching with WordPress transients (1 hour timeout)
- HTTP defaults centralisés (UA/timeout/headers) via `Plugin::get_http_defaults()`
- Détection dynamique du segment (`wp`, `wp-plugins`, `wp-themes`, `meta`, `apps`) avec mémoïsation par `(type,slug)`
- Backoff par projet (respect `Retry-After` 429, fallback 300s) + retry léger 1x sur 5xx/erreur réseau
- Progression de run (transient `pierre_surv_progress`) et arrêt best-effort via transient `pierre_surv_abort`
- Methods: `scrape_project_data()`, `scrape_multiple_projects()`, `calculate_stats()`

**ProjectWatcher** (`ProjectWatcher.php`)
- Core surveillance logic implementing `WatcherInterface`
- Monitors project changes and triggers notifications
- Methods: `start_surveillance()`, `analyze_and_notify()`, `watch_project()`

#### 3. Notification System (`src/Pierre/Notifications/`)

**MessageBuilder** (`MessageBuilder.php`)
- Constructs Slack messages from predefined templates
- Templates for: new strings, completion updates, needs attention, errors
- Methods: `build_new_strings_message()`, `format_template()`

**SlackNotifier** (`SlackNotifier.php`)
- Implements `NotifierInterface` for Slack webhook integration
- Methods: `send_notification()`, `test_notification()`, `is_ready()`

#### 4. Team Management (`src/Pierre/Teams/`)

**RoleManager** (`RoleManager.php`)
- Manages WordPress capabilities and custom roles
- 7 custom capabilities, 3 custom roles
- Methods: `add_capabilities()`, `create_custom_roles()`, `user_has_capability()`

**TeamRepository** (`TeamRepository.php`)
- Database operations for user-project assignments
- Table: `{$wpdb->prefix}pierre_user_projects`
- Methods: `assign_user_to_project()`, `get_user_assignments()`, `remove_user_from_project()`

**UserProjectLink** (`UserProjectLink.php`)
- Business logic for user-project assignments
- Validation, permission checks, assignment management
- Methods: `assign_user_to_project()`, `remove_user_from_project()`, `validate_assignment()`

#### 5. Controllers (`src/Pierre/Admin/` & `src/Pierre/Frontend/`)

**AdminController** (`AdminController.php`)
- WordPress admin interface management
- 5 admin pages: Dashboard, Teams, Projects, Settings, Reports
- AJAX handlers for user management and settings
- Methods: `add_admin_menu()`, `render_*_page()`, `ajax_*()`

**DashboardController** (`DashboardController.php`)
- Public-facing dashboard with routing
- URLs: `/pierre/`, `/pierre/locale/`, `/pierre/locale/project/`
- Template system with fallback rendering
- Methods: `add_rewrite_rules()`, `handle_template_redirect()`, `render_*()`

## Database Schema

### Custom Table: `pierre_user_projects`

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
    PRIMARY KEY (id),
    UNIQUE KEY unique_assignment (user_id, project_slug, locale_code),
    KEY project_locale (project_slug, locale_code),
    KEY user_role (user_id, role)
);
```

## WordPress Integration

### Hooks and Actions

**Activation/Deactivation**:
- `register_activation_hook()` → `Plugin::activate()`
- `register_deactivation_hook()` → `Plugin::deactivate()`
- `register_uninstall_hook()` → `Plugin::uninstall()`

**Cron Events**:
- `pierre_surveillance_check` (every 15 minutes)
- `pierre_cleanup_old_data` (daily)

**Admin Integration**:
- `admin_menu` → Admin menu creation
- `admin_bar_menu` → Admin bar links
- `admin_notices` → Admin notices display

## Admin AJAX Endpoints

New endpoints:

- `pierre_abort_run` (POST, nonce `pierre_admin_ajax`, cap `pierre_manage_projects`): sets abort flag `pierre_abort_run` consumed by CronManager to stop current run/digest.
- `pierre_get_progress` (POST, nonce `pierre_admin_ajax`, cap `pierre_manage_projects`): returns `{ progress:{ processed,total,ts }, aborting:bool }` from transients/options.

Common endpoints:
- `pierre_admin_save_settings`, `pierre_admin_test_notification`, `pierre_fetch_locales`, `pierre_run_surveillance_now`, `pierre_run_cleanup_now`, exports.

## API Integration

### translate.wordpress.org API

**Base URL**: `https://translate.wordpress.org/api/projects`

**Segments**:
- `wp` (core), `wp-plugins` (plugins), `wp-themes` (themes), `meta`, `apps`

**Endpoints**:
- `/{segment}/{project_slug}/{locale_code}/{set}/` - Project translation data (set par défaut: `default`)

**Response Format**:
```json
{
  "name": "Project Name",
  "slug": "project-slug",
  "strings": {
    "total": 1000,
    "translated": 800,
    "untranslated": 200
  },
  "stats": {
    "completion_percentage": 80
  }
}
```

### Slack Webhook Integration

**Webhook URL**: Configured in admin settings

**Payload Format (preferred Blocks + compatible Attachments)**:
```json
{
  "text": "Pierre's message",
  "blocks": [
    {
      "type": "section",
      "text": { "type": "mrkdwn", "text": "Pierre's message" }
    }
  ],
  "attachments": [
    {
      "color": "good",
      "footer": "Pierre - WordPress Translation Monitor",
      "footer_icon": "https://s.w.org/images/wmark.png"
    }
  ]
}
```

## Runtime Metrics

- `pierre_last_surv_duration_ms` — last surveillance run duration (ms)
- `pierre_last_digest_duration_ms` — last digest duration (ms)
- `pierre_last_surv_run` — last run timestamp
- `pierre_last_digest_run` — last digest timestamp

## Logging Policy

- Central logger via `do_action('wp_pierre_debug', $message, $context)`.
- Throttle at 60s per signature in `Plugin::handle_debug()` to avoid log storms.
- Key logs include webhook tests, locales refresh, surveillance run/cleanup, digests, API calls timings/backoff.

## Testing

### Test Structure
- PHPUnit 10.0+ test suite
- Mock WordPress functions in `tests/bootstrap.php`
- Coverage reporting (HTML, text, Clover)

### Test Categories
- Unit tests for individual classes
- Integration tests for component interactions
- Mock external API calls

### Running Tests
```bash
composer test
composer test-coverage
```

## Performance Considerations

### Caching Strategy
- WordPress transients for API responses (1 hour)
- Database query optimization
- Minimal external API calls

### Memory Management
- Efficient data structures
- Proper cleanup in deactivation
- Transient cleanup for old data

## Error Handling

### Logging
- WordPress `error_log()` for debugging
- Graceful degradation on API failures
- User-friendly error messages

### Fallbacks
- Template fallback system
- Default values for missing data
- Graceful handling of missing dependencies

### AJAX Error Contract

All AJAX handlers return a uniform JSON error format:

```json
{
  "success": false,
  "data": {
    "code": "invalid_nonce",
    "message": "Pierre says: Invalid nonce! 😢",
    "details": { "...": "optional" }
  }
}
```

Standard error codes:
- `invalid_nonce`: Nonce invalide ou absent
- `forbidden`: Capacité insuffisante pour l’action
- `invalid_payload`: Charge utile invalide ou paramètres manquants
- `missing_locale`: Code de locale requis manquant
- `empty_library`: Bibliothèque de projets vide
- `partial_failure`: Opération partiellement réussie (voir `details.errors`)
- `no_changes`: Rien à appliquer (aucune modification)
- `slack_test_failed`: Test du webhook Slack échoué (voir `details.error`)
- `cooldown`: Action trop fréquente; respectez l’intervalle
- `upstream_empty`: Données amont (WP.org) vides/indisponibles

Conventions:
- HTTP 403 pour `invalid_nonce` et `forbidden`
- HTTP 400 pour erreurs de validation (`invalid_payload`, `missing_locale`, etc.)
- HTTP 429 pour `cooldown`
- HTTP 5xx pour erreurs amont (`upstream_empty` → 502)

## Development Guidelines

### Code Standards
- WordPress Coding Standards
- PSR-4 autoloading
- PHP 8.3+ features (typed properties, union types)

### Documentation
- PHPDoc comments for all methods
- Inline comments with Pierre's personality
- Technical documentation in `/docs`

### Version Control
- Semantic versioning
- Changelog maintenance
- Git hooks for code quality

## Deployment

### Requirements
- PHP 8.3+
- WordPress 6.0+
- MySQL 5.7+ or MariaDB 10.3+

### Installation
1. Upload plugin to `/wp-content/plugins/`
2. Activate through WordPress admin
3. Configure Slack webhook URL
4. Assign users to projects

### Configuration
- Admin → Pierre → Settings
- Configure Slack webhook
- Set surveillance intervals
- Manage user assignments

---

*Pierre says: This documentation covers all the technical aspects of my plugin! If you need more details, just ask! 🪨*

## Configuration & Storage

### Option `pierre_settings` (structure)
- `slack_webhook_url` (string, legacy convenience)
- `notification_defaults`:
  - `types` (array: `new_strings`, `completion_update`, `needs_attention`, `milestone`)
  - `new_strings_threshold` (int)
  - `milestones` (int[])
  - `mode` (`immediate`|`digest`)
  - `digest` { `type` (`interval`|`fixed_time`), `interval_minutes` (>=15), `fixed_time` (HH:MM) }
- `surveillance_interval` (minutes, default 15)
- `global_webhook` (objet unifié, voir section Unified Webhook Model)
- `locales_slack` (map simplifiée locale→url, legacy)
- `locales`:
  - `[<locale_code>]`:
    - `webhook` (objet unifié pour la locale)
    - `override` (bool) + paramètres `mode/digest/threshold/milestones` si override
- `projects_discovery_library` (liste des projets connus, normalisée)

Conseils:
- Export JSON de l’option (via WP-CLI ou phpMyAdmin) recommandé pour sauvegarde/restauration.

## Discovery (Locales & Projects)

- Sources: API Polyglots + pages Team Polyglots (détection translate_slug/slack) + pages Handbook (liste Slack locaux)
- Locales Discovery: persistées, réutilisées pour filtrer les propositions de projets; enrichissement « fort » hebdo via cron
- Projects Discovery: saisie/chargement d’une « library » (type, slug) ligne par ligne.
- Formats `scopes.projects`: une ligne par projet `type, slug` (ex.: `plugin, woocommerce`).

Limites & cache:
- Ratelimits externes: réponses mises en cache via transients.
- Bouton « refresh » recommandé après 12h si données caduques.

## Surveillance & Cron

- Hooks WP-Cron: `pierre_surveillance_check` (toutes les 15 min par défaut), `pierre_cleanup_old_data` (daily), `pierre_refresh_locales_cache` (weekly).
- Cooldown anti-spam pour exécutions forcées: 2 minutes (global/locale/projet).
- WP-Cron désactivé: planifier via cron système (wp-cron.php) ou `wp cron event run pierre_surveillance_check`.

WP-CLI (exemples):
```bash
wp cron event run pierre_surveillance_check
wp option get pierre_settings --format=json
```

## Événements & Seuils

- `new_strings`: déclenché si nouveaux strings ≥ `threshold`.
Recommandations de presets:
- Sobre: `new_strings_threshold=0`, `milestones=[100]`, `mode=digest (60 min)`
- Active: `new_strings_threshold=20`, `milestones=[50,80,100]`, `mode=immediate`

## Observabilité & Contrôles
- Logs structurés via `do_action('wp_pierre_debug', ...)` (`api_call`, `backoff_set`, `digest_sent`, etc.)
- Progression du run: `pierre_surv_progress` (processed/total)
- Arrêt best-effort: `pierre_abort_surveillance_run` (AJAX) → set transient `pierre_surv_abort`
- `completion_update`: envoi si progression détectée (delta % > 0).
- `needs_attention`: si `waiting`+`fuzzy` > 0.
- `milestone`: si `completion` atteint une valeur listée dans `milestones`.
- `approval`: envoi d’approbations récentes (si exposé par la collecte).

## Slack: tests & exemples

Test rapide via cURL:
```bash
curl -X POST -H 'Content-type: application/json' \
  --data '{"text":"Pierre test webhook 🪨"}' \
  https://hooks.slack.com/services/[TEAM_ID]/[BOT_ID]/[TOKEN]
```

Blocks multi-sections (extrait):
```json
{
  "text": "🧪 Test",
  "blocks": [
    {"type":"section","text":{"type":"mrkdwn","text":"🧪 *Test*"}},
    {"type":"divider"}
  ]
}
```

Politique d’erreur:
- HTTP≠200 ou body≠`ok` → échec consigné via `error_log()`.
- `is_ready()` faux si URL manquante/incorrecte.

## Sécurité & Permissions

- Capacités personnalisées (préfixe `pierre_`) pour pages/admin actions (voir Capabilities).
- AJAX: nonce requis, vérifs `current_user_can()`; sanitization de toutes entrées.
- URLs Slack validées (`hooks.slack.com`) avant sauvegarde.

## Dépannage

- Pas de messages Slack: vérifier URL, tester via bouton/`curl`, consulter logs PHP, vérifier cooldown (2 min), s’assurer que WP-Cron tourne.
- Discovery vide: attendre l’expiration du cache, vérifier connectivité, recharger la library projets.
- Doublons de messages: vérifier overlap Global+Locale (scopes identiques) et ajuster `scopes`.
- Digest non envoyé: confirmer `mode=digest` + fenêtre (`interval_minutes`≥15 ou `fixed_time` HH:MM) et exécution du cron.

## Tests

- PHPUnit 10+, mocks des appels externes (Slack/API Polyglots).
- Catégories: unités (MessageBuilder, SlackNotifier), intégration (ProjectWatcher).
- Coverage disponible via `composer test-coverage`.

## Glossaire

- GTE/PTE: rôles Polyglots.
- `project_type`: `core|plugin|theme|meta|app`.
- `locale_code`: code locale (ex. `fr`, `es_ES`).
- `set`: sous-ensemble de traduction (par défaut `default`).
- `scopes`: filtrage global/local par locales/projets.
- `digest`: regroupement d’événements, `interval` ou `fixed_time`.
