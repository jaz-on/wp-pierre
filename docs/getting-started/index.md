# Getting Started

Guide complet pour installer et configurer Pierre pour la première fois.

## Prérequis

Avant d'installer Pierre, assurez-vous d'avoir :

- **WordPress** : Version 6.0 ou supérieure
- **PHP** : Version 8.3 ou supérieure
- **MySQL** : Version 5.7+ ou MariaDB 10.3+
- **Slack** : Un workspace Slack avec la possibilité de créer des webhooks (optionnel mais recommandé)

## Installation

### Installation Manuelle

1. **Télécharger le plugin**
   - Téléchargez la dernière version depuis [GitHub](https://github.com/jaz-on/wp-pierre/releases)
   - Ou clonez le dépôt : `git clone https://github.com/jaz-on/wp-pierre.git`

2. **Uploader le plugin**
   - Placez le dossier `wp-pierre` dans `/wp-content/plugins/` de votre installation WordPress
   - Le chemin final doit être : `/wp-content/plugins/wp-pierre/`

3. **Activer le plugin**
   - Connectez-vous à votre administration WordPress
   - Allez dans **Extensions** → **Extensions installées**
   - Trouvez "Pierre - Translation Monitor" et cliquez sur **Activer**

### Installation via Composer

Si vous utilisez Composer pour gérer vos dépendances :

```bash
composer require wp-pierre/pierre
```

Puis activez le plugin via l'interface WordPress ou WP-CLI :

```bash
wp plugin activate wp-pierre
```

## Configuration Initiale

Après l'activation, Pierre est prêt à être configuré. Suivez ces étapes dans l'ordre :

### Étape 1 : Accéder aux Réglages

1. Dans l'administration WordPress, allez dans **Pierre** → **Réglages**
2. Vous verrez plusieurs onglets de configuration

### Étape 2 : Configurer le Webhook Global Slack

Le webhook global est utilisé par défaut pour toutes les notifications, sauf si vous configurez des webhooks spécifiques par locale.

1. **Créer un webhook Slack** :
   - Allez dans votre workspace Slack
   - Ouvrez **Paramètres** → **Gérer les apps** → **Incoming Webhooks**
   - Cliquez sur **Ajouter au Slack**
   - Choisissez le canal où recevoir les notifications
   - Copiez l'URL du webhook (format : `https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX`)

2. **Configurer dans Pierre** :
   - Dans **Pierre** → **Réglages** → **Webhook Global**
   - Collez l'URL du webhook dans le champ **URL du Webhook**
   - Configurez les **Types de notifications** à recevoir :
     - ✅ Nouveaux strings
     - ✅ Mise à jour de complétion
     - ✅ Besoin d'attention
     - ✅ Jalons de traduction
   - Définissez les **Seuils** (thresholds) :
     - **Nouveaux strings** : Nombre minimum de nouveaux strings pour déclencher une notification (ex: 10)
     - **Complétion** : Variation de pourcentage pour déclencher (ex: 5%)
     - **Besoin d'attention** : Nombre de strings en attente/fuzzy (ex: 1)
   - Choisissez le **Mode** :
     - **Immédiat** : Notification envoyée dès détection
     - **Digest** : Notifications regroupées (voir [Notifications](../notifications/slack.md))

3. **Tester la configuration** :
   - Cliquez sur **Tester la notification**
   - Vérifiez que le message arrive dans votre canal Slack

### Étape 3 : Découvrir et Ajouter des Locales

Pierre surveille les traductions pour des locales spécifiques (ex: `fr`, `es_ES`, `de_DE`).

1. **Accéder à la découverte** :
   - Allez dans **Pierre** → **Réglages** → **Découverte de Locales**

2. **Récupérer la liste des locales** :
   - Cliquez sur **Récupérer les locales disponibles**
   - Pierre interroge l'API translate.wordpress.org pour obtenir la liste complète

3. **Sélectionner vos locales** :
   - Cochez les locales que vous souhaitez surveiller
   - Exemples : `fr` (Français), `es_ES` (Espagnol), `de_DE` (Allemand)
   - Cliquez sur **Ajouter les locales sélectionnées**

4. **Vérifier** :
   - Allez dans **Pierre** → **Locales**
   - Vous devriez voir vos locales listées

### Étape 4 : Découvrir et Ajouter des Projets

Pierre peut surveiller des plugins, thèmes, projets meta, ou WordPress Core.

#### Option A : Découverte Automatique

1. **Accéder au catalogue** :
   - Allez dans **Pierre** → **Réglages** → **Découverte de Projets**
   - Cliquez sur **Parcourir le Catalogue**

2. **Rechercher des projets** :
   - Utilisez la recherche pour trouver des projets (ex: "woocommerce", "elementor")
   - Filtrez par type : Plugin, Thème, Meta, App

3. **Ajouter des projets** :
   - Cochez les projets à surveiller
   - Sélectionnez les locales pour chaque projet
   - Cliquez sur **Ajouter les projets sélectionnés**

#### Option B : Ajout Manuel

1. **Aller à la page Projets** :
   - Allez dans **Pierre** → **Projets**

2. **Ajouter un projet** :
   - Cliquez sur **Ajouter un Projet**
   - Remplissez le formulaire :
     - **Type** : Plugin, Thème, Meta, ou App
     - **Slug** : Le slug du projet (ex: `woocommerce` pour WooCommerce)
     - **Locale** : La locale à surveiller (ex: `fr`)
   - Cliquez sur **Ajouter**

### Étape 5 : Configurer les Webhooks par Locale (Optionnel)

Si vous souhaitez des canaux Slack différents par locale :

1. **Aller à la page Locales** :
   - Allez dans **Pierre** → **Locales**
   - Cliquez sur une locale pour voir ses détails

2. **Configurer le webhook** :
   - Dans la section **Webhook Slack**, configurez :
     - URL du webhook (différent du global)
     - Types de notifications
     - Seuils spécifiques
     - Mode (immédiat ou digest)
   - Activez **Remplacer le webhook global** si vous voulez que cette locale utilise uniquement ce webhook

### Étape 6 : Premier Test (Dry Run)

Avant de démarrer la surveillance en production, testez avec un "Dry Run" :

1. **Aller à la page Projets** :
   - Allez dans **Pierre** → **Projets**

2. **Lancer un Dry Run** :
   - Cliquez sur **Dry Run**
   - Pierre va simuler une surveillance sans envoyer de notifications
   - Vérifiez les logs pour voir si tout fonctionne correctement

3. **Vérifier les résultats** :
   - Consultez les statistiques affichées
   - Vérifiez qu'aucune erreur n'est présente

### Étape 7 : Démarrer la Surveillance

Une fois tout configuré et testé :

1. **Démarrer la surveillance** :
   - Dans **Pierre** → **Projets**, cliquez sur **Démarrer la Surveillance**
   - La surveillance va commencer immédiatement

2. **Vérifier le statut** :
   - Le statut passe à **Active**
   - La prochaine exécution est planifiée selon l'intervalle configuré (par défaut : 15 minutes)

3. **Recevoir les notifications** :
   - Les notifications Slack commenceront à arriver selon vos seuils configurés
   - Vérifiez votre canal Slack pour confirmer

## Configuration Avancée

### Assigner des Équipes

Pour organiser votre équipe de traduction :

1. Allez dans **Pierre** → **Équipes**
2. Assignez des utilisateurs WordPress à des projets avec des rôles :
   - **Locale Manager** : Gestionnaire de locale
   - **GTE** : General Translation Editor
   - **PTE** : Project Translation Editor
   - **Contributor** : Contributeur
   - **Validator** : Validateur

Voir [Gestion des Équipes](../team-management/) pour plus de détails.

### Tableau de Bord Public

Pierre génère automatiquement un tableau de bord public accessible à :

- `/pierre/` : Vue d'ensemble
- `/pierre/locale/{locale}/` : Vue par locale
- `/pierre/locale/{locale}/project/{type}/{slug}/` : Vue par projet

Ce tableau de bord est en lecture seule et peut être partagé avec les parties prenantes.

## Vérification Post-Installation

Après la configuration, vérifiez que tout fonctionne :

- ✅ Le plugin est activé sans erreurs
- ✅ Le webhook Slack est configuré et testé
- ✅ Au moins une locale est ajoutée
- ✅ Au moins un projet est surveillé
- ✅ Le Dry Run s'est exécuté sans erreur
- ✅ La surveillance est active
- ✅ Les notifications arrivent dans Slack

## Prochaines Étapes

Maintenant que Pierre est configuré :

1. **Consulter la documentation** :
   - [Interface d'Administration](../admin/) : Découvrir toutes les fonctionnalités admin
   - [Système de Surveillance](../surveillance/) : Comprendre comment fonctionne la surveillance
   - [Notifications](../notifications/) : Configurer les notifications en détail
   - [Gestion des Équipes](../team-management/) : Organiser votre équipe

2. **Personnaliser** :
   - Ajustez les seuils selon vos besoins
   - Configurez des webhooks par locale si nécessaire
   - Organisez vos équipes de traduction

3. **Surveiller** :
   - Consultez régulièrement le dashboard pour voir les statistiques
   - Vérifiez les rapports pour analyser les tendances
   - Utilisez le tableau de bord public pour partager avec votre équipe

## Dépannage

Si vous rencontrez des problèmes :

1. **Vérifier les prérequis** : WordPress 6.0+, PHP 8.3+
2. **Vérifier les permissions** : Vous devez avoir `manage_options`
3. **Vérifier les logs** : Activez `WP_DEBUG` et `PIERRE_DEBUG` pour voir les logs
4. **Consulter la FAQ** : Voir [Dépannage](../troubleshooting/) pour les problèmes courants

## Support

- **Documentation** : [Wiki GitHub](https://github.com/jaz-on/wp-pierre/wiki)
- **Issues** : [GitHub Issues](https://github.com/jaz-on/wp-pierre/issues)
- **Contact** : bonjour@jasonrouet.com

---

*Pierre says: Welcome aboard! I'm ready to help you monitor translations! 🪨*
