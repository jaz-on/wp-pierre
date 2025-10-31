<!-- Coverage badge served from gh-badges branch (set by CI). Replace OWNER/REPO if forking. -->
<!-- ![coverage](https://raw.githubusercontent.com/OWNER/REPO/gh-badges/assets/badges/coverage.svg) -->

# Quick start

1) Installer & activer
- Téléversez le dossier dans `wp-content/plugins/` et activez-le.

2) Webhook global Slack
- Allez dans Admin → Pierre → Settings → onglet « Global Webhook ».
- Activez, collez l’URL Slack, choisissez les `types` (ex. `new_strings`, `milestone`).
- Choisissez le `mode`: `immediate` ou `digest` (intervalle ou heure fixe), puis « Test Webhook ».

3) Découverte des locales
- Admin → Pierre → Settings → « Locales Discovery » : sélectionnez vos locales (ex. `fr`, `es`).
- Optionnel: pour une locale, configurez un `Locale Webhook` (canal dédié, seuils, digest).

4) Découverte des projets
- Admin → Pierre → Settings → « Projects Discovery » : cochez les projets à surveiller.

5) Lancer la surveillance
- Page « Projects » : exécutez un « Dry run », puis « Start Surveillance ».

# Pierre - WordPress Translation Monitor 🪨

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

## The Story Behind Pierre 🪨

Pierre loves WordPress, Pierre loves WordPress when it displays **IN HIS PREFERRED LANGUAGE**, Pierre also loves translating some strings on translate.wordpress.org casually.

But Pierre really struggles to be consistent and keep up with the release pace of the plugins and themes he helps translate.

So he talks to his Jason, a frenchmen who doesn't translate much like Pierre, but often discusses with locale managers, GTE, PTE and remembers an old project initiated by a friend of his: a Belgian named Pascal Casier. The thing is, Pascal is a nerd who loves tinkering with APIs and render datas in a nice way, especially those from wordpress.org. In 2016, he built small tools to crawl, then display data from translate.wordpress.org and thus help translators get notified on their Slack about strings waiting for translation.

So Jason basically decided to rewrite the whole thing from scratch to offer a better experience for the community. As the project wasn't really fun to use, he decided to make it more user-friendly by giving it a personality and a rock emoji.

**INTRODUCING to you: WP-Pierre.** The evolution of this old idea, a WordPress plugin coupled with a website https://pierre.jasonrouet.com/ (temporary URL) to simplify access to this tool for any local WordPress community and manage: adding translation teams + connecting your local community Slack.

*Fun fact: The name Pierre is a nod to the [Pierre de Rosette (French)](https://fr.wikipedia.org/wiki/Pierre_de_Rosette), or Rosetta Stone in English. Pierre's also a surname which translates easily across languages: Peter (DE/EN), Peio (EU), Pedro (ES), Πέτρος (GR), Pier/Pietro (IT), Piotr (PL), etc.

Just like Pierre helps bridge the gap between different languages in WordPress! 🪨

(What a great story, don't you think? It took me 2 hours to write it, but it's worth it. IT'S WORTH IT, RIGHT? RIGHT!?! To be fair, I had a lot of fun writing it. And I'm sure you're happy that I didn't stick with my first idea of naming it after [Pierre-François-Xavier Bouchard](https://fr.wikipedia.org/wiki/Pierre-Fran%C3%A7ois-Xavier_Bouchard)! 🤡)*

---

Pierre is a WordPress plugin that monitors WordPress Polyglots translations and notifies teams via Slack. Built with PHP 8.3+, WordPress 6.0+, and following WordPress Coding Standards.

## Features

### **Translation Monitoring**
- **Real-time Surveillance**: Automated monitoring of WordPress Polyglots translations
- **Change Detection**: Instant notifications when translations are updated
- **Progress Tracking**: Monitor completion percentages and translation status
- **Multi-project Support**: Watch multiple translation projects simultaneously

### **Slack Integration**
- **Instant Notifications**: Get notified immediately when translations change
- **Rich Messages**: Beautiful Slack messages with project details and progress
- **Customizable Alerts**: Configure notification types and thresholds
- **Test Notifications**: Verify your Slack integration before going live

### **Team Management**
- **User Assignments**: Assign team members to specific translation projects
- **Role-based Access**: Granular permissions for different user roles
- **Project Ownership**: Track who's responsible for each translation project
- **Assignment History**: Keep track of team changes and assignments

### **Admin Interface**
- **Dashboard**: Overview of all monitored projects and team assignments
- **Project Management**: Add, remove, and configure translation projects
- **Settings Panel**: Configure surveillance intervals and notification preferences
- **Reports**: Detailed analytics and translation progress reports

### **Public Dashboard**
- **Public Interface**: Share translation progress with stakeholders
- **Responsive Design**: Works perfectly on desktop and mobile devices
- **Custom Routing**: Clean URLs for different locales and projects
- **Real-time Updates**: Live data without page refreshes

## Requirements

- **WordPress**: 6.0 or higher
- **PHP**: 8.1 or higher
- **MySQL**: 5.7+ or MariaDB 10.3+
- **Slack**: Webhook URL for notifications (optional)

## Installation

### Manual Installation
1. Download the plugin files
2. Upload the `wp-pierre` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress

### Via Composer
```bash
composer require wp-pierre/pierre
```

## Configuration

### 1. **Initial Setup**
After activation, Pierre will automatically:
- Create necessary database tables
- Set up custom user roles and capabilities
- Initialize the surveillance system

### 2. **Slack (webhooks global & local)**
1. **Global**: Settings → Global Webhook (URL, types, seuils, mode `immediate`/`digest`)
2. **Local**: Settings → Locales Discovery → ouvrir une locale → `Locale Webhook`
3. Testez le webhook pour vérifier la livraison

### 3. **Add Translation Projects**
1. Go to **Pierre** → **Projects**
2. Click **Add New Project**
3. Enter the project slug (e.g., `wp`, `woocommerce`)
4. Select the locale code (e.g., `fr`, `es`, `de`) — ou utilisez « Projects Discovery »
5. Click **Add Project**

### 4. **Assign Team Members**
1. Go to **Pierre** → **Teams**
2. Select a user and project
3. Choose the appropriate role
4. Click **Assign User**

## Usage

### **For Administrators**
- Monitor all translation projects from the admin dashboard
- Configure surveillance settings and notification preferences
- Manage team assignments and user permissions
- View detailed reports and analytics

### **For Team Members**
- Access the public dashboard to view assigned projects
- Track translation progress and completion status
- Receive Slack notifications for project updates
- Collaborate with team members on translation tasks

### **For Stakeholders**
- View public dashboard for project visibility
- Monitor translation progress without admin access
- Stay informed about project status and deadlines

## API Reference

### **AJAX Endpoints**
- `pierre_start_surveillance` - Start surveillance monitoring
- `pierre_stop_surveillance` - Stop surveillance monitoring
- `pierre_add_project` - Add a new project to monitor
- `pierre_remove_project` - Remove a project from monitoring
- `pierre_admin_save_settings` - Save plugin settings

### **Hooks and Filters**
```php
// Customize notification messages
add_filter('pierre_notification_message', function($message, $type) {
    return $message;
}, 10, 2);

// Modify surveillance intervals
add_filter('pierre_surveillance_interval', function($interval) {
    return 30; // 30 minutes
});
```

## Contributing

This is a community & opensource project, feel free to open issues, PRs or contact me directly!

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

## Author & Sponsorship

**Jason Rouet**
- Website: [https://jasonrouet.com](https://jasonrouet.com)
- Email: [bonjour@jasonrouet.com](mailto:bonjour@jasonrouet.com)
- WordPress.org: [https://profiles.wordpress.org/jaz_on/](https://profiles.wordpress.org/jaz_on/)

You can sponsor me on [Ko-fi](https://ko-fi.com/jasonrouet) or [Github](https://github.com/sponsors/jaz-on).
Any help is welcome, sharing the project, giving feedback, reporting issues, etc.

## Acknowledgments

- WordPress Polyglots team for the translation platform
- Pascal Casier for the initial idea called wpupdates that was live on his site that got hacked (https://wp-info.org/wpupdates-to-slack/). WP-Pierre is essentially a rewrite of this idea.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a complete list of changes.

---

**Pierre says: Thank you for using WordPress Translation Monitor! 🪨**

*Made with ❤️ for the WordPress translation community, hope it helps!*

## Troubleshooting

- Aucun message Slack
  - Vérifiez l’URL (Slack Incoming Webhook), testez via « Test Webhook » ou cURL (ci-dessous)
  - Respectez le cooldown (jusqu’à 2 min entre deux runs forcés)
  - Assurez-vous que WP-Cron s’exécute (hébergeur/cron système)

- Discovery vide
  - Réessayez après expiration du cache (transients), vérifiez la connectivité
  - Rechargez la library de projets (Settings → Projects Discovery)

- i18n
  - Sur WordPress.org, le chargement du textdomain est automatique depuis `languages/`. Aucun chargement manuel requis dans le code.

- Doublons de notifications
  - Contrôlez Global Webhook + Locale Webhook sur le même périmètre; ajustez `scopes`

### Tester un webhook Slack (cURL)

```bash
curl -X POST -H 'Content-type: application/json' \
  --data '{"text":"Pierre test webhook 🪨"}' \
  https://hooks.slack.com/services/T000/B000/XXXX
```

### WP-CLI utile

```bash
wp cron event run pierre_surveillance_check
wp option get pierre_settings --format=json
```