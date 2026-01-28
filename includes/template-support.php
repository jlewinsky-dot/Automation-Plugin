<?php
/**
 * Template registration and loading for ACF Automation plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

function ar_register_template_support(): void {

  // Adds "Automation Page" to the Page Template dropdown in WP.
  add_filter('theme_page_templates', static function ($templates) {
    $templates[AR_TPL_SLUG] = AR_TPL_LABEL;
    return $templates;
  });

  // If the current page is using our template, load our plugin template file.
  add_filter('template_include', static function ($template) {
    if (!is_singular('page')) return $template;

    $post_id = get_queried_object_id();
    if (!$post_id) return $template;

    $tpl = get_post_meta($post_id, '_wp_page_template', true);
    if ($tpl !== AR_TPL_SLUG) return $template;

    if (!file_exists(AR_TPL_PATH)) {
      error_log('[ACF Automation] Template file NOT found at: ' . AR_TPL_PATH);
      return $template;
    }

    return AR_TPL_PATH;
  }, 99);
}
