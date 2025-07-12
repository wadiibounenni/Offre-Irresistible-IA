<?php
if (!defined('ABSPATH')) exit;

function oiai_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Réglages – Offre Irrésistible AI</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('oiai_settings_group');
            do_settings_sections('oiai_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', function() {
    register_setting('oiai_settings_group', 'oiai_openai_api_key');
    add_settings_section('oiai_main_section', '', null, 'oiai_settings');
    add_settings_field(
        'oiai_openai_api_key',
        'Clé API OpenAI',
        function() {
            $value = esc_attr(get_option('oiai_openai_api_key'));
            echo '<input type="password" name="oiai_openai_api_key" value="' . $value . '" style="width:400px;">';
            echo '<p class="description">Votre clé API reste confidentielle et n\'est jamais partagée.</p>';
        },
        'oiai_settings',
        'oiai_main_section'
    );
});
