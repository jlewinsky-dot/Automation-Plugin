<?php
/**
 * Utility functions for ACF Automation plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

// checking if template is automatoin page.
function ar_is_our_template(int $post_id): bool {
    return get_post_meta($post_id, '_wp_page_template', true) === AR_TPL_SLUG;
}

// Recurrsive value sanatizer using WordPress sanitize_text_field()
// Turns values into safe plain text
// Sanatizes all values before save
function ar_recursive_sanitize_meta_value($value) {
    if (is_array($value)) {
        foreach ($value as $k => $v) {
            $value[$k] = ar_recursive_sanitize_meta_value($v);
        }
        return $value;
    }

    if (is_object($value)) {
        return ar_recursive_sanitize_meta_value((array) $value);
    }

    if (is_scalar($value)) {
        return sanitize_text_field((string) $value);
    }

    if ($value === null) {
        return '';
    }

    $encoded = function_exists('wp_json_encode') ? wp_json_encode($value) : json_encode($value);
    return sanitize_text_field((string) $encoded);
}
