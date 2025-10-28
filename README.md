# Pierre - WordPress Translation Monitor 🪨

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

Pierre is a modern WordPress plugin that monitors WordPress Polyglots translations and notifies teams via Slack. Built with PHP 8.3+, WordPress 6.0+, and following WordPress Coding Standards.

## 🚀 Features

### 📊 **Translation Monitoring**
- **Real-time Surveillance**: Automated monitoring of WordPress Polyglots translations
- **Change Detection**: Instant notifications when translations are updated
- **Progress Tracking**: Monitor completion percentages and translation status
- **Multi-project Support**: Watch multiple translation projects simultaneously

### 🔔 **Slack Integration**
- **Instant Notifications**: Get notified immediately when translations change
- **Rich Messages**: Beautiful Slack messages with project details and progress
- **Customizable Alerts**: Configure notification types and thresholds
- **Test Notifications**: Verify your Slack integration before going live

### 👥 **Team Management**
- **User Assignments**: Assign team members to specific translation projects
- **Role-based Access**: Granular permissions for different user roles
- **Project Ownership**: Track who's responsible for each translation project
- **Assignment History**: Keep track of team changes and assignments

### 🎛️ **Admin Interface**
- **Dashboard**: Overview of all monitored projects and team assignments
- **Project Management**: Add, remove, and configure translation projects
- **Settings Panel**: Configure surveillance intervals and notification preferences
- **Reports**: Detailed analytics and translation progress reports

### 🌍 **Public Dashboard**
- **Public Interface**: Share translation progress with stakeholders
- **Responsive Design**: Works perfectly on desktop and mobile devices
- **Custom Routing**: Clean URLs for different locales and projects
- **Real-time Updates**: Live data without page refreshes

## 📋 Requirements

- **WordPress**: 6.0 or higher
- **PHP**: 8.3 or higher
- **MySQL**: 5.7+ or MariaDB 10.3+
- **Slack**: Webhook URL for notifications (optional)

## 🛠️ Installation

### From WordPress Admin
1. Go to **Plugins** → **Add New**
2. Search for "Pierre Translation Monitor"
3. Click **Install Now** and then **Activate**

### Manual Installation
1. Download the plugin files
2. Upload the `wp-pierre` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress

### Via Composer
```bash
composer require wp-pierre/pierre
```

## ⚙️ Configuration

### 1. **Initial Setup**
After activation, Pierre will automatically:
- Create necessary database tables
- Set up custom user roles and capabilities
- Initialize the surveillance system

### 2. **Slack Integration**
1. Go to **Pierre** → **Settings**
2. Enter your Slack webhook URL
3. Test the connection to ensure notifications work
4. Configure notification preferences

### 3. **Add Translation Projects**
1. Go to **Pierre** → **Projects**
2. Click **Add New Project**
3. Enter the project slug (e.g., `wp`, `woocommerce`)
4. Select the locale code (e.g., `fr`, `es`, `de`)
5. Click **Add Project**

### 4. **Assign Team Members**
1. Go to **Pierre** → **Teams**
2. Select a user and project
3. Choose the appropriate role
4. Click **Assign User**

## 🎯 Usage

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

## 🔧 API Reference

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

## 🧪 Testing

Pierre includes a comprehensive test suite built with PHPUnit:

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run specific test suite
./vendor/bin/phpunit tests/Surveillance/
```

## 🌐 Internationalization

Pierre is fully internationalized and ready for translation:

- **Text Domain**: `wp-pierre`
- **Language Files**: Located in `/languages/`
- **Translation Template**: `wp-pierre.pot`
- **Supported Languages**: All languages supported by WordPress

### Contributing Translations
1. Download the `wp-pierre.pot` file
2. Translate the strings using Poedit or similar tool
3. Save as `wp-pierre-{locale}.po` (e.g., `wp-pierre-fr_FR.po`)
4. Submit your translation via GitHub or WordPress.org

## 🔒 Security

Pierre follows WordPress security best practices:

- **Input Sanitization**: All user inputs are properly sanitized
- **Output Escaping**: All outputs are escaped to prevent XSS
- **Nonce Verification**: All AJAX requests include nonce verification
- **Capability Checks**: Proper permission checks for all admin actions
- **Prepared Statements**: All database queries use prepared statements

## 📊 Performance

Pierre is optimized for performance:

- **Efficient Caching**: Uses WordPress transients for API responses
- **Minimal Database Queries**: Optimized queries with proper indexing
- **Background Processing**: Surveillance runs in the background
- **Memory Management**: Proper cleanup and memory optimization

## 🐛 Troubleshooting

### **Common Issues**

**Slack notifications not working:**
- Verify your webhook URL is correct
- Check that the webhook is active in Slack
- Test the connection in Pierre settings

**Surveillance not starting:**
- Ensure WordPress cron is working
- Check that you have proper permissions
- Verify the project slug and locale are valid

**Public dashboard not loading:**
- Check that permalinks are enabled
- Verify the routing is working correctly
- Ensure the template files are present

### **Debug Mode**
Enable WordPress debug mode to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

### **Development Setup**
```bash
# Clone the repository
git clone https://github.com/jaz-on/wp-pierre.git

# Install dependencies
composer install

# Run tests
composer test

# Check code standards
composer run-script phpcs
```

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

## 👨‍💻 Author

**Jason Rouet**
- Website: [https://jasonrouet.com](https://jasonrouet.com)
- Email: [bonjour@jasonrouet.com](mailto:bonjour@jasonrouet.com)
- WordPress.org: [https://profiles.wordpress.org/jaz_on/](https://profiles.wordpress.org/jaz_on/)

## 🙏 Acknowledgments

- WordPress Polyglots team for the translation platform
- Slack for the notification webhook API
- WordPress community for coding standards and best practices

## 📈 Changelog

See [CHANGELOG.md](CHANGELOG.md) for a complete list of changes.

---

**Pierre says: Thank you for using WordPress Translation Monitor! 🪨**

*Made with ❤️ for the WordPress translation community*