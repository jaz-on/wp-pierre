# Constantes

Documentation de toutes les constantes définies par WP-Pierre.

## Constantes Principales

### `PIERRE_VERSION`

Version actuelle du plugin.

**Valeur** : `'1.0.0'`

**Définition** : `wp-pierre.php`

**Utilisation** : Versioning, cache busting pour assets

### `PIERRE_PLUGIN_FILE`

Chemin absolu vers le fichier principal du plugin.

**Valeur** : `__FILE__` (dans wp-pierre.php)

**Définition** : `wp-pierre.php`

**Utilisation** : Hooks d'activation/désactivation

### `PIERRE_PLUGIN_DIR`

Répertoire du plugin (chemin absolu).

**Valeur** : `plugin_dir_path(__FILE__)`

**Définition** : `wp-pierre.php`

**Utilisation** : Inclusion de fichiers

### `PIERRE_PLUGIN_URL`

URL du plugin.

**Valeur** : `plugin_dir_url(__FILE__)`

**Définition** : `wp-pierre.php`

**Utilisation** : Enqueue d'assets (CSS/JS)

### `PIERRE_PLUGIN_BASENAME`

Nom de base du plugin.

**Valeur** : `plugin_basename(__FILE__)`

**Définition** : `wp-pierre.php`

**Utilisation** : Filtres de liens de plugin

### `PIERRE_DEBUG`

Mode debug activé.

**Valeur** : `true` si `WP_DEBUG` est activé (sinon non défini)

**Définition** : `wp-pierre.php` (conditionnel)

**Utilisation** : Activation du logging détaillé

### `PIERRE_COMPOSER_MISSING`

Flag indiquant que l'autoloader Composer est manquant.

**Valeur** : `true` si Composer autoload absent

**Définition** : `wp-pierre.php` (conditionnel)

**Utilisation** : Affichage d'un avertissement en mode debug

## Constantes Performance

Définies dans `src/Pierre/Performance/performance-config.php` :

### Cache Timeouts

- `PIERRE_CACHE_API_TIMEOUT` : 15 minutes (900 secondes)
- `PIERRE_CACHE_DB_TIMEOUT` : 5 minutes (300 secondes)
- `PIERRE_CACHE_DASHBOARD_TIMEOUT` : 2 minutes (120 secondes)
- `PIERRE_CACHE_REPORTS_TIMEOUT` : 1 heure (3600 secondes)

### Batch Sizes

- `PIERRE_BATCH_SIZE_SMALL` : 5
- `PIERRE_BATCH_SIZE_MEDIUM` : 10
- `PIERRE_BATCH_SIZE_LARGE` : 20

### Memory

- `PIERRE_MEMORY_LIMIT_MB` : 256
- `PIERRE_MEMORY_CHECK_INTERVAL` : 10

### Database

- `PIERRE_DB_QUERY_LIMIT` : 100
- `PIERRE_DB_CONNECTION_TIMEOUT` : 30 secondes

### API

- `PIERRE_API_RATE_LIMIT` : 10 requêtes/minute
- `PIERRE_API_TIMEOUT` : 30 secondes
- `PIERRE_API_RETRY_ATTEMPTS` : 3

### Surveillance

- `PIERRE_SURVEILLANCE_INTERVAL_MIN` : 5 minutes
- `PIERRE_SURVEILLANCE_INTERVAL_MAX` : 60 minutes
- `PIERRE_SURVEILLANCE_BATCH_SIZE` : 5 projets

### Notifications

- `PIERRE_NOTIFICATION_BATCH_SIZE` : 5
- `PIERRE_NOTIFICATION_DELAY_MS` : 1000 millisecondes

---

*Pierre says: These constants control my behavior and performance! 🪨*

