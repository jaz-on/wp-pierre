# Cas d'Usage

Scénarios d'utilisation typiques du plugin WP-Pierre.

## Configuration Initiale Complète

**Objectif** : Configurer Pierre pour la première fois pour surveiller les traductions françaises de plusieurs plugins.

**Étapes** :

1. Activer le plugin
2. Configurer le webhook Slack global :
   - Créer un webhook dans Slack
   - Admin → Pierre → Settings → Global Webhook
   - Coller l'URL, configurer types et seuils
   - Tester la notification
3. Ajouter la locale française :
   - Admin → Settings → Locales Discovery
   - Récupérer les locales, cocher "fr"
   - Ajouter
4. Ajouter des projets :
   - Admin → Projects
   - Ajouter manuellement ou via catalogue
   - Exemples : WooCommerce, Elementor, Yoast SEO
5. Tester avec un dry run
6. Démarrer la surveillance

**Résultat** : Pierre surveille les projets et envoie des notifications Slack quand des nouveaux strings apparaissent.

## Ajout d'un Nouveau Projet

**Objectif** : Ajouter un nouveau plugin à surveiller.

**Étapes** :

1. Admin → Projects
2. Cliquer sur "Ajouter un Projet"
3. Remplir :
   - Type : Plugin
   - Slug : `nouveau-plugin`
   - Locale : `fr`
4. Cliquer sur "Ajouter"

**Résultat** : Le projet est ajouté et sera surveillé lors de la prochaine exécution.

## Configuration d'un Webhook par Locale

**Objectif** : Avoir un canal Slack différent pour chaque locale.

**Étapes** :

1. Admin → Locales
2. Cliquer sur une locale (ex: "fr")
3. Dans la section "Webhook Slack" :
   - Coller l'URL du webhook pour cette locale
   - Configurer les types et seuils spécifiques
   - Cocher "Remplacer le webhook global"
4. Sauvegarder

**Résultat** : Les notifications pour cette locale iront dans le canal spécifique.

## Assignation d'un Traducteur

**Objectif** : Assigner un utilisateur WordPress à un projet avec un rôle.

**Étapes** :

1. Admin → Teams
2. Rechercher l'utilisateur
3. Sélectionner le projet et la locale
4. Choisir le rôle (ex: "PTE" pour Project Translation Editor)
5. Assigner

**Résultat** : L'utilisateur est assigné et peut voir ses projets assignés.

## Test d'une Notification

**Objectif** : Vérifier que les notifications Slack fonctionnent.

**Étapes** :

1. Admin → Settings → Global Webhook
2. Cliquer sur "Tester la notification"
3. Vérifier dans Slack que le message arrive

**Alternative** : Admin → Projects → "Test Notification"

**Résultat** : Un message de test est envoyé dans Slack.

## Exécution d'un Dry Run

**Objectif** : Tester la surveillance sans envoyer de notifications.

**Étapes** :

1. Admin → Projects
2. Cliquer sur "Dry Run"
3. Attendre la fin de l'exécution
4. Consulter les résultats et statistiques

**Résultat** : Simulation complète de la surveillance avec statistiques, sans notifications.

## Consultation du Dashboard Public

**Objectif** : Partager l'état des traductions avec des parties prenantes.

**Étapes** :

1. Accéder à `/pierre/` sur le site
2. Naviguer vers une locale : `/pierre/locale/fr/`
3. Voir les détails d'un projet : `/pierre/locale/fr/project/plugin/woocommerce/`

**Résultat** : Tableau de bord public en lecture seule accessible sans authentification.

## Gestion d'une Équipe de Traduction

**Objectif** : Organiser une équipe avec différents rôles.

**Étapes** :

1. Admin → Teams
2. Assigner les Locale Managers pour chaque locale
3. Assigner les GTE (General Translation Editors)
4. Assigner les PTE (Project Translation Editors) par projet
5. Assigner les Contributors et Validators

**Résultat** : Équipe organisée avec permissions appropriées.

## Export de Rapports

**Objectif** : Générer un rapport pour analyse.

**Étapes** :

1. Admin → Reports
2. Sélectionner le type de rapport
3. Configurer les paramètres (période, locales, projets)
4. Cliquer sur "Générer"
5. Exporter en JSON ou CSV

**Résultat** : Fichier téléchargeable avec les données de traduction.

## Découverte et Ajout en Masse

**Objectif** : Ajouter plusieurs projets depuis le catalogue.

**Étapes** :

1. Admin → Settings → Projects Discovery
2. Parcourir le catalogue
3. Rechercher et filtrer les projets
4. Cocher plusieurs projets
5. Sélectionner les locales
6. Prévisualiser
7. Ajouter en masse

**Résultat** : Plusieurs projets ajoutés en une seule opération.

## Configuration d'un Digest

**Objectif** : Recevoir des notifications regroupées plutôt qu'immédiates.

**Étapes** :

1. Admin → Settings → Global Webhook
2. Choisir le mode "Digest"
3. Configurer :
   - Type : Interval (toutes les X minutes) ou Fixed Time (heure fixe)
   - Interval : 60 minutes (exemple)
   - Ou heure fixe : 09:00
4. Sauvegarder

**Résultat** : Les notifications sont regroupées et envoyées selon la configuration.

---

*Pierre says: These use cases show you how to get the most out of me! 🪨*

