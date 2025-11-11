---
title: Pierre Documentation
---

# Pierre Documentation 🪨

Bienvenue dans la documentation de Pierre. Cette documentation est la source de vérité, versionnée dans le dépôt sous `docs/`. Le Wiki GitHub est mis à jour automatiquement depuis ce dossier.

## Quick Links

- **Getting Started**: [getting-started/](getting-started/) - Guide d'installation et configuration
- **Admin Interface**: [admin/](admin/) - [Templates](admin/templates.md) | [UI Components](admin/ui-components.md)
- **Surveillance System**: [surveillance/](surveillance/) - [Surveillance & Cron](surveillance/cron)
- **Notifications**: [notifications/](notifications/) - [Slack Notifications](notifications/slack)
- **Team Management**: [team-management/](team-management/) - [Capabilities & Permissions](team-management/capabilities)
- **API Reference**: [api/](api/) - [API Integration](api/integration) | [Hooks](api/hooks.md) | [AJAX Endpoints](api/ajax-endpoints/)
- **Architecture**: [architecture/](architecture/) - [Overview](architecture/overview) | [Database](architecture/database) | [Constants](architecture/constants.md) | [Interfaces & Traits](architecture/interfaces-traits.md) | [Helpers](architecture/helpers.md)
- **Workflows**: [workflows/](workflows/) - Workflows complets
- **Use Cases**: [getting-started/use-cases.md](getting-started/use-cases.md) - Cas d'usage principaux
- **Assets**: [development/assets.md](development/assets.md) - Documentation CSS/JS
- **Customization**: [customization/](customization/) - Guide de personnalisation
- **FAQ**: [troubleshooting/faq.md](troubleshooting/faq.md) - Questions fréquentes
- **Development**: [development/](development/) - [Guidelines](development/guidelines) | [Sanitization](development/sanitization.md)
- **Troubleshooting**: [troubleshooting/](troubleshooting/) - [Common Issues](troubleshooting/common-issues)

## À propos de Pierre

Pierre est un plugin WordPress qui surveille les traductions Polyglots et notifie les équipes via Slack. Objectifs principaux:

- Automatiser la surveillance des progrès de traduction (core, plugins, thèmes)
- Envoyer des notifications Slack (Block Kit, digests, rate limit + retry)
- Gérer les équipes (rôles et affectations par projet/locale)
- Proposer un catalogue pour découvrir/ajouter des projets
- Offrir un tableau de bord public en lecture

## Prérequis

- WordPress ≥ 6.0
- PHP ≥ 8.3
- MySQL 5.7+ ou MariaDB 10.3+
- Slack: URL de webhook (optionnelle)

## Gouvernance de la doc

- Les changements sont proposés via PR sur `docs/**`.
- Le Wiki est un miroir automatique. N'éditez pas le Wiki directement.
- Page d'accueil du Wiki = copie de `index.md` en `Home.md` via CI.

## Changelog

Pour voir l'historique des versions et changements, consultez le [CHANGELOG.md](../CHANGELOG.md).

---

Made with ❤️ for the WordPress translation community.


