=== Pierre - Translation Monitor ===
Contributors: jaz_on
Tags: translation, polyglots, slack, notifications, monitoring
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Pierre monitors Polyglots translations and notifies teams via Slack 🪨

== Description ==

Pierre is a WordPress plugin that helps translation teams stay on top of their Polyglots projects by monitoring translation progress and sending notifications via Slack.

**The Story Behind Pierre**

Pierre loves WordPress, Pierre loves WordPress when it displays IN HIS LANGUAGE, Pierre loves translating some strings on translate.wordpress.org from time to time.

But Pierre really struggles to be consistent and keep up with the release rhythm of the plugins he helps translate.

So he talks to Jason, who translates little, but often discusses with locale managers, GTE, PTE and remembers an old project initiated by a friend of his (hold on, it's worth it): a Belgian, named Pascal. Pascal is a nerd and loves tinkering with APIs, especially those from wordpress.org. In 2016, he set up small tools to crawl, then display data from translate.wordpress.org and thus help translators be notified on their slack of strings pending translation.

INTRODUCING to you: WP-Pierre. The evolution of this old idea, rewritten from scratch, joined to a site https://pierre.jasonrouet.com/ (temporary url) to simplify access to this tool for any local WordPress community and manage: adding translation teams + connecting your local community Slack.

**Fun fact:** The name "Pierre" is inspired by the [Rosetta Stone](https://fr.wikipedia.org/wiki/Pierre_de_Rosette), the famous artifact that helped decipher Egyptian hieroglyphs - just like this plugin helps decipher translation needs! 🪨

= Features =

* **Project Monitoring**: Track translation progress for plugins, themes, and core
* **Slack Integration**: Get notified when translations need attention
* **Team Management**: Organize translation teams with different roles
* **Security Features**: Comprehensive security auditing and protection
* **Performance Optimization**: Intelligent caching and batch processing
* **Multi-language Support**: Full internationalization ready
* **Public Dashboard**: Share translation status with your community

= Installation =

1. Upload the plugin files to `/wp-content/plugins/pierre-translation-monitor/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure your Slack webhook URL in the plugin settings
4. Add translation projects to monitor
5. Set up your translation teams

= Frequently Asked Questions =

= Does this plugin work with any Slack workspace? =

Yes! Pierre works with any Slack workspace. You just need to create a webhook URL in your Slack workspace and configure it in the plugin settings.

= Can I monitor multiple translation projects? =

Absolutely! Pierre can monitor unlimited translation projects including plugins, themes, and WordPress core translations.

= Is this plugin secure? =

Yes! Pierre includes comprehensive security features including CSRF protection, input validation, output sanitization, and security auditing.

= Does Pierre support multiple languages? =

Yes! Pierre is fully internationalized and ready for translation into any language.

= Screenshots =

1. Pierre's main dashboard showing translation project status
2. Slack notification example showing pending translations
3. Team management interface for organizing translators
4. Security audit dashboard with recommendations
5. Public dashboard for community visibility

= Changelog =

Note: On WordPress.org, translations are auto-loaded from the `languages/` directory. No manual `load_plugin_textdomain()` is needed.

= 1.0.4 =
* Fixed component initialization order to prevent typed property errors
* Comprehensive analysis of all initialization dependencies
* Ensured proper component instantiation sequence

= 1.0.3 =
* Fixed typed property initialization for slack_notifier
* Reordered component initialization in correct dependency order

= 1.0.2 =
* Fixed typed property initialization for role_manager
* Added init_components() call in activate() method

= 1.0.1 =
* Fixed closure serialization issue in uninstall hook
* Converted anonymous function to named function

= 1.0.0 =
* Initial release
* Core translation monitoring functionality
* Slack integration
* Team management
* Security features
* Performance optimization
* Public dashboard

= Upgrade Notice =

= 1.0.5 =
Critical update fixing fatal errors and WordPress compliance issues. Update immediately.

= 1.0.4 =
Important update fixing initialization errors. Update recommended.

= 1.0.3 =
Bug fix for activation errors. Update recommended.

= 1.0.2 =
Bug fix for activation errors. Update recommended.

= 1.0.1 =
Bug fix for uninstall issues. Update recommended.

= 1.0.0 =
Initial release of Pierre - Translation Monitor.

== Support ==

For support, please visit the [GitHub repository](https://github.com/jaz-on/wp-pierre) or contact the author at bonjour@jasonrouet.com.

== Credits ==

* Inspired by Pascal's original 2016 translation monitoring tools
* Built with love for the WordPress Polyglots community
* Named after the Rosetta Stone for its role in deciphering languages

== Privacy Policy ==

Pierre does not collect any personal data. All translation data is fetched from the public WordPress.org Polyglots API. Slack notifications are sent only to configured webhook URLs.

== Technical Details ==

* **Minimum WordPress Version**: 6.0
* **Minimum PHP Version**: 8.3
* **Database Changes**: Creates custom table for user-project links
* **Cron Jobs**: Uses WordPress cron for scheduled monitoring
* **Security**: Implements WordPress security best practices
* **Performance**: Uses WordPress transients for caching
