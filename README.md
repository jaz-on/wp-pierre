# Pierre - WordPress Translation Monitor 🪨

[![CI](https://github.com/jaz-on/wp-pierre/actions/workflows/ci.yml/badge.svg)](https://github.com/jaz-on/wp-pierre/actions/workflows/ci.yml)
[![Tests](https://github.com/jaz-on/wp-pierre/actions/workflows/tests.yml/badge.svg)](https://github.com/jaz-on/wp-pierre/actions/workflows/tests.yml)
[![Coverage](https://raw.githubusercontent.com/jaz-on/wp-pierre/gh-badges/assets/badges/coverage.svg)](https://github.com/jaz-on/wp-pierre/actions/workflows/tests.yml)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4.svg)](https://php.net/)
[![Version](https://img.shields.io/badge/Version-1.0.0-blue.svg)](https://github.com/jaz-on/wp-pierre/releases)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

WordPress plugin that monitors WordPress Polyglots translations and notifies teams via Slack. Built with PHP 8.3+, WordPress 6.0+, and following WordPress Coding Standards.

## The Story Behind Pierre 🪨

Pierre loves WordPress, Pierre loves WordPress when it displays **IN HIS PREFERRED LANGUAGE**, Pierre also loves translating some strings on translate.wordpress.org casually.

This project is a rewrite of [Pascal Casier's wpupdates](https://wp-info.org/wpupdates-to-slack/) from 2016, which helped translators get Slack notifications about strings waiting for translation. WP-Pierre modernizes this idea with better UX and a personality—hence the rock emoji.

*The name "Pierre" nods to the [Pierre de Rosette](https://fr.wikipedia.org/wiki/Pierre_de_Rosette) (Rosetta Stone), bridging languages just like Pierre bridges WordPress translations. It translates easily: Peter (EN/DE), Pedro (ES), Πέτρος (GR), Pietro (IT), Piotr (PL), etc.* 🪨

---

## Features

- **Translation Monitoring**: Automated surveillance of WordPress Polyglots translations with real-time change detection and progress tracking
- **Slack Integration**: Instant notifications with rich messages, customizable alerts, and digest/immediate modes
- **Team Management**: Assign team members to projects with role-based access and assignment history
- **Admin & Public Dashboards**: Full admin interface for management and public dashboard for stakeholders

## Requirements

- **WordPress**: 6.0 or higher
- **PHP**: 8.3 or higher
- **MySQL**: 5.7+ or MariaDB 10.3+
- **Slack**: Webhook URL for notifications (optional)

## Quick Start

1. **Install & Activate**: Upload the plugin folder to `wp-content/plugins/` and activate it
2. **Global Webhook**: Admin → Pierre → Settings → Global Webhook (URL, types, thresholds, mode)
3. **Locales Discovery**: Admin → Settings → Locales Discovery → select your locales (e.g., `fr`, `es`)
4. **Projects Discovery**: Admin → Settings → Projects Discovery → check projects to monitor
5. **Start Surveillance**: Projects page → run "Dry run", then "Start Surveillance"

### Optional Configuration

- **Locale Webhooks**: Per-locale Slack channels with dedicated thresholds and digest settings
- **Team Assignments**: Pierre → Teams → assign users to projects with roles
- **Public Dashboard**: Accessible via clean URLs for stakeholders

## Installation

### Manual
1. Download the plugin files
2. Upload `wp-pierre` to `/wp-content/plugins/`
3. Activate via WordPress **Plugins** menu

### Via Composer
```bash
composer require wp-pierre/pierre
```

## Usage

- **Administrators**: Monitor projects, configure surveillance, manage teams, view reports
- **Team Members**: Access public dashboard, track progress, receive Slack notifications
- **Stakeholders**: View public dashboard for project visibility without admin access

## API & Extensibility

### AJAX Endpoints
- `pierre_start_surveillance` - Start surveillance monitoring
- `pierre_stop_surveillance` - Stop surveillance monitoring
- `pierre_add_project` - Add a new project to monitor
- `pierre_remove_project` - Remove a project from monitoring
- `pierre_admin_save_settings` - Save plugin settings

### Hooks & Filters
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

## Troubleshooting

**No Slack messages**
- Verify webhook URL via "Test Webhook" or cURL: `curl -X POST -H 'Content-type: application/json' --data '{"text":"Test"}' YOUR_WEBHOOK_URL`
- Check cooldown (up to 2 min between forced runs)
- Ensure WP-Cron runs (host/system cron)

**Empty Discovery**
- Wait for cache expiration (transients) or reload projects library
- Check connectivity

**Duplicate notifications**
- Control Global + Locale webhook scopes to avoid overlaps

### WP-CLI Helpers
```bash
wp cron event run pierre_surveillance_check
wp option get pierre_settings --format=json
```

## Contributing

This is a community & open source project. Feel free to open issues, PRs, or contact me directly!

## License

GPL v2 or later. See [LICENSE](LICENSE) for details.

## Author & Sponsorship

**Jason Rouet**
- Website: [jasonrouet.com](https://jasonrouet.com)
- Email: [bonjour@jasonrouet.com](mailto:bonjour@jasonrouet.com)
- WordPress.org: [profiles.wordpress.org/jaz_on/](https://profiles.wordpress.org/jaz_on/)

You can sponsor me on [Ko-fi](https://ko-fi.com/jasonrouet) or [GitHub Sponsors](https://github.com/sponsors/jaz-on). Any help is welcome: sharing the project, feedback, reporting issues, etc.

## Acknowledgments

- WordPress Polyglots team for the translation platform
- Pascal Casier for the initial wpupdates idea (2016)

---

**Pierre says: Thank you for using WordPress Translation Monitor! 🪨**

*Made with ❤️ for the WordPress translation community. See [CHANGELOG.md](CHANGELOG.md) for updates.*
