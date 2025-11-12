# Gestion des Erreurs et Rate Limiting

## Gestion des Erreurs

Tous les endpoints peuvent retourner des erreurs avec les codes suivants :

- `invalid_nonce` : Nonce invalide ou expiré
- `forbidden` : Permission insuffisante
- `missing_parameter` : Paramètre requis manquant
- `invalid_parameter` : Paramètre invalide
- `rate_limit_exceeded` : Limite de taux dépassée
- `not_found` : Ressource non trouvée
- `server_error` : Erreur serveur

## Rate Limiting

Certains endpoints sont protégés par un rate limiting :

- `pierre_admin_fetch_catalog` : 60 requêtes par minute
- `pierre_test_notification` : Limité (public)

