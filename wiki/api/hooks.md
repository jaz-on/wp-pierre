# Hooks WordPress

Documentation complète de tous les hooks WordPress (actions et filtres) utilisés et fournis par WP-Pierre.

## Table des Matières

- [Vue d'Ensemble](#vue-densemble)
- [Actions Fournies par WP-Pierre](#actions-fournies-par-wp-pierre)
- [Filtres Fournis par WP-Pierre](#filtres-fournis-par-wp-pierre)
- [Actions WordPress Utilisées](#actions-wordpress-utilisées)
- [Filtres WordPress Utilisés](#filtres-wordpress-utilisés)
- [Exemples d'Utilisation Avancée](#exemples-dutilisation-avancée)
- [Bonnes Pratiques](#bonnes-pratiques)
- [Dépannage](#dépannage)

## Vue d'Ensemble

WP-Pierre utilise et fournit plusieurs hooks WordPress pour permettre l'extension et la personnalisation du plugin. Les hooks sont organisés en deux catégories :

- **Actions** : Pour exécuter du code à des moments précis
- **Filtres** : Pour modifier des données avant leur utilisation

## Actions Fournies par WP-Pierre

### `wp_pierre_debug`

Action centralisée pour le logging de debug avec throttling automatique.

**Déclenchement** : Utilisé par tous les composants du plugin pour logger des messages de debug

**Paramètres** :
- `$message` (string) : Message de debug
- `$context` (array) : Contexte additionnel
  - `source` (string) : Source du log (ex: `Plugin`, `AdminController`)
  - `scope` (string) : Portée (ex: `admin`, `surveillance`)
  - `action` (string) : Action en cours
  - `code` (int) : Code HTTP ou d'erreur

**Throttling** : Les messages identiques sont throttlés à 60 secondes pour éviter les logs répétitifs

**Exemple d'utilisation** :
```php
do_action('wp_pierre_debug', 'Surveillance started', [
    'source' => 'ProjectWatcher',
    'scope' => 'surveillance',
    'action' => 'start_surveillance'
]);
```

**Note** : Cette action ne fait rien si `PIERRE_DEBUG` n'est pas défini à `true`.

### `pierre_refresh_locales_cache`

Déclenche le rafraîchissement du cache des locales disponibles.

**Déclenchement** : Par le cron hebdomadaire ou manuellement

**Paramètres** : Aucun

**Exemple d'utilisation** :
```php
// Rafraîchir le cache manuellement
do_action('pierre_refresh_locales_cache');
```

**Utilisation interne** : `CronManager::run_locales_refresh()`

## Filtres Fournis par WP-Pierre

### `pierre_api_request_args`

Modifie les arguments des requêtes HTTP vers les APIs externes (translate.wordpress.org, Slack).

**Appelé** : Avant chaque requête HTTP externe

**Paramètres** :
- `$args` (array) : Arguments de la requête HTTP (timeout, headers, etc.)
- `$url` (string) : URL de la requête

**Retour** : Tableau d'arguments modifiés

**Exemple d'utilisation** :
```php
add_filter('pierre_api_request_args', function($args, $url) {
    // Augmenter le timeout pour certaines URLs
    if (strpos($url, 'translate.wordpress.org') !== false) {
        $args['timeout'] = 60;
    }
    return $args;
}, 10, 2);
```

**Utilisation interne** :
- `TranslationScraper::make_api_request()`
- `SlackNotifier::send_notification()`
- `AdminController::fetch_locales()`

### `pierre_notification_message`

Modifie le message Slack avant son envoi.

**Appelé** : Avant l'envoi de chaque notification Slack

**Paramètres** :
- `$formatted` (array) : Message formaté (Block Kit + Attachments)
- `$message` (array) : Message original
- `$context` (array) : Contexte de la notification
  - `type` (string) : Type de notification
  - `project` (string) : Slug du projet
  - `locale` (string) : Code de locale
  - `data` (array) : Données additionnelles

**Retour** : Tableau de message modifié

**Exemple d'utilisation** :
```php
add_filter('pierre_notification_message', function($formatted, $message, $context) {
    // Ajouter un emoji personnalisé selon le type
    if ($context['type'] === 'new_strings') {
        $formatted['text'] = '🎉 ' . $formatted['text'];
    }
    return $formatted;
}, 10, 3);
```

**Utilisation interne** : `SlackNotifier::format_message()`

### `pierre_translation_data`

Modifie les données de traduction après leur récupération depuis l'API.

**Appelé** : Après la récupération des données depuis translate.wordpress.org

**Paramètres** :
- `$translation_data` (array) : Données de traduction
- `$project_slug` (string) : Slug du projet
- `$locale_code` (string) : Code de locale
- `$project_type` (string) : Type de projet

**Retour** : Tableau de données modifié

**Exemple d'utilisation** :
```php
add_filter('pierre_translation_data', function($data, $project_slug, $locale_code, $project_type) {
    // Enrichir les données avec des métadonnées personnalisées
    $data['custom_metadata'] = get_custom_metadata($project_slug);
    return $data;
}, 10, 4);
```

**Utilisation interne** : `TranslationScraper::scrape_typed_project()`

### `pierre_digest_max_projects`

Limite le nombre de projets dans un digest.

**Appelé** : Lors de la construction d'un digest

**Paramètres** : Aucun (filtre sans paramètres)

**Retour** : Nombre maximum de projets (int)

**Valeur par défaut** : `20`

**Exemple d'utilisation** :
```php
add_filter('pierre_digest_max_projects', function() {
    // Limiter à 10 projets par digest
    return 10;
});
```

**Utilisation interne** : `CronManager::run_digest()`

### `pierre_digest_chunk_size`

Définit la taille des chunks pour les digests.

**Appelé** : Lors de la construction d'un digest

**Paramètres** : Aucun (filtre sans paramètres)

**Retour** : Taille des chunks (int)

**Valeur par défaut** : `20`

**Exemple d'utilisation** :
```php
add_filter('pierre_digest_chunk_size', function() {
    // Utiliser des chunks de 15 projets
    return 15;
});
```

**Utilisation interne** : `CronManager::run_digest()`

### `sanitize_option_pierre_settings`

Sanitize les réglages avant leur sauvegarde.

**Appelé** : Lors de la sauvegarde des réglages via WordPress Settings API

**Paramètres** :
- `$sanitized` (array) : Réglages sanitizés
- `$settings` (array) : Réglages bruts

**Retour** : Tableau de réglages sanitizés

**Exemple d'utilisation** :
```php
add_filter('sanitize_option_pierre_settings', function($sanitized, $settings) {
    // Validation supplémentaire personnalisée
    if (isset($settings['custom_field'])) {
        $sanitized['custom_field'] = sanitize_text_field($settings['custom_field']);
    }
    return $sanitized;
}, 10, 2);
```

**Utilisation interne** : `Settings::update()`

## Actions WordPress Utilisées

### Cycle de Vie du Plugin

#### `plugins_loaded`

Déclenche l'initialisation du plugin.

**Hook** : `plugins_loaded`

**Callback** : `pierre()->init()`

**Fichier** : `wp-pierre.php`

**Priorité** : Par défaut (10)

#### `register_activation_hook`

Hook d'activation du plugin.

**Hook** : `register_activation_hook(__FILE__, ...)`

**Callback** : `pierre()->activate()`

**Fichier** : `wp-pierre.php`

#### `register_deactivation_hook`

Hook de désactivation du plugin.

**Hook** : `register_deactivation_hook(__FILE__, ...)`

**Callback** : `pierre()->deactivate()`

**Fichier** : `wp-pierre.php`

#### `register_uninstall_hook`

Hook de désinstallation du plugin.

**Hook** : `register_uninstall_hook(__FILE__, 'pierre_uninstall_hook')`

**Callback** : `pierre()->uninstall()`

**Fichier** : `wp-pierre.php`

### Initialisation

#### `init`

Initialise les hooks publics.

**Hook** : `init`

**Callback** : `Plugin::init_public_hooks()`

**Fichier** : `src/Pierre/Plugin.php`

**Condition** : Uniquement si `!is_admin() && !wp_doing_ajax()`

#### `admin_init`

Initialise les hooks admin et enregistre les réglages.

**Hook** : `admin_init`

**Callbacks** :
- `Plugin::init_admin_hooks()`
- `AdminController::register_settings_api()`

**Fichiers** :
- `src/Pierre/Plugin.php`
- `src/Pierre/Admin/AdminController.php`

### Assets

#### `wp_enqueue_scripts`

Enqueue les scripts et styles publics.

**Hook** : `wp_enqueue_scripts`

**Callback** : `Plugin::enqueue_public_scripts()`

**Fichier** : `src/Pierre/Plugin.php`

#### `admin_enqueue_scripts`

Enqueue les scripts et styles admin.

**Hook** : `admin_enqueue_scripts`

**Callbacks** :
- `Plugin::enqueue_admin_scripts()`
- `AdminController::enqueue_admin_scripts()`

**Fichiers** :
- `src/Pierre/Plugin.php`
- `src/Pierre/Admin/AdminController.php`

**Condition** : Uniquement sur les pages admin de Pierre

### Menus Admin

#### `admin_menu`

Ajoute le menu admin.

**Hook** : `admin_menu`

**Callback** : `AdminController::add_admin_menu()`

**Fichier** : `src/Pierre/Admin/AdminController.php`

#### `network_admin_menu`

Ajoute le menu admin réseau (multisite).

**Hook** : `network_admin_menu`

**Callback** : `AdminController::add_admin_menu()`

**Fichier** : `src/Pierre/Admin/AdminController.php`

#### `user_admin_menu`

Ajoute le menu admin utilisateur (multisite).

**Hook** : `user_admin_menu`

**Callback** : `AdminController::add_admin_menu()`

**Fichier** : `src/Pierre/Admin/AdminController.php`

#### `admin_bar_menu`

Ajoute des liens dans la barre d'admin.

**Hook** : `admin_bar_menu`

**Callback** : `AdminController::add_admin_bar_menu()`

**Fichier** : `src/Pierre/Admin/AdminController.php`

**Priorité** : 100

### Interface Admin

#### `admin_notices`

Affiche les notices admin.

**Hook** : `admin_notices`

**Callbacks** :
- `AdminController::show_admin_notices()`
- Notice d'activation dans `wp-pierre.php`

**Fichiers** :
- `src/Pierre/Admin/AdminController.php`
- `wp-pierre.php`

#### `current_screen`

Enregistre les onglets d'aide contextuelle.

**Hook** : `current_screen`

**Callback** : `AdminController::register_help_tabs()`

**Fichier** : `src/Pierre/Admin/AdminController.php`

### Frontend

#### `init`

Ajoute les règles de rewrite pour le dashboard public.

**Hook** : `init`

**Callback** : `DashboardController::add_rewrite_rules()`

**Fichier** : `src/Pierre/Frontend/DashboardController.php`

#### `template_redirect`

Gère le routage du dashboard public.

**Hook** : `template_redirect`

**Callback** : `DashboardController::handle_template_redirect()`

**Fichier** : `src/Pierre/Frontend/DashboardController.php`

#### `wp_loaded`

Vérifie si les règles de rewrite doivent être flushées.

**Hook** : `wp_loaded`

**Callback** : `DashboardController::maybe_flush_rewrite_rules()`

**Fichier** : `src/Pierre/Frontend/DashboardController.php`

### Cron

#### `cron_schedules`

Enregistre les intervalles personnalisés pour le cron.

**Hook** : `cron_schedules`

**Callback** : `CronManager::register_schedules()`

**Fichier** : `src/Pierre/Surveillance/CronManager.php`

**Intervalles ajoutés** :
- `pierre_5min` : 5 minutes
- `pierre_15min` : 15 minutes
- `pierre_30min` : 30 minutes
- `pierre_60min` : 1 heure
- `pierre_120min` : 2 heures
- `pierre_daily` : 1 jour
- `pierre_weekly` : 1 semaine

#### `pierre_surveillance_check`

Action cron pour la surveillance périodique.

**Hook** : `pierre_surveillance_check`

**Callback** : `CronManager::run_surveillance_check()`

**Fichier** : `src/Pierre/Surveillance/CronManager.php`

**Planification** : Selon l'intervalle configuré (défaut: 15 minutes)

#### `pierre_cleanup_old_data`

Action cron pour le nettoyage quotidien.

**Hook** : `pierre_cleanup_old_data`

**Callback** : `CronManager::run_cleanup_task()`

**Fichier** : `src/Pierre/Surveillance/CronManager.php`

**Planification** : Quotidien

#### `pierre_run_digest`

Action cron pour l'envoi des digests.

**Hook** : `pierre_run_digest`

**Callback** : `CronManager::run_digest()`

**Fichier** : `src/Pierre/Surveillance/CronManager.php`

**Planification** : Selon la configuration (interval ou heure fixe)

## Filtres WordPress Utilisés

### Interface Admin

#### `admin_footer_text`

Modifie le texte du footer admin.

**Hook** : `admin_footer_text`

**Callback** : `AdminController::modify_admin_footer()`

**Fichier** : `src/Pierre/Admin/AdminController.php`

**Utilisation** : Affiche "Merci d'utiliser Pierre" sur les pages admin

### Frontend

#### `wp_title`

Modifie le titre de la page pour le dashboard public.

**Hook** : `wp_title`

**Callback** : Fonctions anonymes dans `DashboardController`

**Fichier** : `src/Pierre/Frontend/DashboardController.php`

**Utilisation** : 3 instances selon le contexte (dashboard, locale, projet)

### Capabilities

#### `user_has_cap`

Vérifie les capabilities personnalisées.

**Hook** : `user_has_cap`

**Callback** : Fonction anonyme dans `RoleManager`

**Fichier** : `src/Pierre/Teams/RoleManager.php`

**Utilisation** : Vérifie les capabilities Pierre pour les utilisateurs

#### `map_meta_cap`

Mappe les meta capabilities.

**Hook** : `map_meta_cap`

**Callback** : Fonction anonyme dans `RoleManager`

**Fichier** : `src/Pierre/Teams/RoleManager.php`

**Utilisation** : Mappe les capabilities dynamiques (ex: `pierre_manage_project_{slug}`)

### Performance

#### `pre_get_posts`

Optimise les requêtes WordPress (si activé).

**Hook** : `pre_get_posts`

**Callback** : Fonction anonyme dans `performance-config.php`

**Fichier** : `src/Pierre/Performance/performance-config.php`

**Condition** : Si les optimisations sont activées

## Exemples d'Utilisation Avancée

### Personnaliser les Requêtes API

```php
add_filter('pierre_api_request_args', function($args, $url) {
    // Ajouter un header personnalisé
    $args['headers']['X-Custom-Header'] = 'value';
    
    // Modifier le timeout selon l'URL
    if (strpos($url, 'slack.com') !== false) {
        $args['timeout'] = 10;
    }
    
    return $args;
}, 10, 2);
```

### Personnaliser les Messages Slack

```php
add_filter('pierre_notification_message', function($formatted, $message, $context) {
    // Ajouter un bloc personnalisé
    $formatted['blocks'][] = [
        'type' => 'section',
        'text' => [
            'type' => 'mrkdwn',
            'text' => 'Message personnalisé ajouté'
        ]
    ];
    
    return $formatted;
}, 10, 3);
```

### Enrichir les Données de Traduction

```php
add_filter('pierre_translation_data', function($data, $project_slug, $locale_code, $project_type) {
    // Ajouter des métadonnées depuis une source externe
    $external_data = fetch_external_metadata($project_slug);
    $data['external_metadata'] = $external_data;
    
    return $data;
}, 10, 4);
```

### Logger les Actions de Debug

```php
add_action('wp_pierre_debug', function($message, $context) {
    // Logger vers un service externe
    if (isset($context['scope']) && $context['scope'] === 'surveillance') {
        log_to_external_service($message, $context);
    }
}, 10, 2);
```

### Intercepter le Rafraîchissement du Cache

```php
add_action('pierre_refresh_locales_cache', function() {
    // Exécuter une action personnalisée après le rafraîchissement
    do_custom_action_after_refresh();
});
```

## Bonnes Pratiques

1. **Vérifier les conditions** : Toujours vérifier le contexte avant d'agir sur un hook
2. **Respecter les priorités** : Utiliser des priorités appropriées pour éviter les conflits
3. **Sanitizer les données** : Toujours sanitizer les données modifiées
4. **Documenter** : Documenter vos hooks personnalisés
5. **Tester** : Tester vos hooks dans différents contextes

## Dépannage

### Les hooks ne se déclenchent pas

- Vérifier que `PIERRE_DEBUG` est activé pour `wp_pierre_debug`
- Vérifier les priorités des hooks
- Vérifier que le plugin est bien activé

### Les filtres ne modifient pas les données

- Vérifier que vous retournez bien les données modifiées
- Vérifier l'ordre des priorités
- Vérifier que vous utilisez les bons paramètres

---

*Pierre says: These hooks let you extend and customize my behavior! 🪨*

