# Endpoints Notifications

## `pierre_admin_test_notification`

Teste la configuration Slack en envoyant une notification de test.

**Action** : `wp_ajax_pierre_admin_test_notification`

**Nonce** : `pierre_admin_ajax`

**Capability** : `pierre_manage_notifications`

**Paramètres** :
- `webhook_url` (string, optionnel) : URL du webhook à tester (sinon utilise le webhook global)
- `locale_code` (string, optionnel) : Locale pour tester un webhook spécifique

**Réponse** :
```json
{
  "success": true,
  "data": {
    "message": "Test notification sent successfully"
  }
}
```

