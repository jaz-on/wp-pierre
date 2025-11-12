# API Integration

Intégration avec les APIs externes (translate.wordpress.org et Slack).

## translate.wordpress.org API

### Base URL

```text
https://translate.wordpress.org/api/projects
```

### Segments

Le plugin détecte automatiquement le bon segment pour chaque projet :

- `wp` : WordPress Core
- `wp-plugins` : Plugins
- `wp-themes` : Thèmes
- `meta` : Projets meta
- `apps` : Applications

### Endpoints

**Format** : `/{segment}/{project_slug}/{locale_code}/{set}/`

**Exemple** :
```text
https://translate.wordpress.org/api/projects/wp-plugins/woocommerce/fr/default/
```

**Paramètres** :
- `segment` : Segment du projet (détecté automatiquement)
- `project_slug` : Slug du projet (ex: `woocommerce`)
- `locale_code` : Code de locale (ex: `fr`, `es_ES`)
- `set` : Sous-ensemble de traduction (par défaut: `default`)

### Format de Réponse

```json
{
  "name": "WooCommerce",
  "slug": "woocommerce",
  "translation_sets": [
    {
      "name": "WooCommerce",
      "locale": "fr",
      "translated": 800,
      "untranslated": 200,
      "waiting": 50,
      "fuzzy": 10,
      "percent_translated": 80.0
    }
  ]
}
```

### Gestion des Erreurs

**Rate Limiting** :
- Le plugin respecte l'en-tête `Retry-After` en cas de 429
- Backoff automatique de 300 secondes par défaut
- Backoff par projet pour éviter la surcharge

**Retry** :
- Retry automatique sur erreurs 5xx ou erreurs réseau
- Maximum 1 retry par requête

**Cache** :
- Réponses mises en cache via transients (1 heure)
- Réduction des appels API répétés

## Slack Webhook Integration

### Configuration

**URL du webhook** : Configurée dans les réglages admin

**Format** : `https://hooks.slack.com/services/[TEAM_ID]/[BOT_ID]/[TOKEN]`

**Sécurité** :
- URLs chiffrées dans la base de données
- Validation avant sauvegarde (domaine `hooks.slack.com`)
- Décryptage automatique lors de l'utilisation

### Format de Payload

Le plugin utilise le format Block Kit avec compatibilité Attachments :

```json
{
  "text": "Pierre's message",
  "blocks": [
    {
      "type": "section",
      "text": {
        "type": "mrkdwn",
        "text": "*Nouveaux strings disponibles*\n\nProjet: WooCommerce\nLocale: fr\nNouveaux: 25"
      }
    },
    {
      "type": "divider"
    }
  ],
  "attachments": [
    {
      "color": "good",
      "footer": "Pierre - WordPress Translation Monitor",
      "footer_icon": "https://s.w.org/images/wmark.png",
      "ts": 1234567890
    }
  ]
}
```

### Types de Messages

**Nouveaux strings** :
- Déclenché quand `waiting` ≥ seuil configuré
- Inclut le nombre de nouveaux strings

**Mise à jour de complétion** :
- Déclenché quand le pourcentage de complétion augmente
- Affiche l'ancien et le nouveau pourcentage

**Besoin d'attention** :
- Déclenché quand `waiting + fuzzy > 0`
- Liste les strings nécessitant une action

**Jalon** :
- Déclenché quand un pourcentage de complétion est atteint
- Configurable (ex: 50%, 75%, 100%)

### Mode Digest

Le mode digest regroupe plusieurs notifications :

**Interval** :
- Envoi toutes les X minutes (minimum 15)
- Regroupe tous les événements depuis le dernier envoi

**Fixed Time** :
- Envoi à une heure fixe (format HH:MM)
- Fenêtre de 15 minutes pour l'envoi

### Gestion des Erreurs

**Codes de réponse** :
- `200` : Succès (body doit contenir `ok`)
- Autres : Échec consigné dans les logs

**Retry** :
- Pas de retry automatique pour Slack
- Les échecs sont loggés pour investigation

## Hooks WordPress

### Filtres

**`pierre_api_request_args`**
- Modifie les arguments des requêtes HTTP
- Paramètres : `$args`, `$url`
- Utilisation : Personnaliser timeout, headers, etc.

**`pierre_notification_message`**
- Modifie le message Slack avant envoi
- Paramètres : `$formatted`, `$message`, `$context`
- Utilisation : Personnaliser le format des messages

**`pierre_translation_data`**
- Modifie les données de traduction après récupération
- Paramètres : `$translation_data`, `$project_slug`, `$locale_code`, `$project_type`
- Utilisation : Enrichir ou filtrer les données

**`pierre_digest_max_projects`**
- Limite le nombre de projets dans un digest
- Valeur par défaut : 20

**`pierre_digest_chunk_size`**
- Taille des chunks pour les digests
- Valeur par défaut : 20

### Actions

**`wp_pierre_debug`**
- Logging centralisé pour le debugging
- Paramètres : `$message`, `$context`
- Utilisation : Logs structurés avec throttling

**`pierre_refresh_locales_cache`**
- Déclenche le rafraîchissement du cache des locales
- Utilisation : Rafraîchissement manuel ou programmé

## Endpoints AJAX

### Admin AJAX

Tous les endpoints admin utilisent le nonce `pierre_admin_ajax`.

**`pierre_admin_save_settings`**
- Sauvegarde les réglages
- Capability : `pierre_manage_settings`

**`pierre_admin_test_notification`**
- Teste la configuration Slack
- Capability : `pierre_manage_notifications`

**`pierre_fetch_locales`**
- Récupère la liste des locales disponibles
- Capability : `pierre_view_dashboard`

**`pierre_run_surveillance_now`**
- Démarre une surveillance immédiate
- Capability : `pierre_manage_projects`
- Cooldown : 2 minutes

**`pierre_abort_surveillance_run`**
- Arrête la surveillance en cours
- Capability : `pierre_manage_projects`

**`pierre_get_progress`**
- Récupère la progression de la surveillance
- Capability : `pierre_manage_projects`

Voir [API Reference](index.md) pour la liste complète.

## Sécurité

### Requêtes HTTP

- Utilisation de `wp_safe_remote_get()` pour prévenir les attaques SSRF
- Validation des URLs avant utilisation
- Timeout configurable (défaut: 30 secondes)
- User-Agent personnalisé

### Webhooks Slack

- Chiffrement des URLs dans la base de données
- Validation du domaine `hooks.slack.com`
- Décryptage uniquement lors de l'utilisation

---

*Pierre says: These APIs help me communicate with the translation platform and Slack! 🪨*

