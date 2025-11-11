# Interface d'Administration

Vue d'ensemble complète de l'interface d'administration de WP-Pierre.

## Documentation

- [Templates](templates.md) - Documentation des templates admin
- [UI Components](ui-components.md) - Composants visuels et classes CSS

## Structure des Menus

L'interface admin est accessible via le menu **Pierre** dans l'administration WordPress.

### Menu Principal

- **Dashboard** (`pierre-dashboard`) : Vue d'ensemble et statistiques
- **Locales** (`pierre-locales`) : Gestion des locales surveillées
- **Projects** (`pierre-projects`) : Gestion des projets surveillés
- **Teams** (`pierre-teams`) : Gestion des équipes de traduction
- **Reports** (`pierre-reports`) : Rapports et statistiques
- **Settings** (`pierre-settings`) : Réglages du plugin

### Sous-Menus Settings

- **Global Webhook** : Configuration du webhook Slack global
- **Projects Discovery** : Découverte et ajout de projets
- **Locales Discovery** : Découverte et ajout de locales

## Pages Admin

### Dashboard

**URL** : `admin.php?page=pierre-dashboard`

**Capability** : `pierre_view_dashboard`

**Fonctionnalités** :
- Statistiques globales (projets, locales, traductions)
- Statut de la surveillance
- Statut du système de notifications
- Actions rapides

**Composants UI** :
- Cartes (`.pierre-card`) pour chaque section
- Grille adaptative (`.pierre-grid--cards`) pour les statistiques
- Tableaux WordPress (`.wp-list-table`) pour les assignations
- Badges de statut (`.pierre-status-ok`, `.pierre-status-ko`)

### Locales

**URL** : `admin.php?page=pierre-locales`

**Capability** : `pierre_view_dashboard`

**Fonctionnalités** :
- Liste des locales surveillées
- Ajout/suppression de locales
- Configuration webhook par locale
- Vue détaillée par locale

**Composants UI** :
- Grille de locales (`.pierre-locales-grid`)
- Cartes de locale (`.pierre-locale-card`)
- Badges de statut (`.pierre-badge.is-active`, `.pierre-badge.is-slack-direct`)
- Actions de locale (`.pierre-locale-actions`)

### Projects

**URL** : `admin.php?page=pierre-projects`

**Capability** : `pierre_manage_projects`

**Fonctionnalités** :
- Liste des projets surveillés
- Ajout/suppression de projets
- Démarrage/arrêt de la surveillance
- Dry run (test sans notifications)
- Gestion de la progression

**Composants UI** :
- Formulaire d'ajout (`.pierre-form-compact`) avec toggle
- Cartes pour le statut de surveillance
- Boutons d'action (`.button-primary`, `.pierre-button-danger`)
- Tableaux de projets (`.wp-list-table`)
- Indicateurs de progression

### Teams

**URL** : `admin.php?page=pierre-teams`

**Capability** : `pierre_view_teams`

**Fonctionnalités** :
- Liste des rôles et capabilities
- Assignation d'utilisateurs à des projets
- Gestion des équipes par locale

### Reports

**URL** : `admin.php?page=pierre-reports`

**Capability** : `pierre_view_reports`

**Fonctionnalités** :
- Génération de rapports
- Export de rapports (JSON, CSV)
- Planification de rapports automatiques

### Settings

**URL** : `admin.php?page=pierre-settings`

**Capability** : `pierre_manage_settings`

**Fonctionnalités** :
- Configuration globale
- Webhook global Slack
- Découverte de projets
- Découverte de locales
- Gestion du cache
- Réinitialisation des données

**Composants UI** :
- Onglets (`.pierre-tab-section`) pour organiser les sections
- Formulaires compacts (`.pierre-form-compact`) et larges (`.pierre-form-wide`)
- Groupes de formulaire (`.pierre-form-group`)
- Gestion d'erreurs (`.pierre-field-error`, `.pierre-error-text`)
- Boutons d'action avec validation

## Admin Bar Integration

Pierre ajoute des liens dans la barre d'admin WordPress :

- **Pierre Dashboard** : Accès rapide au dashboard
- **Surveillance Status** : Statut actuel de la surveillance
- **Quick Actions** : Actions rapides (démarrer/arrêter)

## Help Tabs

Chaque page admin dispose d'onglets d'aide contextuelle :

- **Overview** : Vue d'ensemble de la page
- **Usage** : Guide d'utilisation
- **Troubleshooting** : Dépannage

## Notices Admin

Pierre affiche des notices pour :

- Activation réussie
- Erreurs de configuration
- Avertissements de sécurité
- Notifications importantes

## Workflows Principaux

Voir [Workflows](../workflows/) pour les workflows détaillés.

---

*Pierre says: My admin interface helps you manage everything easily! 🪨*
