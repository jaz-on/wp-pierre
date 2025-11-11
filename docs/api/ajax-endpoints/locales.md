# Endpoints Locales

## `pierre_add_locales`

Ajoute des locales à surveiller.

**Action** : `wp_ajax_pierre_add_locales`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_settings`

**Paramètres** :
- `locales` (array, requis) : Tableau de codes de locales

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Locales added",
    "added": ["fr", "es_ES"]
  }
}
```

## `pierre_fetch_locales`

Récupère la liste des locales disponibles depuis translate.wordpress.org.

**Action** : `wp_ajax_pierre_fetch_locales`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_view_dashboard`

**Paramètres** : Aucun

**Réponse** :
```json
{
  "success": true,
  "data": {
    "locales": [
      {
        "code": "fr",
        "name": "Français",
        "english_name": "French"
      },
      ...
    ]
  }
}
```

## `pierre_save_locale_overrides`

Sauvegarde les réglages spécifiques d'une locale.

**Action** : `wp_ajax_pierre_save_locale_overrides`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_settings`

**Paramètres** :
- `locale_code` (string, requis) : Code de locale
- `overrides` (array, requis) : Tableau de réglages à surcharger

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Locale overrides saved"
  }
}
```

## `pierre_save_locale_slack`

Sauvegarde la configuration Slack d'une locale.

**Action** : `wp_ajax_pierre_save_locale_slack`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_notifications`

**Paramètres** :
- `locale_code` (string, requis) : Code de locale
- `webhook_url` (string, requis) : URL du webhook
- `types` (array, optionnel) : Types de notifications
- `thresholds` (array, optionnel) : Seuils
- `mode` (string, optionnel) : Mode (`immediate` ou `digest`)

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Locale Slack settings saved"
  }
}
```

## `pierre_save_locale_webhook`

Alias de `pierre_save_locale_slack`.

**Action** : `wp_ajax_pierre_save_locale_webhook`

## `pierre_check_locale_status`

Vérifie le statut d'une locale.

**Action** : `wp_ajax_pierre_check_locale_status`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_view_dashboard`

**Paramètres** :
- `locale_code` (string, requis) : Code de locale

**Réponse** :
```json
{
  "success": true,
  "data": {
    "status": {
      "projects_count": 10,
      "last_surveillance": "2024-01-01 12:00:00",
      ...
    }
  }
}
```

## `pierre_clear_locale_log`

Efface les logs d'une locale.

**Action** : `wp_ajax_pierre_clear_locale_log`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_settings`

**Paramètres** :
- `locale_code` (string, requis) : Code de locale

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Locale log cleared"
  }
}
```

## `pierre_export_locale_log`

Exporte les logs d'une locale.

**Action** : `wp_ajax_pierre_export_locale_log`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_view_dashboard`

**Paramètres** :
- `locale_code` (string, requis) : Code de locale
- `format` (string, optionnel) : Format (`json` ou `csv`, défaut: `json`)

**Réponse** : Fichier téléchargeable ou JSON

