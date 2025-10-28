<?php
/**
 * Pierre's admin projects template - he manages translation projects! 🪨
 * 
 * @package Pierre
 * @since 1.0.0
 */

// Pierre prevents direct access! 🪨
if (!defined('ABSPATH')) {
    exit;
}

$data = $GLOBALS['pierre_admin_template_data'] ?? [];
?>

<div class="wrap">
    <div class="pierre-admin-header">
        <h1>Pierre Projects 🪨</h1>
        <p>Manage Translation Projects & Surveillance</p>
    </div>

    <?php if (isset($data['stats']) && !empty($data['stats'])): ?>
    <div class="pierre-admin-stats">
        <h2>Project Statistics</h2>
        <div class="pierre-stats-grid">
            <?php foreach ($data['stats'] as $stat): ?>
            <div class="pierre-stat-box">
                <div class="pierre-stat-number"><?php echo esc_html($stat['value']); ?></div>
                <div class="pierre-stat-label"><?php echo esc_html($stat['label']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="pierre-admin-cards">
        <div class="pierre-admin-card">
            <h2>Surveillance Status 🪨</h2>
            <?php if (isset($data['surveillance_status'])): ?>
            <div class="pierre-surveillance-status">
                <div class="pierre-status-indicator <?php echo $data['surveillance_status']['active'] ? 'active' : 'inactive'; ?>">
                    <?php echo $data['surveillance_status']['active'] ? '🟢 Active' : '🔴 Inactive'; ?>
                </div>
                <p><strong>Message:</strong> <?php echo esc_html($data['surveillance_status']['message'] ?? 'No status message'); ?></p>
                <?php if (isset($data['surveillance_status']['next_run'])): ?>
                <p><strong>Next Run:</strong> <?php echo esc_html($data['surveillance_status']['next_run']); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="pierre-surveillance-controls">
                <button class="pierre-admin-button success" id="pierre-start-surveillance">
                    Start Surveillance 🪨
                </button>
                <button class="pierre-admin-button danger" id="pierre-stop-surveillance">
                    Stop Surveillance 🪨
                </button>
                <button class="pierre-admin-button secondary" id="pierre-test-surveillance">
                    Test Surveillance 🪨
                </button>
            </div>
        </div>

        <div class="pierre-admin-card">
            <h2>Watched Projects 🪨</h2>
            <?php if (isset($data['watched_projects']) && !empty($data['watched_projects'])): ?>
            <div class="pierre-projects-list">
                <?php foreach ($data['watched_projects'] as $project): ?>
                <div class="pierre-project-item">
                    <div class="pierre-project-info">
                        <strong><?php echo esc_html($project['project_slug'] ?? 'Unknown Project'); ?></strong>
                        <span class="pierre-project-meta">
                            (<?php echo esc_html($project['locale_code'] ?? 'Unknown Locale'); ?>)
                        </span>
                    </div>
                    <div class="pierre-project-actions">
                        <button class="pierre-admin-button small danger" 
                                data-project="<?php echo esc_attr($project['project_slug'] ?? ''); ?>"
                                data-locale="<?php echo esc_attr($project['locale_code'] ?? ''); ?>">
                            Unwatch 🪨
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p>Pierre says: No projects are being watched yet! 😢</p>
            <div class="pierre-add-project">
                <h3>Add New Project to Watch</h3>
                <form class="pierre-admin-form" id="pierre-add-project-form">
                    <div class="pierre-form-group">
                        <label for="project_slug">Project Slug:</label>
                        <input type="text" id="project_slug" name="project_slug" placeholder="e.g., wp, woocommerce" required>
                        <div class="pierre-form-help">Enter the WordPress project slug</div>
                    </div>
                    <div class="pierre-form-group">
                        <label for="locale_code">Locale Code:</label>
                        <input type="text" id="locale_code" name="locale_code" placeholder="e.g., fr, es, de" required>
                        <div class="pierre-form-help">Enter the locale code (2-5 characters)</div>
                    </div>
                    <button type="submit" class="pierre-admin-button success">
                        Add Project to Watch 🪨
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <div class="pierre-admin-card">
            <h2>Project Management 🪨</h2>
            <div class="pierre-project-management">
                <h3>Bulk Actions</h3>
                <div class="pierre-bulk-actions">
                    <button class="pierre-admin-button" id="pierre-refresh-all-projects">
                        Refresh All Projects 🪨
                    </button>
                    <button class="pierre-admin-button secondary" id="pierre-export-projects">
                        Export Project Data 🪨
                    </button>
                </div>
                
                <h3>Project Statistics</h3>
                <div class="pierre-project-stats">
                    <div class="pierre-stat-item">
                        <span class="pierre-stat-label">Total Projects:</span>
                        <span class="pierre-stat-value"><?php echo count($data['watched_projects'] ?? []); ?></span>
                    </div>
                    <div class="pierre-stat-item">
                        <span class="pierre-stat-label">Active Surveillance:</span>
                        <span class="pierre-stat-value"><?php echo isset($data['surveillance_status']['active']) && $data['surveillance_status']['active'] ? 'Yes' : 'No'; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pierre-admin-actions">
        <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-dashboard')); ?>" class="pierre-admin-button">
            Back to Dashboard 🪨
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-teams')); ?>" class="pierre-admin-button">
            Manage Teams 🪨
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=pierre-settings')); ?>" class="pierre-admin-button">
            Settings 🪨
        </a>
    </div>
</div>

<style>
.pierre-surveillance-status {
    margin-bottom: 20px;
}

.pierre-status-indicator {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 4px;
    font-weight: bold;
    margin-bottom: 10px;
}

.pierre-status-indicator.active {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.pierre-status-indicator.inactive {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.pierre-surveillance-controls {
    margin-top: 20px;
}

.pierre-surveillance-controls .pierre-admin-button {
    margin-right: 10px;
    margin-bottom: 10px;
}

.pierre-projects-list {
    max-height: 400px;
    overflow-y: auto;
}

.pierre-project-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    margin: 10px 0;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #2271b1;
}

.pierre-project-info strong {
    display: block;
    margin-bottom: 5px;
}

.pierre-project-meta {
    color: #666;
    font-size: 0.9em;
}

.pierre-add-project {
    margin-top: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 6px;
}

.pierre-bulk-actions {
    margin: 20px 0;
}

.pierre-bulk-actions .pierre-admin-button {
    margin-right: 10px;
    margin-bottom: 10px;
}

.pierre-project-stats {
    margin-top: 20px;
}

.pierre-stat-item {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    margin: 5px 0;
    background: #e7f3ff;
    border-radius: 4px;
}

.pierre-stat-label {
    font-weight: 500;
}

.pierre-stat-value {
    font-weight: bold;
    color: #2271b1;
}

.pierre-admin-actions {
    margin-top: 30px;
    text-align: center;
}

.pierre-admin-actions .pierre-admin-button {
    margin: 0 10px 10px 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Pierre handles project management! 🪨
    $('#pierre-start-surveillance').on('click', function() {
        alert('Pierre says: Starting surveillance! 🪨');
        // TODO: Implement actual surveillance start
    });
    
    $('#pierre-stop-surveillance').on('click', function() {
        if (confirm('Pierre asks: Are you sure you want to stop surveillance? 😢')) {
            alert('Pierre says: Surveillance stopped! 🪨');
            // TODO: Implement actual surveillance stop
        }
    });
    
    $('#pierre-test-surveillance').on('click', function() {
        alert('Pierre says: Testing surveillance system! 🪨');
        // TODO: Implement actual surveillance test
    });
    
    $('#pierre-add-project-form').on('submit', function(e) {
        e.preventDefault();
        var projectSlug = $('#project_slug').val();
        var localeCode = $('#locale_code').val();
        
        if (projectSlug && localeCode) {
            alert('Pierre says: Adding project ' + projectSlug + ' (' + localeCode + ') to watch list! 🪨');
            // TODO: Implement actual project addition
        }
    });
    
    $('.pierre-project-item button').on('click', function() {
        var project = $(this).data('project');
        var locale = $(this).data('locale');
        
        if (confirm('Pierre asks: Remove ' + project + ' (' + locale + ') from watch list? 😢')) {
            alert('Pierre says: Project removed from watch list! 🪨');
            // TODO: Implement actual project removal
        }
    });
    
    $('#pierre-refresh-all-projects').on('click', function() {
        location.reload();
    });
    
    $('#pierre-export-projects').on('click', function() {
        alert('Pierre says: Export feature coming soon! 🪨');
    });
});
</script>
