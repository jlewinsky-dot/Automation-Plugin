<?php
/**
 * SEO functionality for ACF Automation plugin
 * - Meta title and description
 * - LocalBusiness schema
 * - Meta keywords
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Title + meta description output (template pages only)
 * - Title: uses "meta_title" meta (or ACF field if present)
 * - Description: uses "meta_description" meta (or ACF field if present)
 */
function ar_register_meta_title_description(): void {
    add_filter('pre_get_document_title', 'ar_filter_document_title');
    add_action('wp_head', 'ar_output_meta_description', 5);
}

function ar_filter_document_title($title) {
    if (!is_singular('page')) {
        return $title;
    }
    $pid = get_queried_object_id();
    // only modify the title on pages using our template
    if (!$pid || !ar_is_our_template($pid)) {
        return $title;
    }
    // check post meta first, then fall back to ACF if nothing found
    $mt = (string) get_post_meta($pid, 'meta_title', true);
    if ($mt === '' && function_exists('get_field')) {
        $mt = (string) get_field('meta_title', $pid);
    }
    // use the meta title if we have one, otherwise use the default title
    return $mt !== '' ? wp_strip_all_tags($mt) : $title;
}

function ar_output_meta_description(): void {
    if (!is_singular('page')) {
        return;
    }
    $pid = get_queried_object_id();
    // only output the meta description on our template pages
    if (!$pid || !ar_is_our_template($pid)) {
        return;
    }
    // grab the description from meta or ACF, whichever has data
    $desc = (string) get_post_meta($pid, 'meta_description', true);
    if ($desc === '' && function_exists('get_field')) {
        $desc = (string) get_field('meta_description', $pid);
    }
    // remove any HTML tags
    $desc = wp_strip_all_tags($desc);
    // if we have nothing to say, don't output an empty meta tag
    if ($desc === '') {
        return;
    }
    // output the meta tag for search engines
    echo '<meta name="description" content="' . esc_attr($desc) . '" />' . "\n";
}

/** LocalBusiness schema output (template pages only) */
function ar_register_local_business_schema(): void {
    add_action('wp_head', 'ar_output_local_business_schema', 1);
}

function ar_output_local_business_schema(): void {
    if (!is_singular('page')) {
        return;
    }
    $post_id = get_queried_object_id();
    // only output structured data on our template pages
    if (!$post_id || !ar_is_our_template($post_id)) {
        return;
    }

    // gather business info for the schema
    $business_name = get_bloginfo('name');
    $phone         = get_post_meta($post_id, 'phone_number', true);
    $logo          = get_post_meta($post_id, 'logo', true);
    // collect all the services (there can be up to 8)
    $services      = [];

    for ($i = 1; $i <= 8; $i++) {
        $service = get_post_meta($post_id, "service_title_$i", true);
        // only include services that have a title
        if (!empty($service)) {
            $services[] = $service;
        }
    }

    // start building the schema structure
    // LocalBusiness schema helps Google understand what the business offers
    $structured_data = [
        '@context' => 'https://schema.org',
        '@type'    => 'LocalBusiness',
        'name'     => $business_name,
        'url'      => get_permalink($post_id),
    ];

    // add phone if available
    if ($phone) {
        $structured_data['telephone'] = $phone;
    }

    // add logo if available
    if ($logo) {
        $structured_data['logo'] = $logo;
    }

    // add services list if there are any
    if (!empty($services)) {
        $structured_data['hasOfferCatalog'] = [
            '@type'           => 'OfferCatalog',
            'name'            => 'Services',
            'itemListElement' => array_map(static function ($service, $index) {
                return [
                    '@type'    => 'Offer',
                    'name'     => $service,
                    'position' => $index + 1,
                ];
            }, $services, array_keys($services)),
        ];
    }

    // output the schema as a JSON-LD script tag
    // search engines parse this to better understand the business
    echo '<script type="application/ld+json">' . json_encode($structured_data, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}

function ar_register_meta_keywords_tag(): void {
    add_action('wp_head', static function (): void {
        if (!is_singular('page')) {
            return;
        }
        $pid = get_queried_object_id();
        // only output keywords on our template pages
        if (!$pid || !ar_is_our_template($pid)) {
            return;
        }

        // try to get keywords from ACF first
        $kw = '';
        if (function_exists('get_field')) {
            $val = get_field('keywords', $pid);
            if (is_string($val)) {
                $kw = $val;
            }
        }
        // fall back to post meta if ACF didn't have anything
        if ($kw === '') {
            $meta = get_post_meta($pid, 'keywords', true);
            if (is_string($meta)) {
                $kw = $meta;
            }
        }
        $kw = trim($kw);
        // skip if no keywords provided
        if ($kw === '') {
            return;
        }

        // split the keywords and clean them up
        // this removes HTML, trims whitespace, and filters out empty values
        $parts = array_filter(array_map(static function ($s) {
            return trim(wp_strip_all_tags((string) $s));
        }, explode(',', $kw)), static function ($s) {
            return $s !== '';
        });
        // bail if nothing is left after cleanup
        if (empty($parts)) {
            return;
        }
        // remove duplicates and rejoin with proper spacing
        $parts = array_values(array_unique($parts));
        $final = implode(', ', $parts);

        // output the meta tag for search engines
        echo '<meta name="keywords" content="' . esc_attr($final) . '" />' . "\n";
    }, 7);
}
