# Personnalisation

Guide pour personnaliser et étendre WP-Pierre.

## Hooks pour Personnalisation

### Modifier les Requêtes API

**Contexte** : Ce filtre permet de modifier les arguments des requêtes HTTP vers les APIs externes (translate.wordpress.org, Slack) avant leur envoi.

**Exemple PHP** :
```php
/**
 * Augmenter le timeout pour les requêtes vers translate.wordpress.org
 */
add_filter('pierre_api_request_args', function($args, $url) {
    // Modifier les arguments de requête
    if (strpos($url, 'translate.wordpress.org') !== false) {
        $args['timeout'] = 60; // Augmenter à 60 secondes
        $args['headers']['User-Agent'] = 'MyCustomBot/1.0';
    }
    return $args;
}, 10, 2);
```

### Personnaliser les Messages Slack

**Contexte** : Ce filtre permet de modifier le message Slack avant son envoi. Le message est formaté en Block Kit Slack.

**Exemple PHP** :
```php
/**
 * Ajouter un emoji personnalisé selon le type de notification
 */
add_filter('pierre_notification_message', function($formatted, $message, $context) {
    // Ajouter du contenu personnalisé selon le type
    $emoji = '';
    switch ($context['type']) {
        case 'new_strings':
            $emoji = '🆕';
            break;
        case 'milestone':
            $emoji = '🎉';
            break;
        case 'needs_attention':
            $emoji = '⚠️';
            break;
    }
    
    if ($emoji) {
        $formatted['blocks'][] = [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => $emoji . ' ' . ($message['text'] ?? 'Notification personnalisée')
            ]
        ];
    }
    
    return $formatted;
}, 10, 3);
```

### Enrichir les Données de Traduction

**Contexte** : Ce filtre permet d'enrichir les données de traduction récupérées depuis l'API translate.wordpress.org avec des métadonnées personnalisées.

**Exemple PHP** :
```php
/**
 * Ajouter des métadonnées personnalisées aux données de traduction
 */
add_filter('pierre_translation_data', function($data, $project_slug, $locale_code, $project_type) {
    // Ajouter des métadonnées depuis une source externe
    $custom_metadata = get_option("pierre_custom_metadata_{$project_slug}_{$locale_code}", []);
    
    if (!empty($custom_metadata)) {
        $data['custom_metadata'] = $custom_metadata;
        $data['last_updated_custom'] = current_time('mysql');
    }
    
    // Calculer un score personnalisé
    $data['custom_score'] = calculate_custom_score($data);
    
    return $data;
}, 10, 4);

/**
 * Fonction helper pour calculer un score personnalisé
 */
function calculate_custom_score($data) {
    $translated = $data['translated'] ?? 0;
    $total = $translated + ($data['untranslated'] ?? 0);
    return $total > 0 ? round(($translated / $total) * 100, 2) : 0;
}
```

## Surcharger les Templates

Créez des templates dans votre thème :

```text
wp-content/themes/your-theme/pierre/
├── admin/
│   └── dashboard.php
└── public/
    └── dashboard.php
```

## Extension du Plugin

### Créer un Notifier Personnalisé

```php
class MyCustomNotifier implements \Pierre\Notifications\NotifierInterface {
    public function send_notification(string $message, array $recipients, array $options = []): bool|\WP_Error {
        // Implémentation personnalisée
        return true;
    }
    // ... autres méthodes requises
}
```

### Créer un Watcher Personnalisé

```php
class MyCustomWatcher implements \Pierre\Surveillance\WatcherInterface {
    public function start_surveillance(): bool {
        // Implémentation personnalisée
        return true;
    }
    // ... autres méthodes requises
}
```

## Intégration avec d'Autres Plugins

### Utiliser les Hooks de Debug

```php
add_action('wp_pierre_debug', function($message, $context) {
    // Logger vers un service externe
    if ($context['scope'] === 'surveillance') {
        log_to_external_service($message, $context);
    }
}, 10, 2);
```

Voir [Hooks](../api/hooks.md) pour la liste complète des hooks disponibles.

---

*Pierre says: Customize me to fit your needs! 🪨*

