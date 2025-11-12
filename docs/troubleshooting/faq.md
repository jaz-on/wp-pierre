# FAQ

Questions fréquentes sur WP-Pierre.

## Installation et Configuration

### Quels sont les prérequis ?

- WordPress 6.0 ou supérieur
- PHP 8.3 ou supérieur
- MySQL 5.7+ ou MariaDB 10.3+
- Slack workspace (optionnel mais recommandé)

### Comment installer le plugin ?

Voir [Getting Started](../getting-started/) pour un guide complet.

### Le plugin fonctionne-t-il en multisite ?

Oui, Pierre supporte WordPress multisite. Chaque site peut avoir sa propre configuration.

## Fonctionnalités

### Combien de projets puis-je surveiller ?

Il n'y a pas de limite technique. Pierre peut surveiller des centaines de projets.

### Puis-je surveiller plusieurs locales ?

Oui, vous pouvez surveiller autant de locales que nécessaire.

### Les notifications sont-elles en temps réel ?

Par défaut, oui (mode immédiat). Vous pouvez aussi utiliser le mode digest pour regrouper les notifications.

### Puis-je avoir des canaux Slack différents par locale ?

Oui, configurez un webhook spécifique pour chaque locale dans la page Locales.

## Problèmes Courants

### Les notifications n'arrivent pas dans Slack

1. Vérifiez que le webhook est correctement configuré
2. Testez la notification via Admin → Settings → Global Webhook → "Tester"
3. Vérifiez les logs si `PIERRE_DEBUG` est activé
4. Vérifiez que les seuils sont atteints

### La surveillance ne démarre pas

1. Vérifiez que la surveillance est activée
2. Vérifiez que des projets sont surveillés
3. Vérifiez que WordPress cron fonctionne
4. Consultez les logs d'erreur

### Les données ne se mettent pas à jour

1. Videz le cache : Admin → Settings → "Vider le cache"
2. Vérifiez la connexion à l'API translate.wordpress.org
3. Vérifiez les logs pour des erreurs API

## Performance

### Le plugin ralentit mon site ?

Non, Pierre est conçu pour être performant :
- Utilise le cache WordPress
- Traitement asynchrone via cron
- Optimisations de requêtes

### Combien de requêtes API fait le plugin ?

Cela dépend du nombre de projets surveillés et de l'intervalle. Le plugin utilise un cache pour minimiser les requêtes.

## Sécurité

### Les webhooks sont-ils sécurisés ?

Oui, les URLs de webhook sont chiffrées dans la base de données avec defuse/php-encryption.

### Qui peut accéder au dashboard public ?

Le dashboard public est accessible à tous (pas d'authentification). Il est en lecture seule.

## Support

### Où trouver de l'aide ?

- Documentation : [Wiki GitHub](https://github.com/jaz-on/wp-pierre/wiki)
- Issues : [GitHub Issues](https://github.com/jaz-on/wp-pierre/issues)
- Email : bonjour@jasonrouet.com

### Comment signaler un bug ?

Ouvrez une issue sur GitHub avec :
- Version de WordPress et PHP
- Description du problème
- Logs d'erreur (si disponibles)

---

*Pierre says: I hope these answers help you! 🪨*

