# Endpoints Dashboard et Statistiques

## `pierre_admin_get_stats`

Récupère les statistiques du dashboard admin.

**Action** : `wp_ajax_pierre_admin_get_stats`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_view_dashboard`

**Paramètres** : Aucun

**Réponse** :
```json
{
  "success": true,
  "data": {
    "stats": [
      { "label": "Projects", "value": 10 },
      { "label": "Locales", "value": 3 },
      ...
    ],
    "surveillance_status": { ... },
    "notifier_status": { ... }
  }
}
```

