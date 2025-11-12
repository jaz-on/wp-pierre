# Endpoints Réglages

## `pierre_admin_save_settings`

Sauvegarde les réglages du plugin.

**Action** : `wp_ajax_pierre_admin_save_settings`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_settings`

**Paramètres** :
- `settings` (array, requis) : Tableau de réglages à sauvegarder

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Settings saved successfully"
  }
}
```

## `pierre_flush_cache`

Vide le cache du plugin.

**Action** : `wp_ajax_pierre_flush_cache`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_settings`

**Paramètres** :
- `group` (string, optionnel) : Groupe de cache à vider (sinon vide tout)

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Cache flushed",
    "flushed_count": 42
  }
}
```

## `pierre_reset_settings`

Réinitialise les réglages aux valeurs par défaut.

**Action** : `wp_ajax_pierre_reset_settings`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_settings`

**Paramètres** : Aucun

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Settings reset to defaults"
  }
}
```

## `pierre_clear_data`

Efface toutes les données du plugin (projets surveillés, assignations, etc.).

**Action** : `wp_ajax_pierre_clear_data`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_settings`

**Paramètres** : Aucun

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "All data cleared"
  }
}
```

