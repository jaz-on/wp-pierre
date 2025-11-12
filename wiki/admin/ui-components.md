# Composants UI de l'Interface Admin

Documentation des composants visuels et des classes CSS utilisés dans l'interface d'administration de WP-Pierre.

## Vue d'Ensemble

L'interface admin utilise un système de design minimaliste et natif, aligné avec WordPress 6.5+. Tous les composants sont construits avec des classes CSS réutilisables et des tokens de design.

## Tokens de Design

### Espacements

Les espacements suivent une échelle basée sur 8px :

```css
--pierre-space-8: 8px;
--pierre-space-12: 12px;
--pierre-space-16: 16px;
--pierre-space-24: 24px;
```

### Bordures et Rayons

```css
--pierre-border: 1px solid #dcdcde;
--pierre-radius: 4px;
```

## Composants Principaux

### Cards (Cartes)

Les cartes sont utilisées pour regrouper du contenu visuellement.

**Classe** : `.pierre-card`

**Structure** :
```html
<div class="pierre-card">
    <h2>Titre de la carte</h2>
    <p>Contenu de la carte</p>
</div>
```

**Caractéristiques** :
- Fond blanc
- Bordure et ombre légère
- Padding de 16px
- Marge verticale de 16px
- Titres `h2` et `h3` stylisés automatiquement

**Exemple d'utilisation** :
- Dashboard : Statistiques, statut de surveillance
- Pages de configuration : Regroupement de sections
- Listes : Conteneurs pour tableaux et données

### Grid (Grille)

Système de grille flexible pour organiser le contenu.

**Classes** :
- `.pierre-grid` : Grille de base
- `.pierre-grid--cards` : Grille adaptative pour cartes (min 320px)
- `.pierre-grid--single` : Grille à une colonne

**Exemple** :
```html
<div class="pierre-grid pierre-grid--cards">
    <div class="pierre-card">Carte 1</div>
    <div class="pierre-card">Carte 2</div>
    <div class="pierre-card">Carte 3</div>
</div>
```

### Tabs (Onglets)

Système d'onglets pour organiser le contenu en sections.

**Classes** :
- `.pierre-tab-section` : Section d'onglet (masquée par défaut)
- `.pierre-tab-section.is-active` : Section active (affichée)

**Exemple** :
```html
<div class="pierre-tab-section is-active">
    <h3>Contenu de l'onglet actif</h3>
</div>
<div class="pierre-tab-section">
    <h3>Contenu de l'onglet inactif</h3>
</div>
```

### Boutons

**Bouton standard** : Utilise les classes WordPress natives `.button`, `.button-primary`

**Bouton danger** : `.pierre-button-danger.button`

```html
<button class="button button-primary">Action principale</button>
<button class="button pierre-button-danger">Action destructive</button>
```

### Badges

Indicateurs visuels pour les statuts.

**Classes** :
- `.pierre-badge` : Badge de base
- `.pierre-badge.is-active` : Badge actif (vert)
- `.pierre-badge.is-inactive` : Badge inactif (jaune)
- `.pierre-badge.is-slack-direct` : Badge Slack direct (bleu)

**Exemple** :
```html
<span class="pierre-badge is-active">Actif</span>
<span class="pierre-badge is-inactive">Inactif</span>
```

### Modals (Fenêtres modales)

Fenêtres modales pour les dialogues et formulaires.

**Classes** :
- `.pierre-overlay` : Overlay sombre en arrière-plan
- `.pierre-modal` : Fenêtre modale centrée

**Structure** :
```html
<div class="pierre-overlay">
    <div class="pierre-modal">
        <h2>Titre du modal</h2>
        <p>Contenu du modal</p>
        <button class="button">Fermer</button>
    </div>
</div>
```

### Formulaires

**Formulaire compact** : `.pierre-form-compact` (max-width: 480px)

**Formulaire large** : `.pierre-form-wide` (largeur complète)

**Groupe de formulaire** : `.pierre-form-group`

**Exemple** :
```html
<form class="pierre-form-compact">
    <div class="pierre-form-group">
        <label for="field">Label</label>
        <input type="text" id="field" class="regular-text">
        <p class="pierre-help">Texte d'aide</p>
    </div>
    <div class="pierre-form-actions">
        <button type="submit" class="button button-primary">Sauvegarder</button>
    </div>
</form>
```

### Statuts

Indicateurs de statut colorés.

**Classes** :
- `.pierre-status-ok` : Statut OK (vert)
- `.pierre-status-ko` : Statut erreur (rouge)

**Exemple** :
```html
<span class="pierre-status-ok">Surveillance active</span>
<span class="pierre-status-ko">Erreur de connexion</span>
```

### Sections

**Section standard** : `.pierre-section`

**Section avec en-tête** : `.pierre-section--header`

**Section large** : `.pierre-section--body-wide`

### Locales Discovery

Composants spécifiques pour la découverte de locales.

**Classes** :
- `.pierre-locales-grid` : Grille pour les cartes de locales
- `.pierre-locale-card` : Carte de locale individuelle
- `.pierre-locale-card.is-disabled` : Locale désactivée
- `.pierre-locale-actions` : Actions pour une locale

## Utilitaires

### Espacements

- `.pierre-mt-8`, `.pierre-mt-16` : Marges supérieures
- `.pierre-mb-8`, `.pierre-mb-16` : Marges inférieures
- `.pierre-ml-8` : Marge gauche

### Flexbox

- `.pierre-row` : Conteneur flex horizontal
- `.pierre-flex-spacer` : Espaceur flexible

### Autres

- `.pierre-checkbox-group` : Groupe de checkboxes
- `.pierre-list` : Liste avec puces
- `.pierre-preview-box` : Boîte de prévisualisation
- `.pierre-va-middle` : Alignement vertical au centre
- `.pierre-visually-hidden` : Masqué visuellement mais accessible (screen readers)
- `.pierre-fieldset` : Fieldset stylisé
- `.pierre-danger` : Texte de danger (rouge)

## Gestion des Erreurs

**Classes pour les erreurs de formulaire** :
- `.pierre-form-group--error` : Groupe avec erreur
- `.pierre-field-error` : Champ en erreur
- `.pierre-field-error-message` : Message d'erreur
- `.pierre-error-text` : Texte d'erreur

**Exemple** :
```html
<div class="pierre-form-group pierre-form-group--error">
    <label for="field">Champ</label>
    <input type="text" id="field" class="regular-text pierre-field-error">
    <p class="pierre-field-error-message">
        <span class="pierre-error-text">Ce champ est requis</span>
    </p>
</div>
```

## Structure des Pages

### Page Standard

```html
<div class="wrap">
    <h1>Titre de la page</h1>
    
    <div class="pierre-card">
        <h2>Section</h2>
        <!-- Contenu -->
    </div>
    
    <div class="pierre-grid pierre-grid--cards">
        <div class="pierre-card">Carte 1</div>
        <div class="pierre-card">Carte 2</div>
    </div>
</div>
```

### Page avec Formulaire

```html
<div class="wrap">
    <h1>Titre de la page</h1>
    
    <form class="pierre-form-compact">
        <div class="pierre-form-group">
            <!-- Champs du formulaire -->
        </div>
        <div class="pierre-form-actions">
            <button type="submit" class="button button-primary">Sauvegarder</button>
        </div>
    </form>
</div>
```

## Bonnes Pratiques

1. **Utiliser les classes existantes** : Préférer les classes `.pierre-*` plutôt que d'ajouter du CSS personnalisé
2. **Respecter la hiérarchie** : Utiliser `.pierre-card` pour regrouper le contenu
3. **Cohérence visuelle** : Suivre les patterns existants pour les formulaires et listes
4. **Accessibilité** : Utiliser `.pierre-visually-hidden` pour le contenu accessible mais masqué
5. **Responsive** : Les grilles s'adaptent automatiquement grâce à `auto-fit` et `minmax`

## Personnalisation

Pour personnaliser l'apparence, vous pouvez :

1. **Surcharger les templates** : Créer des templates dans votre thème
2. **Ajouter du CSS personnalisé** : Utiliser le hook `pierre_admin_styles` pour ajouter du CSS
3. **Modifier les tokens** : Redéfinir les variables CSS dans votre CSS personnalisé

**Exemple de personnalisation CSS** :
```css
/* Dans votre thème ou plugin personnalisé */
:root {
    --pierre-space-16: 20px; /* Augmenter l'espacement */
    --pierre-radius: 8px; /* Bordures plus arrondies */
}

.pierre-card {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1); /* Ombre plus prononcée */
}
```

---

*Pierre says: My UI components make the admin interface clean and consistent! 🪨*

