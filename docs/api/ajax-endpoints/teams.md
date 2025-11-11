# Endpoints Gestion des Équipes

## `pierre_admin_assign_user`

Assigne un utilisateur à un projet avec un rôle.

**Action** : `wp_ajax_pierre_admin_assign_user`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_teams`

**Paramètres** :
- `user_id` (int, requis) : ID de l'utilisateur WordPress
- `project_type` (string, requis) : Type de projet (`plugin`, `theme`, `meta`, `app`)
- `project_slug` (string, requis) : Slug du projet
- `locale_code` (string, requis) : Code de locale
- `role` (string, requis) : Rôle (`locale_manager`, `gte`, `pte`, `contributor`, `validator`)

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "User assigned successfully"
  }
}
```

## `pierre_admin_remove_user`

Retire un utilisateur d'un projet.

**Action** : `wp_ajax_pierre_admin_remove_user`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_teams`

**Paramètres** :
- `user_id` (int, requis) : ID de l'utilisateur
- `project_type` (string, requis) : Type de projet
- `project_slug` (string, requis) : Slug du projet
- `locale_code` (string, requis) : Code de locale

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "User removed successfully"
  }
}
```

## `pierre_search_users_for_locale`

Recherche des utilisateurs pour une locale (avec pagination).

**Action** : `wp_ajax_pierre_search_users_for_locale`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_view_teams`

**Paramètres** :
- `locale_code` (string, requis) : Code de locale
- `search` (string, optionnel) : Terme de recherche
- `page` (int, optionnel) : Numéro de page (défaut: 1)
- `per_page` (int, optionnel) : Résultats par page (défaut: 20)

**Réponse** :
```json
{
  "success": true,
  "data": {
    "users": [ ... ],
    "total": 50,
    "page": 1,
    "per_page": 20
  }
}
```

## `pierre_save_locale_managers`

Sauvegarde les assignations de Locale Managers pour une locale.

**Action** : `wp_ajax_pierre_save_locale_managers`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_teams`

**Paramètres** :
- `locale_code` (string, requis) : Code de locale
- `user_ids` (array, requis) : Tableau d'IDs utilisateurs

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Locale managers saved"
  }
}
```

