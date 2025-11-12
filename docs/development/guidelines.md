# Development Guidelines

Standards de code, bonnes pratiques et guide de contribution.

## Standards de Code

### WordPress Coding Standards

Le plugin suit les [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).

**Vérification** :
```bash
composer install
vendor/bin/phpcs --standard=WordPress wp-pierre.php src/
```

### PSR-4 Autoloading

Structure des namespaces :
```
Pierre\                    → src/Pierre/
Pierre\Admin\              → src/Pierre/Admin/
Pierre\Surveillance\       → src/Pierre/Surveillance/
Pierre\Notifications\      → src/Pierre/Notifications/
Pierre\Teams\              → src/Pierre/Teams/
```

### PHP 8.3+ Features

Le plugin utilise les fonctionnalités modernes de PHP :

- **Typed properties** : `private string $name;`
- **Union types** : `string|false`, `array|\WP_Error`
- **Return types** : Toutes les méthodes ont des types de retour
- **Match expressions** : Pour les switch simplifiés
- **Named arguments** : Quand approprié

## Architecture

### Principes

- **KISS** : Keep It Simple, Stupid
- **DRY** : Don't Repeat Yourself
- **Separation of Concerns** : Chaque classe a une responsabilité unique
- **Dependency Injection** : Utilisation d'un Container simple

### Structure des Classes

**Template** :
```php
<?php
namespace Pierre\Namespace;

/**
 * Class description
 * 
 * @package Pierre
 * @since 1.0.0
 */
class ClassName {
    /**
     * Property description
     * 
     * @var type
     */
    private type $property;
    
    /**
     * Method description
     * 
     * @since 1.0.0
     * @param type $param Parameter description
     * @return type Return description
     */
    public function method(type $param): type {
        // Implementation
    }
}
```

## Sécurité

### Sanitization

- **Entrées utilisateur** : Toujours sanitizer avec `sanitize_text_field()`, `sanitize_email()`, etc.
- **Sorties** : Toujours échapper avec `esc_html()`, `esc_attr()`, `esc_url()`, etc.
- **SQL** : Toujours utiliser `$wpdb->prepare()` ou `esc_sql()` pour les identifiants

Voir [Conventions de Sanitization](sanitization.md) pour les détails complets.

### Validation

- **Nonces** : Toutes les actions AJAX nécessitent un nonce
- **Capabilities** : Vérifier `current_user_can()` avant chaque action
- **URLs** : Valider avec `wp_http_validate_url()` ou `wp_safe_remote_get()`

### Chiffrement

- Utiliser `defuse/php-encryption` pour les données sensibles
- Ne jamais stocker de données sensibles en clair
- Utiliser `wp_salt()` pour les clés de chiffrement

## Tests

### Structure

```
tests/
├── bootstrap.php          # Configuration PHPUnit
├── Unit/                  # Tests unitaires
└── Integration/           # Tests d'intégration
```

### Exécution

```bash
# Tous les tests
composer test

# Avec couverture
composer test-coverage
```

### Écriture de Tests

**Exemple** :
```php
<?php
namespace Pierre\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pierre\Teams\RoleManager;

class RoleManagerTest extends TestCase {
    public function test_add_capabilities(): void {
        $manager = new RoleManager();
        $manager->add_capabilities();
        
        $admin = get_role('administrator');
        $this->assertTrue($admin->has_cap('pierre_view_dashboard'));
    }
}
```

## Documentation

### PHPDoc

Toutes les méthodes doivent avoir une documentation PHPDoc complète :

```php
/**
 * Method description
 * 
 * @since 1.0.0
 * @param string $param Parameter description
 * @return array|WP_Error Return description
 * @throws Exception When something goes wrong
 */
```

### Commentaires Inline

- Utiliser des commentaires pour expliquer le "pourquoi", pas le "quoi"
- Style Pierre : `// Pierre does something! 🪨`
- Éviter les commentaires redondants

### Documentation Technique

- Maintenir la documentation dans `/docs`
- Mettre à jour lors des changements majeurs
- Suivre la structure existante

## Gestion des Erreurs

### WP_Error

Utiliser `WP_Error` pour les erreurs récupérables :

```php
if ($error) {
    return new \WP_Error(
        'error_code',
        __('Error message', 'wp-pierre'),
        ['context' => 'data']
    );
}
```

### Exceptions

Utiliser les exceptions pour les erreurs critiques :

```php
try {
    // Risky operation
} catch (\Exception $e) {
    error_log('Error: ' . $e->getMessage());
    return false;
}
```

### Logging

Utiliser le système de logging centralisé :

```php
do_action('wp_pierre_debug', 'Message', [
    'source' => 'ClassName',
    'context' => 'data'
]);
```

## Performance

### Cache

- Utiliser les transients WordPress pour le cache
- Vérifier `wp_using_ext_object_cache()` pour l'object cache
- Invalider le cache lors des mises à jour

### Requêtes

- Optimiser les requêtes SQL avec des index appropriés
- Utiliser `get_transient()` avec fallback pour éviter les appels multiples
- Traiter par lots pour les opérations en masse

### Mémoire

- Libérer les ressources après utilisation
- Éviter les boucles infinies
- Utiliser des générateurs pour les grandes listes

## Version Control

### Commits

- Messages de commit clairs et descriptifs
- Format : `type: description` (ex: `feat: add new capability`)
- Types : `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

### Branches

- `main` : Branche principale (stable)
- `develop` : Développement actif
- `feature/*` : Nouvelles fonctionnalités
- `fix/*` : Corrections de bugs

### Pull Requests

- Description claire des changements
- Référence aux issues si applicable
- Tests passants requis
- Documentation mise à jour

## Dépendances

### Composer

Gérer les dépendances via Composer :

```json
{
    "require": {
        "php": ">=8.3"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0"
    }
}
```

### WordPress

- Utiliser les APIs WordPress natives quand possible
- Éviter de contourner le système WordPress
- Respecter les hooks et filtres existants

## Déploiement

### Préparation

1. Vérifier que tous les tests passent
2. Mettre à jour la version dans `wp-pierre.php`
3. Mettre à jour `CHANGELOG.md`
4. Vérifier la documentation

### Build

```bash
# Installer les dépendances
composer install --no-dev --optimize-autoloader

# Créer le package
zip -r wp-pierre.zip . -x "*.git*" "*.distignore*" "tests/*" "*.md"
```

---

*Pierre says: Following these guidelines helps keep my code clean and maintainable! 🪨*

