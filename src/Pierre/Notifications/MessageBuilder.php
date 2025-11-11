<?php
/**
 * Pierre's message builder - he crafts beautiful messages! 🪨
 * 
 * This class handles building and formatting messages for Slack
 * notifications when Pierre detects changes in translations.
 * 
 * @package Pierre
 * @since 1.0.0
 */

namespace Pierre\Notifications;

use Pierre\Traits\StatusTrait;
use Pierre\Logging\Logger;

/**
 * Message Builder class - Pierre's message crafting system! 🪨
 * 
 * @since 1.0.0
 */
class MessageBuilder {
    use StatusTrait;
    
    /**
     * Pierre's message templates - he has different styles! 🪨
     * 
     * @var array
     */
    private array $templates = [
        'new_strings' => "🆕 *New strings detected!* 🪨\n\n*Project:* {project_name}\n*Locale:* {locale_name}\n*New strings:* {count}\n*Total completion:* {completion}%\n\n<{link}|Open on translate.wordpress.org>",
        'completion_update' => "📈 *Translation progress update!* 🪨\n\n*Project:* {project_name}\n*Locale:* {locale_name}\n*Completion:* {completion}% ({translated}/{total})\n*Change:* {change:+d}%\n\n<{link}|Open on translate.wordpress.org>",
        'needs_attention' => "⚠️ *Translation needs attention!* 🪨\n\n*Project:* {project_name}\n*Locale:* {locale_name}\n*Waiting:* {waiting}\n*Fuzzy:* {fuzzy}\n*Completion:* {completion}%\n\n<{link}|Open on translate.wordpress.org>",
        'approval' => "✅ *Recent approvals!* 🪨\n\n*Project:* {project_name}\n*Locale:* {locale_name}\n*Approved since last check:* {approved}\n\n<{link}|Open on translate.wordpress.org>",
        'milestone' => "🏁 *Milestone reached!* 🪨\n\n*Project:* {project_name}\n*Locale:* {locale_name}\n*Completion:* {completion}%\n\n<{link}|Open on translate.wordpress.org>",
        'error' => "❌ *Translation monitoring error!* 🪨\n\n*Project:* {project_name}\n*Locale:* {locale_name}\n*Error:* {error_message}",
        'test' => "🧪 *Pierre's test message!* 🪨\n\n*Status:* {status}\n*Time:* {timestamp}"
    ];
    
    /**
     * Extract common project data for message building.
     * 
     * @since 1.0.0
     * @param array $project_data The project data
     * @return array Extracted project data
     */
    private function extract_project_data(array $project_data): array {
        return [
            'project_type' => (string)($project_data['project_type'] ?? 'meta'),
            'project_slug' => (string)($project_data['project_slug'] ?? ''),
            'locale_code' => (string)($project_data['locale_code'] ?? ''),
            'project_name' => $project_data['project_name'] ?? 'Unknown Project',
            'locale_name' => $project_data['locale_name'] ?? 'Unknown Locale',
            'completion' => $project_data['stats']['completion_percentage'] ?? 0,
            'translated' => $project_data['stats']['translated'] ?? 0,
            'total' => $project_data['stats']['total'] ?? 0,
            'waiting' => $project_data['stats']['waiting'] ?? 0,
            'fuzzy' => $project_data['stats']['fuzzy'] ?? 0,
        ];
    }
    
    /**
     * Build message with translate link.
     * 
     * @since 1.0.0
     * @param string $template_name Template name
     * @param array $variables Template variables (link will be added automatically)
     * @param array $project_data Project data for link building
     * @param string $color Message color
     * @return array Formatted Slack message
     */
    private function build_message_with_link(string $template_name, array $variables, array $project_data, string $color = 'good'): array {
        $extracted = $this->extract_project_data($project_data);
        $link = $this->build_translate_link(
            $extracted['project_type'],
            $extracted['project_slug'],
            $extracted['locale_code']
        );
        
        $variables['link'] = $link;
        $variables['project_name'] = $variables['project_name'] ?? $extracted['project_name'];
        $variables['locale_name'] = $variables['locale_name'] ?? $extracted['locale_name'];
        
        $message = $this->format_template($template_name, $variables);
        return $this->build_slack_message($message, $color);
    }
    
    /**
     * Pierre builds a new strings notification! 🪨
     * 
     * @since 1.0.0
     * @param array $project_data The project data
     * @param int $new_strings_count The number of new strings
     * @return array Formatted message for Slack
     */
    public function build_new_strings_message(array $project_data, int $new_strings_count): array {
        $extracted = $this->extract_project_data($project_data);
        return $this->build_message_with_link('new_strings', [
            'count' => $new_strings_count,
            'completion' => $extracted['completion'],
        ], $project_data, 'good');
    }
    
    /**
     * Pierre builds a completion update message! 🪨
     * 
     * @since 1.0.0
     * @param array $project_data The project data
     * @param array $previous_data The previous project data
     * @return array Formatted message for Slack
     */
    public function build_completion_update_message(array $project_data, array $previous_data): array {
        $extracted = $this->extract_project_data($project_data);
        $previous_completion = $previous_data['stats']['completion_percentage'] ?? 0;
        $completion_change = $extracted['completion'] - $previous_completion;
        
        $color = $completion_change > 0 ? 'good' : ($completion_change < 0 ? 'warning' : 'info');
        
        return $this->build_message_with_link('completion_update', [
            'completion' => $extracted['completion'],
            'translated' => $extracted['translated'],
            'total' => $extracted['total'],
            'change' => $completion_change,
        ], $project_data, $color);
    }
    
    /**
     * Pierre builds a needs attention message! 🪨
     * 
     * @since 1.0.0
     * @param array $project_data The project data
     * @return array Formatted message for Slack
     */
    public function build_needs_attention_message(array $project_data): array {
        $extracted = $this->extract_project_data($project_data);
        return $this->build_message_with_link('needs_attention', [
            'waiting' => $extracted['waiting'],
            'fuzzy' => $extracted['fuzzy'],
            'completion' => $extracted['completion'],
        ], $project_data, 'warning');
    }
    
    /**
     * Pierre builds an error message! 🪨
     * 
     * @since 1.0.0
     * @param string $project_slug The project slug
     * @param string $locale_code The locale code
     * @param string $error_message The error message
     * @return array Formatted message for Slack
     */
    public function build_error_message(string $project_slug, string $locale_code, string $error_message): array {
        $message = $this->format_template('error', [
            'project_name' => $project_slug,
            'locale_name' => $locale_code,
            'error_message' => $error_message
        ]);
        
        return $this->build_slack_message($message, 'danger');
    }
    
    /**
     * Pierre builds a test message! 🪨
     * 
     * @since 1.0.0
     * @param string $status The test status
     * @return array Formatted message for Slack
     */
    public function build_test_message(string $status = 'success'): array {
        $message = $this->format_template('test', [
            'status' => $status,
            'timestamp' => current_time('Y-m-d H:i:s')
        ]);
        
        return $this->build_slack_message($message, 'good');
    }

    public function build_approval_message(array $project_data, int $approved_count): array {
        return $this->build_message_with_link('approval', [
            'approved' => $approved_count,
        ], $project_data, 'good');
    }

    public function build_milestone_message(array $project_data, int $milestone): array {
        $extracted = $this->extract_project_data($project_data);
        return $this->build_message_with_link('milestone', [
            'completion' => $extracted['completion'],
        ], $project_data, 'good');
    }
    
    /**
     * Pierre builds a bulk update message! 🪨
     * 
     * @since 1.0.0
     * @param array $projects_data Array of project data
     * @return array Formatted message for Slack
     */
    public function build_bulk_update_message(array $projects_data): array {
        $message = "📊 *Pierre's surveillance report!* 🪨\n\n";
        $message .= "*Checked projects:* " . count($projects_data) . "\n\n";
        
        foreach ($projects_data as $project) {
            $completion = $project['stats']['completion_percentage'] ?? 0;
            $needs_attention = ($project['stats']['waiting'] ?? 0) + ($project['stats']['fuzzy'] ?? 0);
            
            $status_emoji = $completion >= 100 ? '✅' : ($needs_attention > 0 ? '⚠️' : '📈');
            
            $message .= "{$status_emoji} *{$project['project_name']}* ({$project['locale_name']})\n";
            $message .= "   Completion: {$completion}% ({$project['stats']['translated']}/{$project['stats']['total']})\n";
            
            if ($needs_attention > 0) {
                $message .= "   Needs attention: {$needs_attention} strings\n";
            }
            
            $message .= "\n";
        }
        
        $message .= "Pierre completed his surveillance round! 🪨";
        
        return $this->build_slack_message($message, 'info');
    }
    
    /**
     * Pierre formats a template with variables! 🪨
     * 
     * @since 1.0.0
     * @param string $template_name The template name
     * @param array $variables The variables to replace
     * @return string Formatted message
     */
    private function format_template(string $template_name, array $variables): string {
        $template = $this->templates[$template_name] ?? 'Pierre says: Template not found! 🪨';
        
        // Pierre replaces the variables! 🪨
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        
        return $template;
    }
    
    /**
     * Pierre builds a Slack message! 🪨
     * 
     * @since 1.0.0
     * @param string $text The message text
     * @param string $color The message color
     * @return array Formatted Slack message
     */
    private function build_slack_message(string $text, string $color = 'good'): array {
        // Provide Blocks (preferred) and keep attachments for broad compatibility
        $blocks = [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $text
                ]
            ]
        ];
        return [
            'text' => $text,
            'blocks' => $blocks,
            'attachments' => [
                [
                    'color' => $color,
                    'footer' => 'Pierre - WordPress Translation Monitor',
                    'footer_icon' => 'https://s.w.org/images/wmark.png',
                    'ts' => time()
                ]
            ]
        ];
    }

    /**
     * Build translate.wordpress.org link for a project/locale
     */
    private function build_translate_link(string $project_type, string $project_slug, string $locale_code, string $set = 'default'): string {
        $segments = [
            'core' => 'wp',
            'plugin' => 'wp-plugins',
            'theme' => 'wp-themes',
            'meta' => 'meta',
            'app' => 'apps',
        ];
        $type = $segments[$project_type] ?? $segments['meta'];
        $project_slug = sanitize_key($project_slug);
        // Normaliser le code locale (ex: fr_FR)
        $locale_code = preg_replace_callback(
            '/^([a-z]{2})(?:_([a-zA-Z]{2}))?$/',
            static function ($m) {
                return isset($m[2]) ? strtolower($m[1]) . '_' . strtoupper($m[2]) : strtolower($m[1]);
            },
            trim((string) $locale_code)
        );
        $set = sanitize_key($set);
        $url = 'https://translate.wordpress.org/projects/' . $type . '/' . $project_slug . '/' . $locale_code . '/' . $set . '/';
        return esc_url_raw($url);
    }
    
    /**
     * Pierre adds a custom template! 🪨
     * 
     * @since 1.0.0
     * @param string $name The template name
     * @param string $template The template content
     * @return void
     */
    public function add_template(string $name, string $template): void {
        $this->templates[$name] = $template;
        Logger::static_debug("Pierre added custom template: {$name} 🪨", ['source' => 'MessageBuilder']);
    }
    
    /**
     * Pierre gets all his templates! 🪨
     * 
     * @since 1.0.0
     * @return array All available templates
     */
    public function get_templates(): array {
        return $this->templates;
    }
    
    /**
     * Get status message.
     *
     * @since 1.0.0
     * @return string Status message
     */
    protected function get_status_message(): string {
        return 'Pierre\'s message builder is ready! 🪨';
    }

    /**
     * Get status details.
     *
     * @since 1.0.0
     * @return array Status details
     */
    protected function get_status_details(): array {
        return [
            'templates_count' => count($this->templates),
            'available_templates' => array_keys($this->templates),
        ];
    }
}