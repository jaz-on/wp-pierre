# Common Issues & Solutions

Problèmes fréquents et leurs solutions.

## Notifications Slack

### Pas de messages reçus

**Symptômes** : Les notifications ne sont pas envoyées à Slack.

**Solutions** :
1. **Vérifier l'URL du webhook**
   - Tester via le bouton "Test Notification" dans les réglages
   - Vérifier que l'URL commence par `https://hooks.slack.com/services/`

2. **Vérifier les logs PHP**
   - Activer `WP_DEBUG` et `WP_DEBUG_LOG`
   - Chercher les erreurs `[wp-pierre]` dans `wp-content/debug.log`

3. **Vérifier la configuration**
   - Types de notifications activés dans les réglages
   - Seuils de déclenchement appropriés
   - Mode (immediate vs digest) correctement configuré

4. **Vérifier le cooldown**
   - Attendre 2 minutes entre les tests
   - Le cooldown s'applique aux exécutions forcées

### Messages en double

**Symptômes** : Les mêmes notifications sont envoyées plusieurs fois.

**Solutions** :
1. **Vérifier les overlaps**
   - Webhook global + webhook locale pour le même projet
   - Ajuster les scopes pour éviter les doublons

2. **Vérifier les projets surveillés**
   - Un projet ne doit être surveillé qu'une fois
   - Vérifier dans **Pierre → Projects**

### Digest non envoyé

**Symptômes** : Les notifications en mode digest ne sont pas envoyées.

**Solutions** :
1. **Vérifier la configuration**
   - `mode=digest` activé
   - Fenêtre d'envoi configurée (interval ou fixed_time)

2. **Vérifier le cron**
   - Vérifier que `pierre_run_digest` est planifié
   - Exécuter manuellement : `wp cron event run pierre_run_digest`

3. **Vérifier la file d'attente**
   - Des événements doivent être en file d'attente
   - Vérifier les transients `pierre_digest_queue_*`

## Surveillance

### La surveillance ne s'exécute pas

**Symptômes** : Les projets ne sont pas surveillés automatiquement.

**Solutions** :
1. **Vérifier WP-Cron**
   ```bash
   wp cron event list | grep pierre
   ```
   - Vérifier que les événements sont planifiés

2. **Vérifier l'activation**
   - `surveillance_enabled` doit être `true` dans les réglages
   - Vérifier dans **Pierre → Settings**

3. **Exécuter manuellement**
   ```bash
   wp cron event run pierre_surveillance_check
   ```

4. **Vérifier les logs**
   - Chercher `run_surveillance_check` dans les logs
   - Vérifier les erreurs éventuelles

### Surveillance trop lente

**Symptômes** : La surveillance prend trop de temps.

**Solutions** :
1. **Réduire le nombre de projets**
   - Surveiller uniquement les projets prioritaires
   - Utiliser les scopes pour filtrer

2. **Augmenter l'intervalle**
   - Passer de 15 à 30 ou 60 minutes
   - Réduit la charge sur l'API

3. **Vérifier les timeouts**
   - Augmenter `request_timeout` dans les réglages
   - Vérifier la connectivité réseau

4. **Vérifier le backoff**
   - Consulter les logs pour les backoffs
   - Attendre l'expiration du backoff

### Erreurs API fréquentes

**Symptômes** : Beaucoup d'erreurs lors des appels API.

**Solutions** :
1. **Vérifier la connectivité**
   - Tester l'accès à `translate.wordpress.org`
   - Vérifier les firewalls/proxies

2. **Vérifier le rate limiting**
   - Respecter les limites de l'API
   - Augmenter l'intervalle de surveillance

3. **Vérifier les logs**
   - Chercher les codes d'erreur HTTP
   - Vérifier les messages d'erreur détaillés

## Discovery

### Liste de locales vide

**Symptômes** : Aucune locale disponible dans la discovery.

**Solutions** :
1. **Attendre le cache**
   - Le cache des locales expire après un certain temps
   - Utiliser le bouton "Refresh" si disponible

2. **Vérifier la connectivité**
   - Tester l'accès à l'API Polyglots
   - Vérifier les logs pour erreurs

3. **Rafraîchir manuellement**
   - Le cron hebdomadaire rafraîchit automatiquement
   - Attendre ou déclencher manuellement

### Catalogue de projets vide

**Symptômes** : Aucun projet disponible dans le catalogue.

**Solutions** :
1. **Charger la library**
   - Importer une library de projets
   - Format : une ligne par projet `type, slug`

2. **Vérifier le format**
   - Format attendu : `plugin, woocommerce`
   - Vérifier les erreurs de parsing

3. **Rafraîchir le cache**
   - Supprimer les transients `pierre_projects_catalog_*`
   - Recharger la library

## Base de Données

### Table non créée

**Symptômes** : La table `pierre_user_projects` n'existe pas.

**Solutions** :
1. **Réactiver le plugin**
   - Désactiver puis réactiver
   - La table est créée à l'activation

2. **Vérifier les permissions**
   - L'utilisateur MySQL doit avoir les permissions CREATE
   - Vérifier les logs pour erreurs SQL

3. **Créer manuellement**
   - Exécuter le SQL de création manuellement
   - Voir [Database Schema](../architecture/database.md)

### Données corrompues

**Symptômes** : Données incohérentes dans les options.

**Solutions** :
1. **Exporter les réglages**
   ```bash
   wp option get pierre_settings --format=json > backup.json
   ```

2. **Réinitialiser**
   - Supprimer l'option `pierre_settings`
   - Reconfigurer depuis zéro

3. **Restauration**
   ```bash
   wp option update pierre_settings --format=json < backup.json
   ```

## Performance

### Site lent après activation

**Symptômes** : Le site devient lent après l'activation du plugin.

**Solutions** :
1. **Vérifier les requêtes**
   - Utiliser Query Monitor pour identifier les requêtes lentes
   - Optimiser les requêtes SQL

2. **Vérifier le cache**
   - S'assurer que le cache fonctionne
   - Vérifier les transients expirés

3. **Réduire la charge**
   - Augmenter l'intervalle de surveillance
   - Réduire le nombre de projets surveillés

### Mémoire insuffisante

**Symptômes** : Erreurs "Memory limit exceeded".

**Solutions** :
1. **Augmenter la limite**
   ```php
   define('WP_MEMORY_LIMIT', '256M');
   ```

2. **Optimiser le code**
   - Traiter par lots
   - Libérer les ressources après utilisation

3. **Réduire la charge**
   - Surveiller moins de projets
   - Augmenter l'intervalle

## Permissions

### Capabilities manquantes

**Symptômes** : Les utilisateurs n'ont pas les permissions attendues.

**Solutions** :
1. **Réactiver le plugin**
   - Les capabilities sont ajoutées à l'activation
   - Vérifier dans **Pierre → Teams → Roles & Capabilities**

2. **Vérifier les rôles**
   - Les administrateurs ont toutes les capabilities
   - Vérifier les assignations d'équipe

3. **Vérifier manuellement**
   ```php
   $user = wp_get_current_user();
   var_dump($user->allcaps);
   ```

## Logs et Debugging

### Activer les logs

**Configuration** :
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('PIERRE_DEBUG', true);
```

**Emplacement** : `wp-content/debug.log`

### Logs structurés

Les logs utilisent le format structuré :
```
[wp-pierre][source] message action=X code=Y
```

**Throttling** : Les logs sont throttlés à 60 secondes par signature pour éviter les log storms.

---

*Pierre says: These solutions should help you resolve most issues! If you need more help, check the [GitHub Issues](https://github.com/jaz-on/wp-pierre/issues)! 🪨*

