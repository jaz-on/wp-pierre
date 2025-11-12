---
title: Pierre Documentation
---

# Pierre Documentation 🪨

Bienvenue dans la documentation de Pierre. Cette documentation est la source de vérité, versionnée dans le dépôt sous `docs/`. Le Wiki GitHub est mis à jour automatiquement depuis ce dossier.

## Quick Links

- **Getting Started**: getting-started/
- **Admin Interface**: admin/
- **Surveillance System**: surveillance/
- **Notifications**: notifications/
- **Team Management**: team-management/
- **Projects Catalog**: catalog/ (à venir)
- **API Reference**: api/
- **Architecture**: architecture/
- **Troubleshooting**: troubleshooting/
- **Development**: development/

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
- Le Wiki est un miroir automatique. N’éditez pas le Wiki directement.
- Page d’accueil du Wiki = copie de `index.md` en `Home.md` via CI.

---

Made with ❤️ for the WordPress translation community.


