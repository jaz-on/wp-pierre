# Standards de Code - Plugin WP Pierre

Ce document décrit les conventions de code utilisées dans le plugin WP Pierre pour assurer la cohérence et la maintenabilité du codebase.

## Table des matières

1. [Syntaxe PHP](#syntaxe-php)
2. [Validation des options](#validation-des-options)
3. [Conventions de nonces AJAX](#conventions-de-nonces-ajax)
4. [Format des messages d'erreur](#format-des-messages-derreur)
5. [Conventions de sanitization](#conventions-de-sanitization)
6. [Gestion des mappings d'équipe](#gestion-des-mappings-déquipe)

## Syntaxe PHP

### Utilisation de `[]` vs `array()`

**Règle :** Toujours utiliser la notation courte `[]` pour les arrays (PHP 5.4+).

**Bon :**
```php
$default = [];
$items = get_option( 'pierre_items', [] );
$array = ['key' => 'value'];
```

**Mauvais :**
```php
$default = array();
$items = get_option( 'pierre_items', array() );
$array = array('key' => 'value');
```

**Raison :** La notation courte est plus moderne, plus concise et recommandée depuis PHP 5.4.

## Validation des options

### Pattern recommandé : Utiliser `is_array()` plutôt que cast

**Règle :** Toujours valider que les options retournées par `get_option()` sont des arrays avant utilisation.

**Bon :**
```php
$map = get_option( 'pierre_locale_managers', [] );
if ( ! is_array( $map ) ) {
    return [];
}
$managers = $map[$locale] ?? [];
if ( ! is_array( $managers ) ) {
    return [];
}
```

**Mauvais :**
```php
$map = (array) get_option( 'pierre_locale_managers', [] );
$managers = (array) ($map[$locale] ?? []);
```

**Raison :** Le cast `(array)` masque les erreurs. La validation avec `is_array()` est plus sûre et permet de détecter les problèmes de données.

### Helper : `OptionHelper::get_option_array()`

Pour les options qui doivent être des arrays, utiliser le helper :

```php
use Pierre\Helpers\OptionHelper;

$watched_projects = OptionHelper::get_option_array( 'pierre_watched_projects', [] );
```

**Avantages :**
- Validation automatique
- Retourne toujours un array
- Code plus lisible

## Conventions de nonces AJAX

### Types de nonces

Le plugin utilise deux types de nonces AJAX :

- **`pierre_admin_ajax`** : Pour les actions nécessitant les permissions admin
- **`pierre_ajax`** : Pour les actions publiques ou avec permissions réduites

### Pattern recommandé : Utiliser `validate_ajax_nonce()`

**Règle :** Toujours utiliser la méthode helper `validate_ajax_nonce()` pour vérifier les nonces.

**Bon :**
```php
// Nonce simple (admin uniquement)
if ( ! $this->validate_ajax_nonce( 'pierre_admin_ajax' ) ) {
    wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'wp-pierre' ) ) );
    return;
}

// Nonce avec fallback (compatibilité)
if ( ! $this->validate_ajax_nonce( 'pierre_ajax', true ) ) {
    wp_die( __( 'Invalid nonce.', 'wp-pierre' ) );
}
```

**Mauvais :**
```php
// Pattern double manuel
if ( ! check_ajax_referer( 'pierre_ajax', 'nonce', false ) ) {
    if ( ! check_ajax_referer( 'pierre_admin_ajax', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'wp-pierre' ) ) );
    }
}
```

**Raison :** Le helper standardise la vérification, supporte le fallback optionnel et améliore la lisibilité.

## Format des messages d'erreur

### Pattern recommandé : Utiliser `ErrorHelper::format_error_message()`

**Règle :** Tous les messages d'erreur utilisateur doivent utiliser le helper pour un format cohérent.

**Bon :**
```php
use Pierre\Helpers\ErrorHelper;

$error = new \WP_Error(
    'invalid_nonce',
    ErrorHelper::format_error_message( __( 'Invalid nonce! CSRF attack detected!', 'wp-pierre' ) )
);
```

**Mauvais :**
```php
$error = new \WP_Error(
    'invalid_nonce',
    __( 'Pierre says: Invalid nonce! CSRF attack detected!', 'wp-pierre' ) . ' 😢'
);
```

**Format standard :**
- Préfixe "Pierre says: " ajouté automatiquement
- Emoji 😢 ajouté automatiquement (configurable)
- Format cohérent dans tout le plugin

**Raison :** Centralise le formatage, facilite les modifications futures et assure la cohérence.

## Conventions de sanitization

### Pattern recommandé : `wp_unslash()` avant sanitization

**Règle :** Toujours utiliser `wp_unslash()` avant la sanitization pour les données POST/GET.

**Bon :**
```php
$value = sanitize_text_field( wp_unslash( $_POST['field'] ?? '' ) );
$key = sanitize_key( wp_unslash( $_GET['key'] ?? '' ) );
```

**Mauvais :**
```php
$value = sanitize_text_field( $_POST['field'] ?? '' );
$key = sanitize_key( $_GET['key'] ?? '' );
```

**Raison :** WordPress ajoute automatiquement des slashes aux données POST/GET. `wp_unslash()` les retire avant la sanitization.

### Helpers disponibles

#### `OptionHelper::sanitize_locale_code()`

Pour sanitizer et valider les codes de locale :

```php
use Pierre\Helpers\OptionHelper;

$locale = OptionHelper::sanitize_locale_code( wp_unslash( $_POST['locale'] ?? '' ) );
// Retourne "fr_FR" si valide, "" si invalide
```

#### `sanitize_key()` vs `sanitize_text_field()`

- **`sanitize_key()`** : Pour les clés, slugs, codes (alphanumériques, tirets, underscores uniquement)
- **`sanitize_text_field()`** : Pour les champs de texte (peut contenir espaces et caractères spéciaux)

**Référence :** Voir `docs/SANITIZATION.md` pour plus de détails.

## Gestion des mappings d'équipe

### Pattern recommandé : Utiliser `get_team_mapping()`

**Règle :** Toujours utiliser la méthode helper `get_team_mapping()` pour récupérer les mappings d'équipe (LM/GTE/PTE).

**Bon :**
```php
// Dans RoleManager
$managers = $this->get_team_mapping( 'lm', $locale_code );
$gte_list = $this->get_team_mapping( 'gte', $locale_code );
$pte_list = $this->get_team_mapping( 'pte', $locale_code, $project_key );
```

**Mauvais :**
```php
$map = get_option( 'pierre_locale_managers', [] );
$managers = (array) ($map[$locale_code] ?? []);
```

**Avantages :**
- Validation automatique avec `is_array()`
- Gestion cohérente des trois types (LM/GTE/PTE)
- Support du paramètre `$project_key` pour PTE
- Code plus lisible et maintenable

**Types supportés :**
- `'lm'` : Locale Managers
- `'gte'` : General Translation Editors
- `'pte'` : Project Translation Editors (nécessite `$project_key`)

## Exemples de patterns complets

### Pattern 1 : Endpoint AJAX avec validation

```php
public function ajax_example(): void {
    // Vérifier le nonce
    if ( ! $this->validate_ajax_nonce( 'pierre_admin_ajax' ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'wp-pierre' ) ) );
        return;
    }
    
    // Vérifier les permissions
    if ( ! current_user_can( 'pierre_manage_settings' ) ) {
        wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wp-pierre' ) ) );
        return;
    }
    
    // Sanitizer les données
    $locale = OptionHelper::sanitize_locale_code( wp_unslash( $_POST['locale'] ?? '' ) );
    if ( empty( $locale ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid locale.', 'wp-pierre' ) ) );
        return;
    }
    
    // Récupérer les données
    $managers = OptionHelper::get_option_array( 'pierre_locale_managers', [] );
    
    // Traitement...
}
```

### Pattern 2 : Validation avec messages d'erreur

```php
use Pierre\Helpers\ErrorHelper;

if ( ! $valid ) {
    $error = new \WP_Error(
        'validation_failed',
        ErrorHelper::format_error_message( __( 'Validation failed!', 'wp-pierre' ) )
    );
    return $error;
}
```

### Pattern 3 : Récupération de mapping d'équipe

```php
// Dans RoleManager
$managers = $this->get_team_mapping( 'lm', $locale_code );
if ( in_array( $user_id, $managers, true ) ) {
    // Utilisateur est Locale Manager
}
```

## Résumé des helpers disponibles

| Helper | Classe | Usage |
|--------|--------|-------|
| `format_error_message()` | `ErrorHelper` | Formater les messages d'erreur |
| `get_option_array()` | `OptionHelper` | Récupérer une option array avec validation |
| `sanitize_locale_code()` | `OptionHelper` | Sanitizer et valider un code de locale |
| `validate_ajax_nonce()` | `AdminController` | Vérifier un nonce AJAX (méthode privée) |
| `get_team_mapping()` | `RoleManager` | Récupérer un mapping d'équipe (méthode privée) |

## Références

- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- [PHP The Right Way](https://phptherightway.com/)
- [Documentation de sanitization](./docs/SANITIZATION.md)

