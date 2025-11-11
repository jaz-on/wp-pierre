# Assets

Documentation des fichiers CSS et JavaScript du plugin WP-Pierre.

## CSS

### `assets/css/admin.css`

Styles pour l'interface d'administration.

**Classes principales** :
- `.pierre-card` : Carte de contenu
- `.pierre-grid` : Grille de mise en page
- `.pierre-stat-box` : Boîte de statistique
- `.pierre-status-info` : Informations de statut

**Enqueue** : Automatique sur les pages admin de Pierre

### `assets/css/public.css`

Styles pour le dashboard public.

**Classes principales** :
- `.pierre-dashboard` : Container principal
- `.pierre-project-card` : Carte de projet
- `.pierre-locale-header` : En-tête de locale

**Enqueue** : Automatique sur les pages publiques `/pierre/*`

## JavaScript

### `assets/js/admin.js`

Scripts pour l'interface admin.

**Localisation** : `pierreAdminL10n` (objet JavaScript)

**Propriétés disponibles** :
- `ajaxUrl` : URL admin-ajax.php
- `nonce` : Nonce pour requêtes admin
- `nonceAjax` : Nonce pour requêtes AJAX
- Messages de traduction (saving, testing, etc.)

**Fonctionnalités** :
- Gestion des formulaires AJAX
- Tests de notifications
- Gestion de la progression
- Actions de surveillance

**Enqueue** : Automatique sur les pages admin de Pierre

### `assets/js/public.js`

Scripts pour le dashboard public.

**Fonctionnalités** :
- Chargement dynamique de données
- Interactions utilisateur

**Enqueue** : Automatique sur les pages publiques `/pierre/*`

---

*Pierre says: My assets make the interface beautiful and functional! 🪨*

