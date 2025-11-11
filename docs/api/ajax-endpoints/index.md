# Endpoints AJAX

Documentation complète de tous les endpoints AJAX du plugin WP-Pierre.

## Vue d'Ensemble

Tous les endpoints AJAX utilisent le système standard WordPress `admin-ajax.php`. Les endpoints admin nécessitent une authentification et des capabilities appropriées.

### Authentification

**Nonces** :
- Endpoints admin : `pierre_admin_ajax` (nonce dans `$_POST['nonce']` ou `$_REQUEST['nonce']`)
- Endpoints public : `pierre_ajax` (nonce dans `$_POST['nonce']`)

**Capabilities** :
- Chaque endpoint requiert une capability spécifique (voir détails ci-dessous)
- Les administrateurs (`manage_options`) ont accès à tous les endpoints

### Format de Réponse

**Succès** :
```json
{
  "success": true,
  "data": { ... }
}
```

**Erreur** :
```json
{
  "success": false,
  "data": {
    "code": "error_code",
    "message": "Error message"
  }
}
```

## Documentation par Catégorie

- [Dashboard et Statistiques](dashboard.md) - Endpoints pour le dashboard et les statistiques
- [Gestion des Équipes](teams.md) - Endpoints pour la gestion des équipes
- [Notifications](notifications.md) - Endpoints pour les notifications Slack
- [Réglages](settings.md) - Endpoints pour les réglages du plugin
- [Surveillance](surveillance.md) - Endpoints pour la surveillance des traductions
- [Projets](projects.md) - Endpoints pour la gestion des projets
- [Locales](locales.md) - Endpoints pour la gestion des locales
- [Découverte de Projets](discovery.md) - Endpoints pour la découverte de projets
- [Catalogue de Projets](catalog.md) - Endpoints pour le catalogue de projets
- [Rapports](reports.md) - Endpoints pour les rapports
- [Exports](exports.md) - Endpoints pour les exports
- [Nettoyage](cleanup.md) - Endpoints pour le nettoyage
- [Endpoints Public](public.md) - Endpoints publics (sans authentification)
- [Gestion des Erreurs](errors.md) - Codes d'erreur et rate limiting

## Exemples d'Utilisation

### Exemple JavaScript (Admin)

**Contexte** : Utiliser jQuery pour appeler un endpoint AJAX depuis l'interface d'administration. L'objet `pierreAdminL10n` est automatiquement disponible et contient les nonces et URLs nécessaires.

**Exemple de base** :
```javascript
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'pierre_admin_get_stats',
        nonce: pierreAdminL10n.nonce
    },
    success: function(response) {
        if (response.success) {
            console.log('Stats:', response.data);
        } else {
            console.error('Error:', response.data.message);
        }
    }
});
```

**Exemple avec gestion d'erreur complète** :
```javascript
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'pierre_admin_get_stats',
        nonce: pierreAdminL10n.nonce
    },
    beforeSend: function() {
        // Afficher un indicateur de chargement
        jQuery('#stats-container').html('<p>Chargement...</p>');
    },
    success: function(response) {
        if (response.success) {
            // Afficher les statistiques
            displayStats(response.data);
        } else {
            // Afficher l'erreur
            jQuery('#stats-container').html(
                '<div class="error"><p>' + response.data.message + '</p></div>'
            );
        }
    },
    error: function(xhr, status, error) {
        // Gérer les erreurs réseau
        jQuery('#stats-container').html(
            '<div class="error"><p>Erreur réseau: ' + error + '</p></div>'
        );
    }
});

function displayStats(data) {
    // Fonction personnalisée pour afficher les statistiques
    var html = '<ul>';
    if (data.stats) {
        data.stats.forEach(function(stat) {
            html += '<li>' + stat.label + ': ' + stat.value + '</li>';
        });
    }
    html += '</ul>';
    jQuery('#stats-container').html(html);
}
```

**Exemple avec Fetch API (moderne)** :
```javascript
fetch(ajaxurl, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
        action: 'pierre_admin_get_stats',
        nonce: pierreAdminL10n.nonce
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Stats:', data.data);
    } else {
        console.error('Error:', data.data.message);
    }
})
.catch(error => {
    console.error('Network error:', error);
});
```

### Exemple PHP

```php
// Appeler un endpoint AJAX depuis PHP
$response = wp_remote_post(admin_url('admin-ajax.php'), [
    'body' => [
        'action' => 'pierre_admin_get_stats',
        'nonce' => wp_create_nonce('pierre_admin_ajax')
    ]
]);

if (!is_wp_error($response)) {
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if ($data['success']) {
        // Traiter les données
    }
}
```

---

*Pierre says: All these endpoints help you interact with me programmatically! 🪨*

