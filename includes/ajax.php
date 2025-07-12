<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_oiai_generate_offer', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Non autorisé.');
    }
    $product_name = sanitize_text_field($_POST['oiai_product_name'] ?? '');
    $answers = [];
    if (isset($_POST['oiai_answers']) && is_array($_POST['oiai_answers'])) {
        foreach ($_POST['oiai_answers'] as $arr) {
            if (is_array($arr)) {
                $merged = array_map('sanitize_text_field', $arr);
                $answers[] = implode(' / ', array_filter($merged));
            } else {
                $answers[] = sanitize_text_field($arr);
            }
        }
    }
    require_once OIAI_PLUGIN_DIR . 'includes/openai-api.php';
    $offer = oiai_generate_offer($answers, $product_name);
    wp_send_json_success($offer);
});

// AJAX pour générer dynamiquement les réponses adaptées au produit/service
add_action('wp_ajax_oiai_generate_dynamic_answers', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Non autorisé.');
    }
    $product_name = sanitize_text_field($_POST['oiai_product_name'] ?? '');
    require_once OIAI_PLUGIN_DIR . 'includes/helpers.php';
    require_once OIAI_PLUGIN_DIR . 'includes/openai-api.php';
    $questions = oiai_get_offer_questions();
    $dynamic_answers = [];
    foreach ($questions as $q) {
        $suggestions = oiai_generate_dynamic_answers_for_question($product_name, $q['label'], $q['desc']);
        $dynamic_answers[] = [
            'label' => $q['label'],
            'desc' => $q['desc'],
            'options' => $suggestions,
        ];
    }
    wp_send_json_success($dynamic_answers);
});

// AJAX pour audit d'offre IA
add_action('wp_ajax_oiai_audit_offer', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Non autorisé.');
    }
    $offer = trim(stripslashes($_POST['oiai_audit_offer_text'] ?? ''));
    $tone = sanitize_text_field($_POST['oiai_audit_tone'] ?? 'Neutre');
    if (empty($offer)) {
        wp_send_json_error("Aucune offre à auditer.");
    }
    require_once OIAI_PLUGIN_DIR . 'includes/openai-api.php';
    $result = oiai_audit_offer($offer, $tone);
    wp_send_json_success($result);
});

// AJAX pour suggestions de bonus bundle
add_action('wp_ajax_oiai_bundle_bonus_suggestions', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Non autorisé.');
    }
    $product = sanitize_text_field($_POST['oiai_bundle_product'] ?? '');
    $price = sanitize_text_field($_POST['oiai_bundle_price'] ?? '');
    if (empty($product) || empty($price)) {
        wp_send_json_error("Produit ou prix manquant.");
    }
    require_once OIAI_PLUGIN_DIR . 'includes/openai-api.php';
    $result = oiai_bundle_bonus_suggestions($product, $price);
    wp_send_json_success($result);
});

// AJAX pour génération du texte marketing du bundle
add_action('wp_ajax_oiai_bundle_marketing_text', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Non autorisé.');
    }
    $product = sanitize_text_field($_POST['oiai_bundle_product'] ?? '');
    $price = sanitize_text_field($_POST['oiai_bundle_price'] ?? '');
    $type = sanitize_text_field($_POST['oiai_bundle_type'] ?? '');
    $bonuses = json_decode(stripslashes($_POST['oiai_bundle_bonus'] ?? '[]'), true);
    $prix_normal = sanitize_text_field($_POST['oiai_bundle_prix_normal'] ?? '');
    $prix_final = sanitize_text_field($_POST['oiai_bundle_prix_final'] ?? '');
    require_once OIAI_PLUGIN_DIR . 'includes/openai-api.php';
    $result = oiai_bundle_marketing_text($product, $price, $type, $bonuses, $prix_normal, $prix_final);
    wp_send_json_success($result);
});

// AJAX pour Générateur d'offre IA (mixte)
add_action('wp_ajax_oiai_generate_offer_ia', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Non autorisé.');
    }
    $product = sanitize_text_field($_POST['oiai_ia_product'] ?? '');
    $price = sanitize_text_field($_POST['oiai_ia_price'] ?? '');
    $type = sanitize_text_field($_POST['oiai_ia_type'] ?? '');
    $answers = json_decode(stripslashes($_POST['oiai_ia_answers'] ?? '[]'), true);
    $bonuses = json_decode(stripslashes($_POST['oiai_ia_bonus'] ?? '[]'), true);
    $prix_normal = sanitize_text_field($_POST['oiai_ia_prix_normal'] ?? '');
    $prix_final = sanitize_text_field($_POST['oiai_ia_prix_final'] ?? '');
    require_once OIAI_PLUGIN_DIR . 'includes/openai-api.php';
    $result = oiai_generate_offer_ia($product, $price, $type, $answers, $bonuses, $prix_normal, $prix_final);
    wp_send_json_success($result);
});
