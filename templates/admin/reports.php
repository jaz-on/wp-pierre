<?php
/**
 * Pierre's admin reports template - he shows his surveillance reports! 🪨
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
    <h1>Pierre 🪨 Reports</h1>
    <p><?php echo esc_html__('Work in progress.', 'wp-pierre'); ?></p>

    <pre style="background:#fff;border:1px solid #ddd;padding:10px;max-width:900px;white-space:pre-wrap;">
TODO: Journal minimal (compteurs notifs envoyées/erreurs) – afficher dans Settings en attendant les vrais rapports
TODO: Rapport digest: aperçu des agrégations envoyées par locale (volumes, types, fenêtres)
TODO: Export CSV/JSON des derniers N événements (par locale, par projet)
TODO: Graphes de progression par palier (50/80/100) et temps pour franchir chaque palier
TODO: Historique des deltas (new_strings, approvals, warnings) sur période sélectionnable
TODO: Observabilité: Mini journal (compteurs envois/erreurs, dernier digest par locale) dans Reports.
TODO: Observabilité: Hook de log pour erreurs d’API (translate.w.org/Slack) avec horodatage.
    </pre>
</div>
