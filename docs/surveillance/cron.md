# Surveillance & Cron

Système de surveillance automatique et gestion des tâches planifiées.

## Vue d'ensemble

Le système de surveillance de Pierre utilise WordPress Cron pour exécuter des tâches périodiques :

- **Surveillance** : Vérification périodique des traductions
- **Nettoyage** : Suppression des données anciennes
- **Digest** : Envoi des notifications groupées
- **Rafraîchissement** : Mise à jour du cache des locales

## Intervalles Personnalisés

Le plugin enregistre des intervalles cron personnalisés :

- `pierre_5min` : 5 minutes
- `pierre_15min` : 15 minutes (défaut)
- `pierre_30min` : 30 minutes
- `pierre_60min` : 60 minutes
- `pierre_120min` : 120 minutes
- `pierre_daily` : 24 heures (nettoyage)
- `pierre_weekly` : 7 jours (rafraîchissement locales)

L'intervalle de surveillance est configurable dans les réglages (défaut: 15 minutes).

## Hooks Cron

### `pierre_surveillance_check`

Vérification périodique des projets surveillés.

**Fréquence** : Configurable (5, 15, 30, 60, ou 120 minutes)

**Actions** :
1. Récupère la liste des projets surveillés
2. Pour chaque projet :
   - Récupère les données depuis l'API
   - Compare avec les données précédentes
   - Détecte les changements
   - Déclenche les notifications si nécessaire

**Progression** :
- Stockée dans `pierre_surv_progress` (transient)
- Format : `['processed' => X, 'total' => Y, 'ts' => timestamp]`

**Arrêt** :
- Vérifie `pierre_surv_abort` (transient) pour arrêt best-effort
- Peut être déclenché via AJAX `pierre_abort_surveillance_run`

### `pierre_cleanup_old_data`

Nettoyage quotidien des données anciennes.

**Fréquence** : Quotidienne (avec jitter aléatoire)

**Actions** :
1. Supprime les transients expirés (> 7 jours)
2. Nettoie les erreurs de surveillance anciennes (> 24 heures)
3. Nettoie les logs de sécurité anciens

### `pierre_refresh_locales_cache`

Rafraîchissement hebdomadaire du cache des locales.

**Fréquence** : Hebdomadaire

**Actions** :
1. Déclenche l'action `pierre_refresh_locales_cache`
2. Permet aux handlers admin de reconstruire le cache

### `pierre_run_digest`

Envoi des notifications en mode digest.

**Fréquence** : Alignée sur l'intervalle de surveillance

**Actions** :
1. Vérifie les files d'attente par locale
2. Détermine si un digest est dû (interval ou fixed_time)
3. Construit et envoie les messages groupés
4. Vide les files d'attente

## Configuration

### Intervalle de Surveillance

Configurable dans **Pierre → Settings → Surveillance Interval**.

**Options** : 5, 15, 30, 60, ou 120 minutes

**Impact** :
- Fréquence des vérifications
- Charge sur l'API translate.wordpress.org
- Délai de détection des changements

### Auto-start

Option `auto_start_surveillance` dans les réglages.

**Défaut** : `true`

Si activé, la surveillance démarre automatiquement à l'activation du plugin.

## WP-Cron vs Cron Système

### WP-Cron (défaut)

WordPress Cron s'exécute lors des requêtes HTTP.

**Avantages** :
- Aucune configuration serveur requise
- Fonctionne sur tous les hébergements

**Inconvénients** :
- Dépend des visites du site
- Peut être retardé sur sites peu fréquentés

### Cron Système

Pour les sites à fort trafic, utilisez le cron système.

**Configuration** :
```bash
# Exécuter toutes les 15 minutes
*/15 * * * * cd /path/to/wordpress && php wp-cron.php >/dev/null 2>&1
```

**Désactiver WP-Cron** :
```php
define('DISABLE_WP_CRON', true);
```

**Exécution manuelle** :
```bash
wp cron event run pierre_surveillance_check
```

## Monitoring

### Métriques Disponibles

**Options WordPress** :
- `pierre_last_surv_run` : Timestamp de la dernière surveillance
- `pierre_last_surv_duration_ms` : Durée en millisecondes
- `pierre_last_digest_run` : Timestamp du dernier digest
- `pierre_last_digest_duration_ms` : Durée en millisecondes

**Transients** :
- `pierre_surv_progress` : Progression actuelle
- `pierre_surv_abort` : Flag d'arrêt

### Logs

Les événements cron sont loggés via `do_action('wp_pierre_debug', ...)` :

- `run_surveillance_check:start` : Début de surveillance
- `run_surveillance_check:end` : Fin avec durée
- `run_digest:start` : Début de digest
- `run_digest:end` : Fin avec durée
- `api_call` : Appels API avec timing

## Cooldown

Un cooldown de 2 minutes est appliqué pour les exécutions forcées (via AJAX) pour éviter le spam.

**Appliquer** :
- Surveillance manuelle
- Nettoyage manuel
- Tests de notification

## Dépannage

### La surveillance ne s'exécute pas

1. Vérifier que WP-Cron est actif : `wp cron event list`
2. Vérifier que l'événement est planifié : `wp cron event list | grep pierre`
3. Vérifier les logs pour erreurs
4. Exécuter manuellement : `wp cron event run pierre_surveillance_check`

### Surveillance trop lente

1. Réduire le nombre de projets surveillés
2. Augmenter l'intervalle de surveillance
3. Vérifier les timeouts API
4. Consulter les logs de backoff

### Digest non envoyé

1. Vérifier que `mode=digest` est configuré
2. Vérifier la fenêtre d'envoi (interval ou fixed_time)
3. Vérifier que des événements sont en file d'attente
4. Vérifier l'exécution du cron `pierre_run_digest`

---

*Pierre says: My surveillance system keeps watch over translations automatically! 🪨*

