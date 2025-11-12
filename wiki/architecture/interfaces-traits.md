# Interfaces et Traits

Documentation des interfaces et traits utilisés par WP-Pierre.

## Interfaces

### `WatcherInterface`

Interface définissant le contrat pour les composants de surveillance.

**Namespace** : `Pierre\Surveillance`

**Fichier** : `src/Pierre/Surveillance/WatcherInterface.php`

**Implémentations** :
- `ProjectWatcher` : Implémentation principale

**Méthodes requises** :

#### `start_surveillance(): bool`

Démarre la surveillance.

**Retour** : `true` si démarrée avec succès, `false` sinon

#### `stop_surveillance(): bool`

Arrête la surveillance.

**Retour** : `true` si arrêtée avec succès, `false` sinon

#### `is_surveillance_active(): bool`

Vérifie si la surveillance est active.

**Retour** : `true` si active, `false` sinon

#### `get_surveillance_status(): array`

Récupère le statut de la surveillance.

**Retour** : Tableau contenant les informations de statut

#### `watch_project(string $project_slug, string $locale_code): bool`

Surveille un projet spécifique.

**Paramètres** :
- `$project_slug` : Slug du projet
- `$locale_code` : Code de locale

**Retour** : `true` si le projet est maintenant surveillé

#### `unwatch_project(string $project_slug, string $locale_code): bool`

Arrête de surveiller un projet.

**Paramètres** :
- `$project_slug` : Slug du projet
- `$locale_code` : Code de locale

**Retour** : `true` si le projet n'est plus surveillé

#### `get_watched_projects(): array`

Récupère tous les projets surveillés.

**Retour** : Tableau des projets surveillés

### `NotifierInterface`

Interface définissant le contrat pour les composants de notification.

**Namespace** : `Pierre\Notifications`

**Fichier** : `src/Pierre/Notifications/NotifierInterface.php`

**Implémentations** :
- `SlackNotifier` : Implémentation Slack

**Méthodes requises** :

#### `send_notification(string $message, array $recipients, array $options = []): bool|\WP_Error`

Envoie une notification.

**Paramètres** :
- `$message` : Message à envoyer
- `$recipients` : Informations des destinataires
- `$options` : Options additionnelles

**Retour** : `true` si envoyé avec succès, `WP_Error` en cas d'échec

#### `send_bulk_notifications(array $messages, array $recipients, array $options = []): array`

Envoie plusieurs notifications en masse.

**Paramètres** :
- `$messages` : Tableau de messages
- `$recipients` : Informations des destinataires
- `$options` : Options additionnelles

**Retour** : Tableau de résultats pour chaque message

#### `test_notification(string $test_message = '...'): bool|\WP_Error`

Teste le système de notification.

**Paramètres** :
- `$test_message` : Message de test (optionnel)

**Retour** : `true` si le test réussit, `WP_Error` sinon

#### `is_ready(): bool`

Vérifie si le système est prêt.

**Retour** : `true` si prêt, `false` sinon

#### `get_status(): array`

Récupère le statut du système.

**Retour** : Tableau contenant les informations de statut

#### `format_message(string $message, array $context = []): array`

Formate un message pour l'envoi.

**Paramètres** :
- `$message` : Message brut
- `$context` : Contexte additionnel

**Retour** : Tableau du message formaté (payload)

## Traits

### `SlackDebugTrait`

Trait fournissant des fonctionnalités de logging debug pour les classes Slack.

**Namespace** : `Pierre\Notifications`

**Fichier** : `src/Pierre/Notifications/SlackDebugTrait.php`

**Utilisé par** :
- Classes liées à Slack nécessitant du logging

**Méthodes fournies** :

#### `is_debug(): bool`

Vérifie si le debug est activé.

**Retour** : `true` si `PIERRE_DEBUG` est défini et `true`

#### `log_debug(string $message): void`

Log un message de debug.

**Paramètres** :
- `$message` : Message à logger

**Comportement** :
- Ne fait rien si `PIERRE_DEBUG` n'est pas activé
- Utilise `error_log()` avec le préfixe `[wp-pierre]`

**Exemple d'utilisation** :

```php
use Pierre\Notifications\SlackDebugTrait;

class MySlackClass {
    use SlackDebugTrait;
    
    public function do_something() {
        $this->log_debug('Doing something important');
    }
}
```

### `StatusTrait`

Trait fournissant des fonctionnalités de gestion de statut.

**Namespace** : `Pierre\Traits`

**Fichier** : `src/Pierre/Traits/StatusTrait.php`

**Méthodes fournies** : (à documenter selon l'implémentation)

---

*Pierre says: These interfaces and traits help keep my code organized and extensible! 🪨*

