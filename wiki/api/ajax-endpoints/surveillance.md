# Endpoints Surveillance

## `pierre_run_surveillance_now`

Démarre une surveillance immédiate (cooldown de 2 minutes).

**Action** : `wp_ajax_pierre_run_surveillance_now`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_projects`

**Paramètres** : Aucun

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Surveillance started"
  }
}
```

## `pierre_start_surveillance`

Démarre la surveillance automatique (planifie les cron).

**Action** : `wp_ajax_pierre_start_surveillance`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_projects`

**Paramètres** : Aucun

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Surveillance started",
    "next_run": "2024-01-01 12:00:00"
  }
}
```

## `pierre_stop_surveillance`

Arrête la surveillance automatique.

**Action** : `wp_ajax_pierre_stop_surveillance`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_projects`

**Paramètres** : Aucun

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Surveillance stopped"
  }
}
```

## `pierre_test_surveillance`

Lance un test de surveillance (dry run) sans envoyer de notifications.

**Action** : `wp_ajax_pierre_test_surveillance`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_projects`

**Paramètres** : Aucun

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Dry run completed",
    "stats": { ... }
  }
}
```

## `pierre_abort_run`

Arrête une surveillance en cours d'exécution.

**Action** : `wp_ajax_pierre_abort_run`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_projects`

**Paramètres** : Aucun

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Surveillance aborted"
  }
}
```

## `pierre_abort_surveillance_run`

Alias de `pierre_abort_run` pour compatibilité.

**Action** : `wp_ajax_pierre_abort_surveillance_run`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_projects`

## `pierre_get_progress`

Récupère la progression de la surveillance en cours.

**Action** : `wp_ajax_pierre_get_progress`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_view_dashboard`

**Paramètres** : Aucun

**Réponse** :
```json
{
  "success": true,
  "data": {
    "progress": {
      "processed": 5,
      "total": 10,
      "percentage": 50,
      "status": "running"
    }
  }
}
```

## `pierre_get_surveillance_errors`

Récupère les erreurs de la dernière surveillance.

**Action** : `wp_ajax_pierre_get_surveillance_errors`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_view_dashboard`

**Paramètres** : Aucun

**Réponse** :
```json
{
  "success": true,
  "data": {
    "errors": [
      {
        "project": "woocommerce",
        "locale": "fr",
        "error": "API timeout",
        "timestamp": "2024-01-01 12:00:00"
      }
    ]
  }
}
```

## `pierre_clear_surveillance_errors`

Efface les erreurs de surveillance.

**Action** : `wp_ajax_pierre_clear_surveillance_errors`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_projects`

**Paramètres** : Aucun

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Errors cleared"
  }
}
```

## `pierre_get_error_stats`

Récupère les statistiques d'erreurs.

**Action** : `wp_ajax_pierre_get_error_stats`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_view_dashboard`

**Paramètres** : Aucun

**Réponse** :
```json
{
  "success": true,
  "data": {
    "total_errors": 5,
    "errors_by_type": { ... },
    "recent_errors": [ ... ]
  }
}
```

