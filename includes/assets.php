<?php
/**
 * Frontend asset management for ACF Automation plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

// calling CSS and JS only to pages that are using my template
// no need to load these assets on pages using other templates
function ar_register_frontend_assets(): void {
    add_action('wp_enqueue_scripts', static function () {
        if (!is_singular('page')) {
            return;
        }

        $post_id = get_queried_object_id();
        if (!$post_id || !ar_is_our_template($post_id)) {
            return;
        }

        // load the styles and scripts for this template
        // jQuery is a dependency since we need it for the JS functionality
        wp_enqueue_style('ar-automation-css', plugins_url('assets/automation.css', AR_PLUGIN_FILE), [], AR_AUTOMATION_VERSION);
        wp_enqueue_script('ar-automation-js', plugins_url('assets/automation.js', AR_PLUGIN_FILE), ['jquery'], AR_AUTOMATION_VERSION, true);
    });
}

// Adds a marker attribute to the CSS <link> tag so it can be identified later.
// this helps the style boundary function find and manage only our CSS
function ar_register_style_isolation(): void {
    add_filter('style_loader_tag', static function ($html, $handle) {
        $target_handles = ['ar-automation-css'];
        if (!in_array($handle, $target_handles, true)) {
            return $html;
        }

        if (strpos($html, 'data-ar-isolated') !== false) {
            return $html;
        }

        // inject the data-ar-isolated attribute so we can find it later
        return preg_replace('/rel=(["\'])stylesheet\1/', 'rel=$1stylesheet$1 data-ar-isolated="true"', $html, 1) ?: $html;
    }, 10, 2);
}

// Removes all non allowed CSS on my template pages to prevent theme/plugin style conflicts.
function ar_register_template_style_boundary(): void {
    add_action('wp_enqueue_scripts', static function (): void {
        if (!is_singular('page')) {
            return;
        }

        // checking to make sure page is using my template
        $post_id = get_queried_object_id();
        if (!$post_id || !ar_is_our_template((int) $post_id)) {
            return;
        }

        // pulls WordPress's global style manager into this function (tracks all CSS that has been enqued)
        global $wp_styles;
        if (!($wp_styles instanceof WP_Styles) || empty($wp_styles->queue)) {
            return;
        }

        // This is what is allowed
        $allowed = apply_filters('ar_template_style_allowlist', [
            'ar-automation-css',
            'dashicons',
            'admin-bar',
        ], $post_id);

        foreach ((array) $wp_styles->queue as $handle) {
            // check if this style is in the allowlist or is related to Gravity Forms
            // Gravity Forms needs to load for forms to work properly
            $is_allowed = in_array($handle, $allowed, true)
                || strpos($handle, 'gform') === 0
                || strpos($handle, 'gf_') === 0;

            if ($is_allowed) {
                continue; // keep this one
            }

            // remove everything else to prevent style conflicts
            wp_dequeue_style($handle);
        }
    }, 100); // runs late
}
