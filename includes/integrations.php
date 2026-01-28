<?php
/**
 * Third-party integrations for ACF Automation plugin
 * - Gravity Forms tweaks
 * - Cache invalidation
 */

if (!defined('ABSPATH')) {
    exit;
}

function ar_register_gravity_forms_tweaks(): void {
    add_filter('gform_init_scripts_footer', static function ($bool) {
        // move Gravity Forms scripts to the footer on our template pages
        // this helps with page load speed
        if (is_singular('page')) {
            $pid = get_queried_object_id();
            if ($pid && ar_is_our_template($pid)) {
                return true; // put it in the footer
            }
        }
        return $bool;
    });
}

function ar_register_template_cache_invalidation(): void {
    // clear cache whenever a page is saved
    add_action('save_post_page', 'ar_maybe_invalidate_template_cache', 20, 3);
}

function ar_maybe_invalidate_template_cache($post_id, $post, $update): void {
    // ignore autosaves and revisions; only clear on real saves
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }
    ar_purge_template_pages_transient();
}

function ar_purge_template_pages_transient(): void {
    // delete any cached list of automation template pages
    delete_transient('ar_tpl_pages_ids');
}
