<div align="center">

# Pierre 🪨 WordPress Translation Monitor

[![CI](https://github.com/jaz-on/wp-pierre/actions/workflows/ci.yml/badge.svg)](https://github.com/jaz-on/wp-pierre/actions/workflows/ci.yml)
[![Tests](https://github.com/jaz-on/wp-pierre/actions/workflows/tests.yml/badge.svg)](https://github.com/jaz-on/wp-pierre/actions/workflows/tests.yml)
[![Coverage](https://raw.githubusercontent.com/jaz-on/wp-pierre/gh-badges/assets/badges/coverage.svg)](https://github.com/jaz-on/wp-pierre/actions/workflows/tests.yml)
[![Version](https://img.shields.io/badge/Version-1.0.0-blue.svg)](https://github.com/jaz-on/wp-pierre/releases)

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

</div>

WordPress plugin that monitors WordPress Polyglots translations and notifies teams via Slack. Built with PHP 8.3+, WordPress 6.0+, and following WordPress Coding Standards.

## The Story Behind Pierre 🪨

Pierre loves WordPress, Pierre loves WordPress when it displays **IN HIS PREFERRED LANGUAGE**, Pierre also loves translating some strings on translate.wordpress.org casually.

But Pierre really struggles to be consistent and keep up with the release pace of the plugins and themes he helps translate.

So he talks to his Jason, a frenchmen who doesn't translate much like Pierre, but often discusses with locale managers, GTE, PTE and remembers an old project initiated by a friend of his: a Belgian named Pascal Casier. The thing is, Pascal is a nerd who loves tinkering with APIs and render datas in a nice way, especially those from wordpress.org. In 2016, he built small tools to crawl, then display data from translate.wordpress.org and thus help translators get notified on their Slack about strings waiting for translation.

So Jason basically decided to rewrite the whole thing from scratch to offer a better experience for the community. As the project wasn't really fun to use, he decided to make it more user-friendly by giving it a personality and a rock emoji.

**INTRODUCING to you: WP-Pierre.** The evolution of this old idea, a WordPress plugin coupled with a website https://pierre.jasonrouet.com/ (temporary URL) to simplify access to this tool for any local WordPress community and manage: adding translation teams + connecting your local community Slack.

*Fun fact: The name Pierre is a nod to the [Pierre de Rosette (French)](https://fr.wikipedia.org/wiki/Pierre_de_Rosette), or Rosetta Stone in English. Pierre's also a surname which translates easily across languages: Peter (DE/EN), Peio (EU), Pedro (ES), Πέτρος (GR), Pier/Pietro (IT), Piotr (PL), etc.

Just like Pierre helps bridge the gap between different languages in WordPress! 🪨

(What a great story, don't you think? It took me 2 hours to write it, but it's worth it. IT'S WORTH IT, RIGHT? RIGHT!?! To be fair, I had a lot of fun writing it. And I'm sure you're happy that I didn't stick with my first idea of naming it after [Pierre-François-Xavier Bouchard](https://fr.wikipedia.org/wiki/Pierre-Fran%C3%A7ois-Xavier_Bouchard)! 🤡)*

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
This project is a rewrite of [Pascal Casier's wpupdates](https://wp-info.org/wpupdates-to-slack/) from 2016, which helped translators get Slack notifications about strings waiting for translation. WP-Pierre modernizes this idea with better UX and a personality—hence the rock emoji.

---

**Pierre says: Thank you for using WordPress Translation Monitor! 🪨**

*Made with ❤️ for the WordPress translation community. See [CHANGELOG.md](CHANGELOG.md) for updates.*
