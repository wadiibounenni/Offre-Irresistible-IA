<?php
/*
Plugin Name: Offre Irrésistible IA
Description: Générez des offres irrésistibles dynamiques pour vendre vos produits/services grâce à l’IA.
Version: 1.0.0
Author: Wadii Bounenni
Author URI: https://wadii-bounenni.dev
Text Domain: offre-irresistible-ai
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Définir les chemins
if (!defined('OIAI_PLUGIN_DIR')) {
    define('OIAI_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('OIAI_PLUGIN_URL')) {
    define('OIAI_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Inclusions
require_once OIAI_PLUGIN_DIR . 'includes/settings-page.php';
// require_once OIAI_PLUGIN_DIR . 'includes/offer-generator-page.php'; // supprimé car le fichier n'existe plus
require_once OIAI_PLUGIN_DIR . 'includes/helpers.php';
require_once OIAI_PLUGIN_DIR . 'includes/openai-api.php';
require_once OIAI_PLUGIN_DIR . 'includes/ajax.php';

// Enqueue scripts & styles
add_action('admin_enqueue_scripts', function($hook) {
    if (strpos($hook, 'offre-irresistible') !== false) {
        wp_enqueue_style('offre-irresistible-admin', OIAI_PLUGIN_URL . 'assets/css/admin.css', [], '1.0');
        wp_enqueue_script('offre-irresistible-admin', OIAI_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], '1.0', true);
    }
});

// Ajouter menu admin
add_action('admin_menu', function() {
    // Main menu + first submenu (settings)
    add_menu_page(
        __('Offre Irrésistible IA', 'offre-irresistible-ai'),   // Main menu label
        __('Offre Irrésistible IA', 'offre-irresistible-ai'),   // Page title
        'manage_options',
        'offre-irresistible-settings',
        'oiai_render_settings_page',
        'dashicons-lightbulb',
        60
    );
    add_submenu_page(
        'offre-irresistible-settings',
        __('Clé API', 'offre-irresistible-ai'),     // Submenu label
        __('Clé API', 'offre-irresistible-ai'),
        'manage_options',
        'offre-irresistible-settings',
        'oiai_render_settings_page',
        1
    );
    add_submenu_page(
        'offre-irresistible-settings',
        __('Générateur d\'offre IA', 'offre-irresistible-ai'),
        __('Générateur d\'offre IA', 'offre-irresistible-ai'),
        'manage_options',
        'offre-irresistible-ia',
        'oiai_render_offer_ia_page',
        2
    );
    add_submenu_page(
        'offre-irresistible-settings',
        __('Audit Offre IA', 'offre-irresistible-ai'),
        __('Audit Offre IA', 'offre-irresistible-ai'),
        'manage_options',
        'offre-irresistible-audit',
        'oiai_render_offer_audit_page',
        3
    );
});

// Nouvelle page d'audit d'offre
function oiai_render_offer_audit_page() {
    echo '<div class="wrap"><h1>Audit Offre IA</h1>';
    include OIAI_PLUGIN_DIR . 'templates/offer-audit-form.php';
    echo '</div>';
}

// Page Générateur d'offre IA
function oiai_render_offer_ia_page() {
    echo '<div class="wrap"><h1>Générateur d\'offre IA</h1>';
    include OIAI_PLUGIN_DIR . 'templates/offer-ia-form.php';
    echo '</div>';
}
