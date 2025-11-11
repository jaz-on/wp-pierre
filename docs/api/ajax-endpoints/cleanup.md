# Endpoints Nettoyage

## `pierre_run_cleanup_now`

Lance le nettoyage immédiatement.

**Action** : `wp_ajax_pierre_run_cleanup_now`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_settings`

**Paramètres** : Aucun

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Cleanup completed",
    "cleaned": {
      "transients": 10,
      "old_errors": 5
    }
  }
}
```

