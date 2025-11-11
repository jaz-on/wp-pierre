# Guide de Migration

Guide pour migrer entre les versions de WP-Pierre.

## Vue d'Ensemble

Ce guide vous aide à migrer votre installation de WP-Pierre d'une version à une autre. Les migrations peuvent inclure des changements de schéma de base de données, de structure de données, ou de configuration.

## Version 1.0.0

Version initiale du plugin. Aucune migration nécessaire pour cette version.

## Préparation à la Migration

Avant de migrer :

1. **Sauvegarder les données** :
   - Exporter les projets surveillés
   - Exporter les assignations d'équipes
   - Sauvegarder la configuration (webhooks, seuils, etc.)
   - Sauvegarder la base de données WordPress

2. **Vérifier les prérequis** :
   - WordPress 6.0 ou supérieur
   - PHP 8.3 ou supérieur
   - MySQL 5.7+ ou MariaDB 10.3+

3. **Tester en environnement de développement** :
   - Tester la migration sur une copie de votre site
   - Vérifier que toutes les fonctionnalités fonctionnent
   - Vérifier que les données sont correctement migrées

## Processus de Migration

### Migration Automatique

WP-Pierre effectue automatiquement les migrations nécessaires lors de l'activation ou de la mise à jour :

1. Détection de la version actuelle
2. Exécution des migrations nécessaires
3. Mise à jour de la version dans la base de données
4. Vérification de l'intégrité des données

### Migration Manuelle

Si nécessaire, vous pouvez forcer une migration manuelle :

1. Désactiver le plugin
2. Mettre à jour les fichiers du plugin
3. Réactiver le plugin (les migrations s'exécuteront automatiquement)

## Vérification Post-Migration

Après la migration, vérifiez :

- ✅ Les projets surveillés sont toujours présents
- ✅ Les assignations d'équipes sont intactes
- ✅ Les webhooks sont toujours configurés
- ✅ La surveillance fonctionne correctement
- ✅ Les notifications sont envoyées
- ✅ Le dashboard public est accessible

## Problèmes Courants

### Données Manquantes

Si des données semblent manquantes après la migration :

1. Vérifiez les logs d'erreur WordPress
2. Vérifiez que la migration s'est bien terminée
3. Restaurez depuis la sauvegarde si nécessaire

### Erreurs de Migration

Si des erreurs surviennent pendant la migration :

1. Consultez les logs d'erreur
2. Vérifiez les permissions de la base de données
3. Contactez le support si le problème persiste

## Support

Pour obtenir de l'aide avec la migration :

- Documentation : [Wiki GitHub](https://github.com/jaz-on/wp-pierre/wiki)
- Issues : [GitHub Issues](https://github.com/jaz-on/wp-pierre/issues)
- Email : bonjour@jasonrouet.com

---

*Pierre says: I'll help you migrate smoothly between versions! 🪨*

