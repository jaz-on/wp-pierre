# Conventions de Sanitization

Ce document décrit les conventions de sanitization utilisées dans le plugin WP Pierre.

## Vue d'ensemble

La sanitization est le processus de nettoyage et de validation des données utilisateur avant leur stockage ou leur utilisation. WordPress fournit plusieurs fonctions de sanitization, chacune adaptée à un type de données spécifique.

## Fonctions de sanitization WordPress

### `sanitize_key()`

**Quand l'utiliser :**
- Pour les clés de base de données, noms d'options, slugs
- Pour les codes de locale (utiliser `OptionHelper::sanitize_locale_code()` pour les locales)
- Pour les identifiants de type/slug de projet
- Pour les valeurs qui doivent contenir uniquement des caractères alphanumériques, tirets et underscores

**Exemples :**
```php
$option_name = sanitize_key( $_POST['option_name'] );
$locale_code = sanitize_key( $_POST['locale'] );
$project_slug = sanitize_key( $_POST['slug'] );
```

**Note :** Pour les codes de locale, préférer `OptionHelper::sanitize_locale_code()` qui valide aussi le format (fr, fr_FR).

### `sanitize_text_field()`

**Quand l'utiliser :**
- Pour les champs de texte simples (noms, titres, descriptions)
- Pour les valeurs de chaîne qui peuvent contenir des espaces et caractères spéciaux
- Avant d'afficher du texte dans l'interface utilisateur

**Exemples :**
```php
$project_name = sanitize_text_field( $_POST['project_name'] );
$description = sanitize_text_field( $_POST['description'] );
```

### `sanitize_textarea_field()`

**Quand l'utiliser :**
- Pour les champs de texte multilignes (textarea)
- Pour préserver les sauts de ligne

**Exemples :**
```php
$notes = sanitize_textarea_field( $_POST['notes'] );
```

### `sanitize_email()`

**Quand l'utiliser :**
- Pour les adresses email
- Valide le format d'email en plus de la sanitization

**Exemples :**
```php
$email = sanitize_email( $_POST['email'] );
```

### `esc_url_raw()`

**Quand l'utiliser :**
- Pour les URLs qui seront stockées en base de données
- Pour les webhooks, liens externes

**Exemples :**
```php
$webhook_url = esc_url_raw( $_POST['webhook_url'] );
```

### `absint()`

**Quand l'utiliser :**
- Pour convertir une valeur en entier positif
- Pour les IDs, compteurs, valeurs numériques non négatives

**Exemples :**
```php
$user_id = absint( $_POST['user_id'] );
$interval = absint( $_POST['interval'] );
```

## Utilisation de `wp_unslash()`

**Quand l'utiliser :**
- Toujours avant la sanitization pour les données provenant de `$_POST`, `$_GET`, `$_REQUEST`
- WordPress ajoute automatiquement des slashes aux données POST/GET
- `wp_unslash()` retire ces slashes avant la sanitization

**Pattern recommandé :**
```php
$value = sanitize_text_field( wp_unslash( $_POST['field'] ?? '' ) );
$key = sanitize_key( wp_unslash( $_GET['key'] ?? '' ) );
```

**Exemples :**
```php
// Correct
$locale = sanitize_key( wp_unslash( $_POST['locale'] ?? '' ) );

// Incorrect (double sanitization possible)
$locale = wp_unslash( sanitize_key( $_POST['locale'] ?? '' ) );
```

## Helpers du plugin

### `OptionHelper::sanitize_locale_code()`

**Quand l'utiliser :**
- Pour sanitizer et valider les codes de locale
- Valide le format : "fr" ou "fr_FR" (2 lettres minuscules, optionnel underscore et 2 lettres majuscules)
- Convertit automatiquement "fr_fr" en "fr_FR"

**Exemples :**
```php
use Pierre\Helpers\OptionHelper;

$locale = OptionHelper::sanitize_locale_code( $_POST['locale'] ?? '' );
// Retourne "fr_FR" si valide, "" si invalide
```

### `OptionHelper::get_option_array()`

**Quand l'utiliser :**
- Pour récupérer des options qui doivent être des arrays
- Valide automatiquement que la valeur est un array
- Retourne un array vide par défaut si invalide

**Exemples :**
```php
use Pierre\Helpers\OptionHelper;

$watched_projects = OptionHelper::get_option_array( 'pierre_watched_projects', [] );
$locale_managers = OptionHelper::get_option_array( 'pierre_locale_managers', [] );
```

## Patterns recommandés

### Pattern 1 : Données POST/GET avec valeur par défaut

```php
$value = sanitize_text_field( wp_unslash( $_POST['field'] ?? '' ) );
```

### Pattern 2 : Clé/slug avec validation

```php
$key = sanitize_key( wp_unslash( $_GET['key'] ?? '' ) );
if ( empty( $key ) ) {
    // Gérer l'erreur
}
```

### Pattern 3 : Locale avec helper

```php
use Pierre\Helpers\OptionHelper;

$locale = OptionHelper::sanitize_locale_code( wp_unslash( $_POST['locale'] ?? '' ) );
if ( empty( $locale ) ) {
    // Gérer l'erreur
}
```

### Pattern 4 : Option array avec helper

```php
use Pierre\Helpers\OptionHelper;

$projects = OptionHelper::get_option_array( 'pierre_watched_projects', [] );
if ( empty( $projects ) ) {
    // Aucun projet surveillé
}
```

## Sécurité

1. **Toujours sanitizer** : Ne jamais faire confiance aux données utilisateur
2. **Sanitizer avant validation** : Sanitizer d'abord, puis valider la logique métier
3. **Échapper à l'affichage** : Utiliser `esc_html()`, `esc_attr()`, `esc_url()` pour l'affichage
4. **Préparer les requêtes SQL** : Utiliser `$wpdb->prepare()` pour les requêtes SQL

## Références

- [WordPress Codex: Data Validation](https://codex.wordpress.org/Data_Validation)
- [WordPress Codex: Sanitizing](https://codex.wordpress.org/Validating_Sanitizing_and_Escaping)
- [WordPress Developer Handbook: Sanitization](https://developer.wordpress.org/themes/theme-security/data-sanitization-escaping/)

