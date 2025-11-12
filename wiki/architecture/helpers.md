# Fonctions Helper

Documentation des fonctions helper publiques de WP-Pierre.

## `pierre()`

Fonction principale retournant l'instance unique du plugin.

**Signature** : `function pierre(): Pierre\Plugin`

**Fichier** : `wp-pierre.php`

**Retour** : Instance de `Pierre\Plugin` (singleton)

**Utilisation** :

```php
// Obtenir l'instance du plugin
$pierre = pierre();

// Accéder aux composants
$cron_manager = pierre()->get_cron_manager();
$project_watcher = pierre()->get_project_watcher();
$slack_notifier = pierre()->get_slack_notifier();
```

**Note** : Cette fonction utilise le pattern Singleton pour garantir une seule instance.

## `pierre_decrypt_webhook()`

Décrypte une URL de webhook chiffrée.

**Signature** : `function pierre_decrypt_webhook(string $encrypted_webhook): string`

**Fichier** : `wp-pierre.php`

**Paramètres** :
- `$encrypted_webhook` : URL de webhook chiffrée

**Retour** : URL décryptée ou chaîne vide si échec

**Utilisation** :

```php
// Dans un template
$encrypted = get_option('pierre_webhook_url');
$decrypted = pierre_decrypt_webhook($encrypted);

if (!empty($decrypted)) {
    echo esc_url($decrypted);
}
```

**Sécurité** :
- Utilise `Pierre\Security\Encryption::decrypt()`
- Retourne la chaîne originale si le décryptage échoue (pour compatibilité)
- Ne doit être utilisée que dans des contextes sécurisés (admin)

**Note** : Cette fonction est principalement utilisée dans les templates admin pour afficher les webhooks (avec masquage partiel).

---

*Pierre says: These helper functions make it easy to interact with me! 🪨*

