# Endpoints Découverte de Projets

## `pierre_save_projects_discovery`

Sauvegarde la bibliothèque de découverte de projets.

**Action** : `wp_ajax_pierre_save_projects_discovery`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_settings`

**Paramètres** :
- `library` (array, requis) : Bibliothèque de projets `[{"type": "plugin", "slug": "woocommerce"}, ...]`

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Projects discovery library saved"
  }
}
```

## `pierre_bulk_add_from_discovery`

Ajoute en masse des projets depuis la découverte.

**Action** : `wp_ajax_pierre_bulk_add_from_discovery`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_projects`

**Paramètres** :
- `locale_code` (string, requis) : Code de locale
- `projects` (array, requis) : Tableau de projets

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Bulk add completed",
    "added": 10,
    "errors": 0
  }
}
```

## `pierre_bulk_preview_from_discovery`

Prévisualise l'ajout en masse depuis la découverte.

**Action** : `wp_ajax_pierre_bulk_preview_from_discovery`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_view_dashboard`

**Paramètres** :
- `locale_code` (string, requis) : Code de locale
- `projects` (array, requis) : Tableau de projets

**Réponse** :
```json
{
  "success": true,
  "data": {
    "preview": {
      "will_add": 10,
      "already_exists": 2,
      "invalid": 0
    }
  }
}
```

## `pierre_bulk_add_projects_to_locale`

Ajoute des projets à une locale en masse.

**Action** : `wp_ajax_pierre_bulk_add_projects_to_locale`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_projects`

**Paramètres** :
- `locale_code` (string, requis) : Code de locale
- `projects` (array, requis) : Tableau de projets

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Projects added",
    "added": 10
  }
}
```

