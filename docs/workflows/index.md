# Workflows

Documentation des workflows complets du plugin WP-Pierre.

## Workflow de Surveillance

### 1. Déclenchement

La surveillance peut être déclenchée de deux manières :

**Automatique** :
1. Cron WordPress déclenche `pierre_surveillance_check`
2. `CronManager::run_surveillance_check()` est appelé
3. Vérification que la surveillance est active

**Manuelle** :
1. Admin clique sur "Démarrer la Surveillance" ou "Surveillance Maintenant"
2. Endpoint AJAX `pierre_start_surveillance` ou `pierre_run_surveillance_now`
3. `ProjectWatcher::start_surveillance()` est appelé

### 2. Récupération des Projets

1. `ProjectWatcher` récupère la liste des projets surveillés depuis les settings
2. Filtrage par locale si nécessaire
3. Groupement par batch pour traitement

### 3. Scraping des Données

Pour chaque projet :

1. `TranslationScraper::scrape_typed_project()` est appelé
2. Vérification du cache (transient `pierre_project_{type}_{slug}_{locale}`)
3. Si cache expiré ou absent :
   - Détection du segment API (`wp`, `wp-plugins`, `wp-themes`, `meta`, `apps`)
   - Requête HTTP vers translate.wordpress.org
   - Gestion du backoff si rate limit (429)
   - Retry automatique sur erreurs 5xx
4. Mise en cache des données (1 heure)
5. Application du filtre `pierre_translation_data`

### 4. Comparaison et Détection

1. Récupération des données précédentes (transient `pierre_project_{type}_{slug}_{locale}_prev`)
2. Comparaison avec les nouvelles données
3. Détection des changements :
   - Nouveaux strings (`waiting` augmenté)
   - Mise à jour de complétion (`percent_translated` changé)
   - Besoin d'attention (`waiting + fuzzy > 0`)
   - Jalons atteints (50%, 75%, 100%)

### 5. Notification

Si changement détecté et seuils atteints :

1. `NotificationService` construit le message via `MessageBuilder`
2. Application du filtre `pierre_notification_message`
3. Envoi via `SlackNotifier` :
   - Mode immédiat : Envoi direct
   - Mode digest : Ajout à la file d'attente (`pierre_digest_queue_{locale}`)
4. Logging du résultat

### 6. Mise à Jour

1. Sauvegarde des nouvelles données comme "précédentes"
2. Mise à jour des statistiques
3. Mise à jour de la progression (transient `pierre_surv_progress`)

## Workflow d'Assignation

### 1. Sélection

1. Admin va dans **Pierre → Teams**
2. Sélectionne un utilisateur et un projet
3. Choisit un rôle (Locale Manager, GTE, PTE, Contributor, Validator)

### 2. Validation

1. `UserProjectLink` valide l'assignation
2. Vérification des permissions (`pierre_manage_teams`)
3. Vérification de la validité du projet et de la locale

### 3. Enregistrement

1. `TeamRepository::assign_user_to_project()` est appelé
2. Insertion dans la table `pierre_user_projects`
3. Enregistrement de l'historique (assigned_by, assigned_at)

### 4. Notification (Optionnel)

1. Notification à l'utilisateur assigné (si configuré)
2. Log de l'assignation

## Workflow de Configuration

### Configuration Initiale

1. **Activation du plugin** :
   - Création de la table `pierre_user_projects`
   - Initialisation des capabilities
   - Planification des cron

2. **Configuration du webhook global** :
   - Admin → Settings → Global Webhook
   - Saisie de l'URL du webhook Slack
   - Configuration des types et seuils
   - Test de la notification

3. **Ajout de locales** :
   - Admin → Settings → Locales Discovery
   - Récupération de la liste
   - Sélection et ajout

4. **Ajout de projets** :
   - Admin → Projects
   - Ajout manuel ou via catalogue
   - Configuration par locale

5. **Démarrage** :
   - Dry run pour test
   - Démarrage de la surveillance

### Configuration Avancée

1. **Webhooks par locale** :
   - Admin → Locales → [Locale]
   - Configuration webhook spécifique
   - Activation du remplacement du webhook global

2. **Organisation des équipes** :
   - Admin → Teams
   - Assignation des membres
   - Définition des rôles

## Workflow de Découverte

### 1. Accès au Catalogue

1. Admin → Settings → Projects Discovery
2. Clic sur "Parcourir le Catalogue"

### 2. Recherche et Filtrage

1. Recherche par nom, tags, type
2. Filtrage par type (plugin, theme, meta, app)
3. Tri et pagination

### 3. Sélection

1. Coche des projets à ajouter
2. Sélection des locales pour chaque projet
3. Prévisualisation (optionnel)

### 4. Ajout

1. Endpoint AJAX `pierre_add_from_catalog`
2. Pour chaque projet :
   - Validation
   - Ajout via `ProjectWatcher::watch_project()`
   - Marquage comme "connu" dans le catalogue
3. Confirmation et statistiques

## Workflow de Digest

### Mode Interval

1. **Planification** :
   - Cron déclenche `pierre_run_digest` toutes les X minutes
   - Vérification des files d'attente par locale

2. **Regroupement** :
   - Récupération des notifications en attente (`pierre_digest_queue_{locale}`)
   - Application du filtre `pierre_digest_max_projects` (défaut: 20)
   - Regroupement par locale

3. **Envoi** :
   - Construction du message digest
   - Application du filtre `pierre_digest_chunk_size` (défaut: 20)
   - Envoi via `SlackNotifier`
   - Vidage de la file d'attente

### Mode Fixed Time

1. **Planification** :
   - Cron déclenche `pierre_run_digest` à l'heure configurée
   - Fenêtre de 15 minutes pour l'envoi

2. **Regroupement** : Identique au mode interval

3. **Envoi** : Identique au mode interval

## Workflow de Nettoyage

### Déclenchement

1. Cron quotidien déclenche `pierre_cleanup_old_data`
2. `CronManager::run_cleanup_task()` est appelé

### Actions

1. **Transients expirés** :
   - Suppression des transients `pierre_*` expirés depuis > 7 jours

2. **Erreurs anciennes** :
   - Nettoyage des erreurs de surveillance > 24 heures
   - Mise à jour de `pierre_last_surv_errors`

3. **Cache** :
   - Invalidation du cache expiré
   - Nettoyage des options temporaires

## Workflow de Rapport

### Génération

1. Admin → Reports
2. Sélection du type de rapport
3. Configuration des paramètres (période, locales, projets)
4. Génération via endpoint AJAX

### Export

1. Choix du format (JSON, CSV)
2. Téléchargement du fichier
3. Ou envoi par email (si configuré)

### Planification

1. Configuration de la planification
2. Enregistrement dans les settings
3. Cron automatique pour génération et envoi

---

*Pierre says: Understanding these workflows helps you use me effectively! 🪨*

