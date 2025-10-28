# Pierre - Technical Documentation 🪨

## Overview

Pierre is a modern WordPress plugin that monitors WordPress Polyglots translations and notifies teams via Slack. Built with PHP 8.3+, WordPress 6.0+, and following WordPress Coding Standards + PSR-4.

## Architecture

### Core Components

#### 1. Plugin Class (`src/Pierre/Plugin.php`)
- **Purpose**: Main orchestrator and plugin lifecycle manager
- **Responsibilities**:
  - Plugin initialization and component loading
  - Activation/deactivation/uninstall hooks
  - Database table creation/removal
  - Text domain loading

#### 2. Surveillance System (`src/Pierre/Surveillance/`)

**CronManager** (`CronManager.php`)
- Manages WordPress cron events for surveillance and cleanup
- Custom intervals: 15 minutes (surveillance), daily (cleanup)
- Methods: `schedule_events()`, `clear_events()`, `run_surveillance_check()`

**TranslationScraper** (`TranslationScraper.php`)
- Scrapes translation data from translate.wordpress.org API
- Caching with WordPress transients (1 hour timeout)
- Methods: `scrape_project_data()`, `scrape_multiple_projects()`, `calculate_stats()`

**ProjectWatcher** (`ProjectWatcher.php`)
- Core surveillance logic implementing `WatcherInterface`
- Monitors project changes and triggers notifications
- Methods: `start_surveillance()`, `analyze_and_notify()`, `watch_project()`

#### 3. Notification System (`src/Pierre/Notifications/`)

**MessageBuilder** (`MessageBuilder.php`)
- Constructs Slack messages from predefined templates
- Templates for: new strings, completion updates, needs attention, errors
- Methods: `build_new_strings_message()`, `format_template()`

**SlackNotifier** (`SlackNotifier.php`)
- Implements `NotifierInterface` for Slack webhook integration
- Methods: `send_notification()`, `test_notification()`, `is_ready()`

#### 4. Team Management (`src/Pierre/Teams/`)

**RoleManager** (`RoleManager.php`)
- Manages WordPress capabilities and custom roles
- 7 custom capabilities, 3 custom roles
- Methods: `add_capabilities()`, `create_custom_roles()`, `user_has_capability()`

**TeamRepository** (`TeamRepository.php`)
- Database operations for user-project assignments
- Table: `{$wpdb->prefix}wpupdates_user_projects`
- Methods: `assign_user_to_project()`, `get_user_assignments()`, `remove_user_from_project()`

**UserProjectLink** (`UserProjectLink.php`)
- Business logic for user-project assignments
- Validation, permission checks, assignment management
- Methods: `assign_user_to_project()`, `remove_user_from_project()`, `validate_assignment()`

#### 5. Controllers (`src/Pierre/Admin/` & `src/Pierre/Frontend/`)

**AdminController** (`AdminController.php`)
- WordPress admin interface management
- 5 admin pages: Dashboard, Teams, Projects, Settings, Reports
- AJAX handlers for user management and settings
- Methods: `add_admin_menu()`, `render_*_page()`, `ajax_*()`

**DashboardController** (`DashboardController.php`)
- Public-facing dashboard with routing
- URLs: `/pierre/`, `/pierre/locale/`, `/pierre/locale/project/`
- Template system with fallback rendering
- Methods: `add_rewrite_rules()`, `handle_template_redirect()`, `render_*()`

## Database Schema

### Custom Table: `wpupdates_user_projects`

```sql
CREATE TABLE wpupdates_user_projects (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    user_id bigint(20) unsigned NOT NULL,
    project_type varchar(50) NOT NULL,
    project_slug varchar(100) NOT NULL,
    locale_code varchar(10) NOT NULL,
    role varchar(50) NOT NULL,
    assigned_by bigint(20) unsigned NOT NULL,
    assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_assignment (user_id, project_slug, locale_code),
    KEY project_locale (project_slug, locale_code),
    KEY user_role (user_id, role)
);
```

## WordPress Integration

### Hooks and Actions

**Activation/Deactivation**:
- `register_activation_hook()` → `Plugin::activate()`
- `register_deactivation_hook()` → `Plugin::deactivate()`
- `register_uninstall_hook()` → `Plugin::uninstall()`

**Cron Events**:
- `pierre_surveillance_check` (every 15 minutes)
- `pierre_cleanup_old_data` (daily)

**Admin Integration**:
- `admin_menu` → Admin menu creation
- `admin_bar_menu` → Admin bar links
- `admin_notices` → Admin notices display

**Public Integration**:
- `init` → Rewrite rules
- `template_redirect` → Custom page handling
- `wp_loaded` → Rewrite rules flush

### Capabilities

**Custom Capabilities**:
- `wpupdates_manage_projects` - Manage translation projects
- `wpupdates_manage_teams` - Manage translation teams
- `wpupdates_view_dashboard` - View Pierre dashboard
- `wpupdates_manage_settings` - Manage Pierre settings
- `wpupdates_view_reports` - View translation reports
- `wpupdates_assign_projects` - Assign projects to users
- `wpupdates_manage_notifications` - Manage notification settings

**Custom Roles**:
- `pierre_admin` - Full access to all Pierre features
- `pierre_manager` - Manage teams and projects
- `pierre_contributor` - View dashboard and reports

## API Integration

### translate.wordpress.org API

**Base URL**: `https://translate.wordpress.org/api/projects`

**Endpoints**:
- `/{project_slug}/{locale_code}/` - Project translation data
- `/{project_slug}/` - Project metadata

**Response Format**:
```json
{
  "name": "Project Name",
  "slug": "project-slug",
  "strings": {
    "total": 1000,
    "translated": 800,
    "untranslated": 200
  },
  "stats": {
    "completion_percentage": 80
  }
}
```

### Slack Webhook Integration

**Webhook URL**: Configured in admin settings
**Payload Format**:
```json
{
  "text": "Pierre's message",
  "attachments": [
    {
      "color": "good",
      "fields": [
        {
          "title": "Project",
          "value": "Project Name",
          "short": true
        }
      ]
    }
  ]
}
```

## Security Features

### Input Sanitization
- All user inputs sanitized with WordPress functions
- `sanitize_key()`, `sanitize_text_field()`, `sanitize_url()`
- Database queries use prepared statements

### Permission Checks
- Capability verification for all admin actions
- Nonce verification for AJAX requests
- User existence validation

### Output Escaping
- `esc_html()`, `esc_url()`, `esc_attr()` for all outputs
- XSS prevention in templates and responses

## Testing

### Test Structure
- PHPUnit 10.0+ test suite
- Mock WordPress functions in `tests/bootstrap.php`
- Coverage reporting (HTML, text, Clover)

### Test Categories
- Unit tests for individual classes
- Integration tests for component interactions
- Mock external API calls

### Running Tests
```bash
composer test
composer test-coverage
```

## Performance Considerations

### Caching Strategy
- WordPress transients for API responses (1 hour)
- Database query optimization
- Minimal external API calls

### Memory Management
- Efficient data structures
- Proper cleanup in deactivation
- Transient cleanup for old data

## Error Handling

### Logging
- WordPress `error_log()` for debugging
- Graceful degradation on API failures
- User-friendly error messages

### Fallbacks
- Template fallback system
- Default values for missing data
- Graceful handling of missing dependencies

## Development Guidelines

### Code Standards
- WordPress Coding Standards
- PSR-4 autoloading
- PHP 8.3+ features (typed properties, union types)

### Documentation
- PHPDoc comments for all methods
- Inline comments with Pierre's personality
- Technical documentation in `/docs`

### Version Control
- Semantic versioning
- Changelog maintenance
- Git hooks for code quality

## Deployment

### Requirements
- PHP 8.3+
- WordPress 6.0+
- MySQL 5.7+ or MariaDB 10.3+

### Installation
1. Upload plugin to `/wp-content/plugins/`
2. Activate through WordPress admin
3. Configure Slack webhook URL
4. Assign users to projects

### Configuration
- Admin → Pierre → Settings
- Configure Slack webhook
- Set surveillance intervals
- Manage user assignments

---

*Pierre says: This documentation covers all the technical aspects of my plugin! If you need more details, just ask! 🪨*
