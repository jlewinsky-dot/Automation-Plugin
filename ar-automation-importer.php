<?php
/**
 * Plugin Name: ACF Automation
 * Description: Registers a WP Page Template; accepts ACF data via REST; template renders directly from ACF fields on each load with externalized scripts.
 * Version: 1.1.8
 * Author: Jaedon Lewinsky
 */


// if someone tries to run this PHP file not through WP, it kills the script
if (!defined('ABSPATH')) {
    exit;
}

// Setting global constants
ar_define_plugin_constants();

// Load all the plugin modules
ar_load_includes();

// for running all other functions
ar_bootstrap_automation_plugin();


function ar_define_plugin_constants(): void {
    if (!defined('AR_TPL_SLUG'))       define('AR_TPL_SLUG', 'template-automation-acf.php'); // Temaplate file wordpress stores
    if (!defined('AR_TPL_LABEL'))      define('AR_TPL_LABEL', 'Automation Page'); // Name seen in wordpress dropdown thingy
    if (!defined('AR_TPL_PATH'))       define('AR_TPL_PATH', plugin_dir_path(__FILE__) . 'template-automation-acf.php'); // Full server path
    if (!defined('AR_PLUGIN_FILE'))    define('AR_PLUGIN_FILE', __FILE__); // Reference to main plugin file

    if (!defined('AR_AUTOMATION_VERSION')) define('AR_AUTOMATION_VERSION', '1.1.8'); // Plugins version
    if (!defined('AR_ENABLE_CSP'))         define('AR_ENABLE_CSP', false); // On and off for my content security policy
}

function ar_load_includes(): void {
    $include_path = plugin_dir_path(__FILE__) . 'includes/';

    require_once $include_path . 'utilities.php';
    require_once $include_path . 'template-support.php';
    require_once $include_path . 'rest-api.php';
    require_once $include_path . 'assets.php';
    require_once $include_path . 'acf-fields.php';
    require_once $include_path . 'admin.php';
    require_once $include_path . 'seo.php';
    require_once $include_path . 'integrations.php';
}

// Main function to run all other functions
function ar_bootstrap_automation_plugin(): void {
    ar_register_template_support();
    ar_register_rest_endpoints();
    ar_register_frontend_assets();
    ar_register_style_isolation();
    ar_register_template_style_boundary();
    ar_register_acf_fields();
    ar_register_admin_ui();
    ar_register_editor_controls();

    ar_register_meta_title_description();
    ar_register_local_business_schema();
    ar_register_meta_keywords_tag();

    ar_register_gravity_forms_tweaks();
    ar_register_template_cache_invalidation();
}
