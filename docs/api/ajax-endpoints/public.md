# Endpoints Public

## `pierre_get_stats`

Récupère les statistiques publiques.

**Action** : `wp_ajax_pierre_get_stats`

**Nonce** : `pierre_ajax`

**Capability** : Aucune (public)

**Paramètres** : Aucun

**Réponse** :
```json
{
  "success": true,
  "data": {
    "stats": { ... }
  }
}
```

## `pierre_get_projects`

Récupère la liste des projets (public).

**Action** : `wp_ajax_pierre_get_projects`

**Nonce** : `pierre_ajax`

**Capability** : Aucune (public)

**Paramètres** :
- `locale_code` (string, optionnel) : Filtrer par locale

**Réponse** :
```json
{
  "success": true,
  "data": {
    "projects": [ ... ]
  }
}
```

## `pierre_test_notification`

Teste une notification (public, limité).

**Action** : `wp_ajax_pierre_test_notification`

**Nonce** : `pierre_ajax`

**Capability** : Aucune (public, mais rate-limited)

**Paramètres** :
- `webhook_url` (string, requis) : URL du webhook

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Test notification sent"
  }
}
```

