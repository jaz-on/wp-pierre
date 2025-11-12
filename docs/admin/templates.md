# Templates

Documentation des templates admin et public du plugin WP-Pierre.

## Vue d'Ensemble

Les templates sont organisés en deux catégories :
- **Templates Admin** : Interface d'administration (`templates/admin/`)
- **Templates Public** : Dashboard public (`templates/public/`)

Tous les templates utilisent la variable globale `$GLOBALS['pierre_admin_template_data']` ou `$GLOBALS['pierre_public_template_data']` pour recevoir les données.

## Templates Admin

### `dashboard.php`

Template du dashboard principal de l'administration.

**Fichier** : `templates/admin/dashboard.php`

**Variables disponibles** :
- `$data['stats']` (array) : Statistiques du dashboard
  - Format : `[['label' => 'Projects', 'value' => 10], ...]`
- `$data['surveillance_status']` (array) : Statut de la surveillance
  - `active` (bool) : Surveillance active ou non
  - `message` (string) : Message de statut
  - `next_run` (string, optionnel) : Prochaine exécution
- `$data['notifier_status']` (array) : Statut du système de notifications
  - `ready` (bool) : Système prêt
  - `webhook_configured` (bool) : Webhook configuré
  - `message` (string) : Message de statut

**Hooks disponibles** :
- `pierre_admin_dashboard_before` : Avant le contenu principal
- `pierre_admin_dashboard_after` : Après le contenu principal

**Utilisation** : Page principale du menu admin (Pierre → Dashboard)

### `locales.php`

Template de la liste des locales surveillées.

**Fichier** : `templates/admin/locales.php`

**Variables disponibles** :
- `$data['locales']` (array) : Liste des locales
  - Format : `['fr' => ['name' => 'Français', 'projects_count' => 10], ...]`
- `$data['stats']` (array, optionnel) : Statistiques globales

**Hooks disponibles** :
- `pierre_admin_locales_before` : Avant la liste
- `pierre_admin_locales_after` : Après la liste

**Utilisation** : Page Locales (Pierre → Locales)

### `projects.php`

Template de la liste des projets surveillés.

**Fichier** : `templates/admin/projects.php`

**Variables disponibles** :
- `$data['projects']` (array) : Liste des projets
  - Format : `[['type' => 'plugin', 'slug' => 'woocommerce', 'locale' => 'fr', ...], ...]`
- `$data['surveillance_status']` (array) : Statut de la surveillance
- `$data['stats']` (array, optionnel) : Statistiques

**Hooks disponibles** :
- `pierre_admin_projects_before` : Avant la liste
- `pierre_admin_projects_after` : Après la liste

**Utilisation** : Page Projets (Pierre → Projects)

### `teams.php`

Template de gestion des équipes.

**Fichier** : `templates/admin/teams.php`

**Variables disponibles** :
- `$data['roles']` (array) : Rôles formatés (simple)
  - Format : `['Locale Manager' => 'Description', ...]`
- `$data['roles_full']` (array) : Rôles complets avec métadonnées
  - Format : `['locale_manager' => ['display_name' => '...', 'description' => '...'], ...]`
- `$data['capabilities']` (array) : Capabilities formatées
  - Format : `['pierre_manage_settings' => ['description' => '...', 'meta_cap' => false], ...]`
- `$data['capabilities_full']` (array) : Capabilities complètes avec métadonnées

**Hooks disponibles** :
- `pierre_admin_teams_before` : Avant le contenu
- `pierre_admin_teams_after` : Après le contenu

**Utilisation** : Page Équipes (Pierre → Teams)

### `settings.php`

Template de la page de réglages principale.

**Fichier** : `templates/admin/settings.php`

**Variables disponibles** :
- `$data['settings']` (array) : Réglages actuels
- `$data['sections']` (array) : Sections de réglages disponibles
- `$data['active_tab']` (string, optionnel) : Onglet actif

**Hooks disponibles** :
- `pierre_admin_settings_before` : Avant les réglages
- `pierre_admin_settings_after` : Après les réglages

**Utilisation** : Page Réglages (Pierre → Settings)

### `settings-discovery.php`

Template des réglages de découverte de projets.

**Fichier** : `templates/admin/settings-discovery.php`

**Variables disponibles** :
- `$data['library']` (array) : Bibliothèque de projets de découverte
- `$data['catalog_status']` (array, optionnel) : Statut du catalogue

**Hooks disponibles** :
- `pierre_admin_settings_discovery_before` : Avant le contenu
- `pierre_admin_settings_discovery_after` : Après le contenu

**Utilisation** : Sous-page Réglages → Découverte de Projets

### `settings-global-webhook.php`

Template des réglages du webhook global.

**Fichier** : `templates/admin/settings-global-webhook.php`

**Variables disponibles** :
- `$data['webhook']` (array) : Configuration du webhook global
  - `webhook_url` (string) : URL du webhook (chiffrée)
  - `types` (array) : Types de notifications
  - `thresholds` (array) : Seuils
  - `mode` (string) : Mode (`immediate` ou `digest`)
  - `digest` (array, optionnel) : Configuration digest

**Hooks disponibles** :
- `pierre_admin_settings_webhook_before` : Avant le formulaire
- `pierre_admin_settings_webhook_after` : Après le formulaire

**Utilisation** : Sous-page Réglages → Webhook Global

### `settings-projects-discovery.php`

Template des réglages de découverte de projets (alias).

**Fichier** : `templates/admin/settings-projects-discovery.php`

**Variables disponibles** : Identiques à `settings-discovery.php`

**Utilisation** : Sous-page Réglages → Découverte de Projets

### `locale-view.php`

Template de la vue détaillée d'une locale.

**Fichier** : `templates/admin/locale-view.php`

**Variables disponibles** :
- `$data['locale_code']` (string) : Code de la locale
- `$data['locale_name']` (string) : Nom de la locale
- `$data['projects']` (array) : Projets surveillés pour cette locale
- `$data['webhook']` (array, optionnel) : Configuration webhook spécifique
- `$data['stats']` (array) : Statistiques de la locale
- `$data['team_members']` (array, optionnel) : Membres de l'équipe assignés

**Hooks disponibles** :
- `pierre_admin_locale_view_before` : Avant le contenu
- `pierre_admin_locale_view_after` : Après le contenu

**Utilisation** : Vue détaillée d'une locale (depuis la liste des locales)

### `reports.php`

Template de la page des rapports.

**Fichier** : `templates/admin/reports.php`

**Variables disponibles** :
- `$data['reports']` (array) : Rapports disponibles
- `$data['stats']` (array) : Statistiques pour les rapports
- `$data['schedule']` (array, optionnel) : Planification des rapports

**Hooks disponibles** :
- `pierre_admin_reports_before` : Avant les rapports
- `pierre_admin_reports_after` : Après les rapports

**Utilisation** : Page Rapports (Pierre → Reports)

## Templates Public

### `dashboard.php`

Template du dashboard public principal.

**Fichier** : `templates/public/dashboard.php`

**Variables disponibles** :
- `$data['stats']` (array) : Statistiques publiques
- `$data['locales']` (array, optionnel) : Liste des locales
- `$data['recent_activity']` (array, optionnel) : Activité récente

**Hooks disponibles** :
- `pierre_public_dashboard_before` : Avant le contenu
- `pierre_public_dashboard_after` : Après le contenu

**URL** : `/pierre/`

**Permissions** : Aucune (public)

### `locale.php`

Template de la vue publique d'une locale.

**Fichier** : `templates/public/locale.php`

**Variables disponibles** :
- `$data['locale_code']` (string) : Code de la locale
- `$data['locale_name']` (string) : Nom de la locale
- `$data['projects']` (array) : Projets surveillés
- `$data['stats']` (array) : Statistiques de la locale
- `$data['progress']` (array, optionnel) : Progression de traduction

**Hooks disponibles** :
- `pierre_public_locale_before` : Avant le contenu
- `pierre_public_locale_after` : Après le contenu

**URL** : `/pierre/locale/{locale_code}/`

**Permissions** : Aucune (public)

### `project.php`

Template de la vue publique d'un projet.

**Fichier** : `templates/public/project.php`

**Variables disponibles** :
- `$data['project_type']` (string) : Type de projet
- `$data['project_slug']` (string) : Slug du projet
- `$data['project_name']` (string) : Nom du projet
- `$data['locale_code']` (string) : Code de la locale
- `$data['translation_data']` (array) : Données de traduction
  - `translated` (int) : Strings traduits
  - `untranslated` (int) : Strings non traduits
  - `waiting` (int) : Strings en attente
  - `fuzzy` (int) : Strings fuzzy
  - `percent_translated` (float) : Pourcentage de complétion
- `$data['history']` (array, optionnel) : Historique des changements

**Hooks disponibles** :
- `pierre_public_project_before` : Avant le contenu
- `pierre_public_project_after` : Après le contenu

**URL** : `/pierre/locale/{locale_code}/project/{type}/{slug}/`

**Permissions** : Aucune (public)

## Structure Commune

Tous les templates suivent cette structure :

```php
<?php
// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Récupération des données
$data = $GLOBALS['pierre_admin_template_data'] ?? [];
// ou
$data = $GLOBALS['pierre_public_template_data'] ?? [];

// Contenu du template
?>
```

## Hooks Disponibles dans les Templates

Tous les templates supportent des hooks avant et après le contenu principal. Ces hooks permettent d'ajouter du contenu personnalisé.

### Exemple d'Utilisation

```php
// Ajouter du contenu avant le dashboard admin
add_action('pierre_admin_dashboard_before', function() {
    echo '<div class="custom-notice">Message personnalisé</div>';
});

// Ajouter du contenu après le dashboard public
add_action('pierre_public_dashboard_after', function() {
    echo '<div class="custom-footer">Footer personnalisé</div>';
});
```

## Personnalisation des Templates

### Surcharger un Template

Pour surcharger un template, créez un fichier dans votre thème :

**Structure** : `{theme}/pierre/{template_name}.php`

**Exemples** :
- `wp-content/themes/your-theme/pierre/admin/dashboard.php`
- `wp-content/themes/your-theme/pierre/public/dashboard.php`

Le plugin cherchera d'abord dans le thème, puis utilisera le template par défaut.

### Modifier les Données

Vous pouvez filtrer les données avant qu'elles ne soient passées au template :

```php
add_filter('pierre_admin_template_data', function($data, $template_name) {
    if ($template_name === 'dashboard') {
        // Modifier les données du dashboard
        $data['custom_field'] = 'custom_value';
    }
    return $data;
}, 10, 2);
```

## Bonnes Pratiques

1. **Toujours vérifier l'existence des variables** : Utiliser `isset()` ou l'opérateur null coalescing `??`
2. **Échapper les sorties** : Utiliser `esc_html()`, `esc_attr()`, `esc_url()`, etc.
3. **Respecter la structure** : Suivre la structure existante pour la cohérence
4. **Documenter les modifications** : Documenter toute personnalisation importante

## Exemples

### Exemple : Template Admin Personnalisé

```php
<?php
// wp-content/themes/your-theme/pierre/admin/dashboard.php
if (!defined('ABSPATH')) {
    exit;
}

$data = $GLOBALS['pierre_admin_template_data'] ?? [];
?>

<div class="wrap">
    <h1>Mon Dashboard Personnalisé</h1>
    
    <?php if (isset($data['stats'])): ?>
        <div class="custom-stats">
            <?php foreach ($data['stats'] as $stat): ?>
                <div class="stat">
                    <strong><?php echo esc_html($stat['label']); ?>:</strong>
                    <?php echo esc_html($stat['value']); ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
```

### Exemple : Ajouter du Contenu via Hook

```php
// Dans functions.php du thème
add_action('pierre_public_project_before', function() {
    echo '<div class="project-header-custom">';
    echo '<p>Contenu personnalisé avant le projet</p>';
    echo '</div>';
});
```

---

*Pierre says: Customize my templates to match your site's design! 🪨*

