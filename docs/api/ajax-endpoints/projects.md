# Endpoints Projets

## `pierre_add_project`

Ajoute un projet à surveiller.

**Action** : `wp_ajax_pierre_add_project`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_projects`

**Paramètres** :
- `project_type` (string, requis) : Type (`plugin`, `theme`, `meta`, `app`)
- `project_slug` (string, requis) : Slug du projet
- `locale_code` (string, requis) : Code de locale

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Project added successfully"
  }
}
```

## `pierre_remove_project`

Retire un projet de la surveillance.

**Action** : `wp_ajax_pierre_remove_project`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_projects`

**Paramètres** :
- `project_type` (string, requis) : Type de projet
- `project_slug` (string, requis) : Slug du projet
- `locale_code` (string, requis) : Code de locale

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Project removed"
  }
}
```

## `pierre_add_from_catalog`

Ajoute des projets depuis le catalogue.

**Action** : `wp_ajax_pierre_add_from_catalog`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_projects`

**Paramètres** :
- `locale_code` (string, requis) : Code de locale
- `items` (array, requis) : Tableau d'items `["type,slug", ...]`

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Added 5 item(s), 0 error(s)."
  }
}
```

