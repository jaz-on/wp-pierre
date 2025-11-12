# Endpoints Rapports

## `pierre_export_report`

Exporte un rapport.

**Action** : `wp_ajax_pierre_export_report`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_view_reports`

**Paramètres** :
- `report_type` (string, requis) : Type de rapport
- `format` (string, optionnel) : Format (`json` ou `csv`)

**Réponse** : Fichier téléchargeable

## `pierre_export_all_reports`

Exporte tous les rapports.

**Action** : `wp_ajax_pierre_export_all_reports`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_view_reports`

**Paramètres** :
- `format` (string, optionnel) : Format (`json` ou `csv`)

**Réponse** : Fichier ZIP avec tous les rapports

## `pierre_schedule_reports`

Planifie l'envoi automatique de rapports.

**Action** : `wp_ajax_pierre_schedule_reports`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_settings`

**Paramètres** :
- `schedule` (array, requis) : Configuration de la planification

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Reports scheduled"
  }
}
```

