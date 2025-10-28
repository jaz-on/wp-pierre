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

/**
 * Message Builder class - Pierre's message crafting system! 🪨
 * 
 * @since 1.0.0
 */
class MessageBuilder {
    
    /**
     * Pierre's message templates - he has different styles! 🪨
     * 
     * @var array
     */
    private array $templates = [
        'new_strings' => '🆕 *New strings detected!* 🪨\n\n*Project:* {project_name}\n*Locale:* {locale_name}\n*New strings:* {count}\n*Total completion:* {completion}%\n\nPierre found new strings to translate! 🎉',
        'completion_update' => '📈 *Translation progress update!* 🪨\n\n*Project:* {project_name}\n*Locale:* {locale_name}\n*Completion:* {completion}% ({translated}/{total})\n*Change:* {change:+d}% since last check\n\nPierre is tracking the progress! 📊',
        'needs_attention' => '⚠️ *Translation needs attention!* 🪨\n\n*Project:* {project_name}\n*Locale:* {locale_name}\n*Waiting:* {waiting} strings\n*Fuzzy:* {fuzzy} strings\n*Total completion:* {completion}%\n\nPierre found strings that need review! 🔍',
        'error' => '❌ *Translation monitoring error!* 🪨\n\n*Project:* {project_name}\n*Locale:* {locale_name}\n*Error:* {error_message}\n\nPierre encountered an issue! 😢',
        'test' => '🧪 *Pierre\'s test message!* 🪨\n\nPierre is testing his notification system!\n\n*Status:* {status}\n*Time:* {timestamp}\n\nPierre says: Everything is working! ✅'
    ];
    
    /**
     * Pierre builds a new strings notification! 🪨
     * 
     * @since 1.0.0
     * @param array $project_data The project data
     * @param int $new_strings_count The number of new strings
     * @return array Formatted message for Slack
     */
    public function build_new_strings_message(array $project_data, int $new_strings_count): array {
        $message = $this->format_template('new_strings', [
            'project_name' => $project_data['project_name'] ?? 'Unknown Project',
            'locale_name' => $project_data['locale_name'] ?? 'Unknown Locale',
            'count' => $new_strings_count,
            'completion' => $project_data['stats']['completion_percentage'] ?? 0
        ]);
        
        return $this->build_slack_message($message, 'good');
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
        $current_completion = $project_data['stats']['completion_percentage'] ?? 0;
        $previous_completion = $previous_data['stats']['completion_percentage'] ?? 0;
        $completion_change = $current_completion - $previous_completion;
        
        $message = $this->format_template('completion_update', [
            'project_name' => $project_data['project_name'] ?? 'Unknown Project',
            'locale_name' => $project_data['locale_name'] ?? 'Unknown Locale',
            'completion' => $current_completion,
            'translated' => $project_data['stats']['translated'] ?? 0,
            'total' => $project_data['stats']['total'] ?? 0,
            'change' => $completion_change
        ]);
        
        $color = $completion_change > 0 ? 'good' : ($completion_change < 0 ? 'warning' : 'info');
        return $this->build_slack_message($message, $color);
    }
    
    /**
     * Pierre builds a needs attention message! 🪨
     * 
     * @since 1.0.0
     * @param array $project_data The project data
     * @return array Formatted message for Slack
     */
    public function build_needs_attention_message(array $project_data): array {
        $message = $this->format_template('needs_attention', [
            'project_name' => $project_data['project_name'] ?? 'Unknown Project',
            'locale_name' => $project_data['locale_name'] ?? 'Unknown Locale',
            'waiting' => $project_data['stats']['waiting'] ?? 0,
            'fuzzy' => $project_data['stats']['fuzzy'] ?? 0,
            'completion' => $project_data['stats']['completion_percentage'] ?? 0
        ]);
        
        return $this->build_slack_message($message, 'warning');
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
        return [
            'text' => $text,
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
     * Pierre adds a custom template! 🪨
     * 
     * @since 1.0.0
     * @param string $name The template name
     * @param string $template The template content
     * @return void
     */
    public function add_template(string $name, string $template): void {
        $this->templates[$name] = $template;
        error_log("Pierre added custom template: {$name} 🪨");
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
     * Pierre gets his message builder status! 🪨
     * 
     * @since 1.0.0
     * @return array Message builder status
     */
    public function get_status(): array {
        return [
            'templates_count' => count($this->templates),
            'available_templates' => array_keys($this->templates),
            'message' => 'Pierre\'s message builder is ready! 🪨'
        ];
    }
}