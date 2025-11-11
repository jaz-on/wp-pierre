# Capabilities & Permissions

Ce document décrit toutes les capabilities (permissions) utilisées par le plugin WP-Pierre.

> 📚 **Documentation complète** : Pour une documentation encore plus détaillée avec des exemples avancés, consultez le [Wiki Capabilities](https://github.com/jaz-on/wp-pierre/wiki/Capabilities) sur GitHub.

## Vue d'ensemble

Le plugin WP-Pierre utilise un système de capabilities WordPress pour gérer les permissions des utilisateurs. Les capabilities sont organisées en deux catégories :

1. **Capabilities standards** : Permissions générales assignées aux rôles WordPress
2. **Meta capabilities** : Permissions dynamiques basées sur les assignations d'équipe (Locale Manager, GTE, PTE)

### Où voir les capabilities dans l'interface ?

Vous pouvez visualiser toutes les capabilities et rôles directement dans l'interface WordPress :
- **Admin WordPress** → **Pierre** → **Teams** → Section "Roles & Capabilities"

Cette interface affiche la liste complète des rôles et capabilities avec leurs descriptions.

## Capabilities Standards

### `pierre_view_dashboard`
- **Description** : Permet de visualiser le tableau de bord Pierre et les statistiques de traduction
- **Rôles par défaut** : `administrator`, `editor`
- **Utilisation** : Accès au tableau de bord public et aux statistiques de traduction

### `pierre_manage_settings`
- **Description** : Permet de gérer les réglages du plugin Pierre, les webhooks Slack et les intervalles de surveillance
- **Rôles par défaut** : `administrator`
- **Utilisation** : Accès à la page de réglages du plugin

### `pierre_manage_projects`
- **Description** : Permet d'ajouter, supprimer et gérer les projets de traduction surveillés
- **Rôles par défaut** : `administrator`
- **Utilisation** : Gestion de la liste des projets surveillés

### `pierre_manage_teams`
- **Description** : Permet de gérer les assignations d'équipe et les rôles utilisateurs pour les projets de traduction
- **Rôles par défaut** : `administrator`
- **Utilisation** : Gestion des équipes et assignations utilisateurs

### `pierre_manage_reports`
- **Description** : Permet de générer et gérer les rapports de traduction
- **Rôles par défaut** : `administrator`, `editor`
- **Utilisation** : Génération et export de rapports

### `pierre_manage_notifications`
- **Description** : Permet de configurer et gérer les notifications Slack
- **Rôles par défaut** : `administrator`
- **Utilisation** : Configuration des webhooks et paramètres de notification

### `pierre_assign_projects`
- **Description** : Permet d'assigner des utilisateurs aux projets de traduction (Locale Managers uniquement)
- **Rôles par défaut** : `administrator`
- **Utilisation** : Assignation d'utilisateurs aux projets

## Meta Capabilities

Les meta capabilities sont vérifiées dynamiquement en fonction des assignations d'équipe de l'utilisateur pour une locale ou un projet spécifique.

### `pierre_manage_locale`
- **Description** : Permet de gérer les réglages pour une locale spécifique (Locale Manager uniquement)
- **Assignations requises** : `locale_manager`
- **Contexte requis** : `locale` (code de locale)
- **Utilisation** : Gestion des réglages de notification par locale

### `pierre_manage_project_locale`
- **Description** : Permet de gérer un projet spécifique pour une locale (Locale Manager, GTE, ou PTE)
- **Assignations requises** : `locale_manager`, `gte`, ou `pte`
- **Contexte requis** : `locale` (code de locale), `project` (type et slug)
- **Utilisation** : Gestion d'un projet spécifique pour une locale

### `pierre_assign_user_locale`
- **Description** : Permet d'assigner des utilisateurs à une locale (Locale Manager uniquement)
- **Assignations requises** : `locale_manager`
- **Contexte requis** : `locale` (code de locale)
- **Utilisation** : Assignation d'utilisateurs aux projets d'une locale

### `pierre_view_reports_locale`
- **Description** : Permet de visualiser les rapports pour une locale spécifique (Locale Manager, GTE, ou PTE)
- **Assignations requises** : `locale_manager`, `gte`, ou `pte`
- **Contexte requis** : `locale` (code de locale)
- **Utilisation** : Visualisation des rapports de traduction par locale

## Rôles d'Équipe de Traduction

Le plugin reconnaît également des rôles d'équipe de traduction qui ne sont pas des rôles WordPress standards, mais des assignations basées sur les projets :

### Locale Manager (LM)
- **Description** : Gère une locale spécifique et peut assigner des utilisateurs aux projets
- **Capabilities** : `pierre_manage_locale`, `pierre_assign_user_locale`, `pierre_view_reports_locale`
- **Assignation** : Via la page Teams du plugin

### General Translation Editor (GTE)
- **Description** : Peut gérer les projets pour une locale mais ne peut pas assigner d'utilisateurs
- **Capabilities** : `pierre_manage_project_locale`, `pierre_view_reports_locale`
- **Assignation** : Via la page Teams du plugin

### Project Translation Editor (PTE)
- **Description** : Peut gérer un projet spécifique pour une locale
- **Capabilities** : `pierre_manage_project_locale`, `pierre_view_reports_locale`
- **Assignation** : Via la page Teams du plugin

## Utilisation dans le Code

### Vérifier une capability standard

```php
if ( current_user_can( 'pierre_view_dashboard' ) ) {
    // Afficher le tableau de bord
}
```

### Vérifier une meta capability

```php
if ( current_user_can( 'pierre_manage_project_locale', $user_id, [
    'locale' => 'fr',
    'project' => [
        'type' => 'plugin',
        'slug' => 'my-plugin'
    ]
] ) ) {
    // Gérer le projet
}
```

### Obtenir les informations d'une capability

```php
$role_manager = new \Pierre\Teams\RoleManager();
$cap_info = $role_manager->get_capability_info( 'pierre_view_dashboard' );
// Retourne : ['name' => '...', 'description' => '...', 'roles' => [...], 'meta_cap' => false]
```

### Lister toutes les capabilities

```php
$role_manager = new \Pierre\Teams\RoleManager();
$all_caps = $role_manager->get_capabilities( true ); // Inclut les meta capabilities
```

## Notes Importantes

1. **Administrateurs** : Tous les administrateurs ont automatiquement toutes les capabilities Pierre
2. **Meta capabilities** : Sont vérifiées dynamiquement via le filtre `map_meta_cap`
3. **Assignations** : Les assignations d'équipe sont stockées dans les options WordPress :
   - `pierre_locale_managers` : Liste des Locale Managers par locale
   - `pierre_gte` : Liste des GTE par locale
   - `pierre_pte` : Liste des PTE par locale et projet

## Migration et Compatibilité

Lors de l'activation du plugin, toutes les capabilities sont automatiquement ajoutées au rôle `administrator`. Les capabilities sont persistantes et ne sont pas supprimées lors de la désactivation (seulement lors de la désinstallation).

## Ressources supplémentaires

- **Interface Admin** : Visualisez les capabilities dans **Pierre → Teams → Roles & Capabilities**
- **Wiki GitHub** : [Documentation complète des Capabilities](https://github.com/jaz-on/wp-pierre/wiki/Capabilities)
- **Code source** : `src/Pierre/Teams/RoleManager.php` pour l'implémentation complète

## Questions fréquentes

### Comment ajouter une nouvelle capability ?

Modifiez le tableau `$caps` dans `RoleManager.php` et ajoutez la capability au rôle `administrator` via `add_capabilities()`.

### Comment vérifier si un utilisateur a une meta capability ?

Utilisez `current_user_can()` avec le contexte approprié :
```php
current_user_can('pierre_manage_project_locale', $user_id, [
    'locale' => 'fr',
    'project' => ['type' => 'plugin', 'slug' => 'my-plugin']
]);
```

### Les capabilities sont-elles supprimées à la désactivation ?

Non, les capabilities restent dans la base de données. Elles ne sont supprimées que lors de la désinstallation complète du plugin.

---

*Pierre says: This documentation helps you understand all the permissions in my plugin! For more details, check the [Wiki](https://github.com/jaz-on/wp-pierre/wiki/Capabilities)! 🪨*

