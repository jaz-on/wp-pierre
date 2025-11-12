# Slack Notifications

Configuration et utilisation des notifications Slack.

## Vue d'ensemble

Pierre envoie des notifications Slack pour informer les équipes de traduction des changements dans les projets surveillés.

## Configuration

### Webhook Global

Configuration d'un webhook Slack global dans **Pierre → Settings → Global Webhook**.

**Étapes** :
1. Créer un webhook Slack dans votre workspace
2. Copier l'URL du webhook
3. Coller dans les réglages Pierre
4. L'URL est automatiquement chiffrée lors de la sauvegarde

**URL Format** :
```text
https://hooks.slack.com/services/[TEAM_ID]/[BOT_ID]/[TOKEN]
```

### Webhooks par Locale

Configuration de webhooks spécifiques par locale dans **Pierre → Settings → Locales**.

**Avantages** :
- Canaux Slack dédiés par locale
- Seuils et modes différents par locale
- Meilleure organisation des notifications

## Types de Notifications

### Nouveaux Strings (`new_strings`)

Déclenché quand de nouveaux strings sont disponibles pour traduction.

**Seuil** : Configurable (`new_strings_threshold`)

**Exemple** :
```text
*Nouveaux strings disponibles*

Projet: WooCommerce
Locale: fr
Nouveaux: 25 strings
```

### Mise à jour de Complétion (`completion_update`)

Déclenché quand le pourcentage de complétion augmente.

**Exemple** :
```text
*Progression de traduction*

Projet: WooCommerce
Locale: fr
Complétion: 75% → 80% (+5%)
```

### Besoin d'Attention (`needs_attention`)

Déclenché quand des strings nécessitent une action (`waiting + fuzzy > 0`).

**Exemple** :
```text
*Action requise*

Projet: WooCommerce
Locale: fr
En attente: 15 strings
Fuzzy: 5 strings
```

### Jalon (`milestone`)

Déclenché quand un pourcentage de complétion est atteint.

**Seuils** : Configurables (ex: `[50, 75, 100]`)

**Exemple** :
```text
*🎉 Jalon atteint!*

Projet: WooCommerce
Locale: fr
Complétion: 100% ✓
```

## Modes d'Envoi

### Mode Immédiat (`immediate`)

Chaque événement déclenche une notification immédiate.

**Avantages** :
- Réactivité maximale
- Informations en temps réel

**Inconvénients** :
- Peut générer beaucoup de messages
- Risque de spam sur projets actifs

### Mode Digest (`digest`)

Les notifications sont regroupées et envoyées périodiquement.

**Configuration** :

**Type Interval** :
- Envoi toutes les X minutes (minimum 15)
- Regroupe tous les événements depuis le dernier envoi

**Type Fixed Time** :
- Envoi à une heure fixe (format HH:MM)
- Fenêtre de 15 minutes pour l'envoi

**Avantages** :
- Moins de messages
- Vue d'ensemble consolidée
- Meilleure organisation

## Format des Messages

### Block Kit

Le plugin utilise le format Block Kit de Slack pour des messages riches :

```json
{
  "blocks": [
    {
      "type": "section",
      "text": {
        "type": "mrkdwn",
        "text": "*Titre*\n\nContenu du message"
      }
    },
    {
      "type": "divider"
    }
  ]
}
```

### Compatibilité Attachments

Pour compatibilité avec les anciennes versions de Slack :

```json
{
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

## Test de Configuration

### Via l'Interface Admin

**Pierre → Settings → Test Notification**

Envoie un message de test pour vérifier la configuration.

### Via cURL

```bash
curl -X POST -H 'Content-type: application/json' \
  --data '{"text":"Pierre test webhook 🪨"}' \
  https://hooks.slack.com/services/[TEAM_ID]/[BOT_ID]/[TOKEN]
```

## Personnalisation

### Filtre `pierre_notification_message`

Modifier le format des messages avant envoi :

```php
add_filter('pierre_notification_message', function($formatted, $message, $context) {
    // Personnaliser $formatted
    return $formatted;
}, 10, 3);
```

### Filtre `pierre_api_request_args`

Modifier les arguments des requêtes HTTP :

```php
add_filter('pierre_api_request_args', function($args, $webhook_url) {
    // Modifier timeout, headers, etc.
    return $args;
}, 10, 2);
```

## Gestion des Erreurs

### Codes de Réponse

- `200` : Succès (body doit contenir `ok`)
- `400` : Requête invalide
- `404` : Webhook introuvable
- `429` : Rate limit (non géré actuellement)
- Autres : Échec consigné dans les logs

### Logs

Les échecs sont loggés via `error_log()` avec le message d'erreur.

**Format** :
```
[wp-pierre] [ERROR] Failed to send notification: HTTP 404
```

## Sécurité

### Chiffrement

Les URLs de webhook sont chiffrées dans la base de données :
- Utilisation de `defuse/php-encryption` (recommandé)
- Fallback OpenSSL avec IV uniques
- Clé stockée de manière sécurisée

### Validation

- Validation du domaine `hooks.slack.com` avant sauvegarde
- Sanitization de toutes les entrées
- Vérification des permissions avant envoi

## Dépannage

### Pas de messages reçus

1. Vérifier l'URL du webhook (test via bouton)
2. Vérifier les logs PHP pour erreurs
3. Vérifier que les types de notifications sont activés
4. Vérifier les seuils de déclenchement
5. Vérifier le mode (immediate vs digest)

### Messages en double

1. Vérifier les overlaps entre webhook global et locale
2. Ajuster les scopes pour éviter les doublons
3. Vérifier que les projets ne sont pas surveillés plusieurs fois

### Digest non envoyé

1. Vérifier que `mode=digest` est configuré
2. Vérifier la fenêtre d'envoi (interval ou fixed_time)
3. Vérifier que des événements sont en file d'attente
4. Vérifier l'exécution du cron `pierre_run_digest`

---

*Pierre says: My Slack notifications keep teams informed about translation progress! 🪨*

