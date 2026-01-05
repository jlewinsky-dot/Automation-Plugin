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

// for running all other functions
ar_bootstrap_automation_plugin();

// Main function to run all other functions
function ar_bootstrap_automation_plugin(): void {
    ar_define_plugin_constants();
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

// Setting global constants
function ar_define_plugin_constants(): void {
    if (!defined('AR_TPL_SLUG'))       define('AR_TPL_SLUG', 'template-automation-acf.php'); // Temaplate file wordpress stores
    if (!defined('AR_TPL_LABEL'))      define('AR_TPL_LABEL', 'Automation Page'); // Name seen in wordpress dropdown thingy
    if (!defined('AR_TPL_PATH'))       define('AR_TPL_PATH', plugin_dir_path(__FILE__) . 'template-automation-acf.php'); // Full server path

    if (!defined('AR_AUTOMATION_VERSION')) define('AR_AUTOMATION_VERSION', '1.1.8'); // Plugins version
    if (!defined('AR_ENABLE_CSP'))         define('AR_ENABLE_CSP', false); // On and off for my content security policy
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


function ar_register_template_support(): void {

  // Adds “Automation Page” to the Page Template dropdown in WP.
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


function ar_register_rest_endpoints(): void {

  // When WP is setting up the REST API, register our endpoint.
  add_action('rest_api_init', static function () {

    // Creates /wp-json/automation/v1/push
    register_rest_route('automation/v1', '/push', [
      'methods' => 'POST',

      // Only allow logged-in users who can edit pages. Need valid auth
      'permission_callback' => static function () {
        return current_user_can('edit_pages');
      },

      // This runs when endpoint is hit
      'callback' => static function (WP_REST_Request $req) {

        // Read JSON body into an array - just the payload from my python script
        $data = $req->get_json_params();

        // Pull out basic page info from the payload.
        $post_id_input = isset($data['post_id']) ? absint($data['post_id']) : 0;                 // page id to update (optional)
        $title_input   = isset($data['title']) ? sanitize_text_field($data['title']) : '';       // new title
        $slug_input    = isset($data['slug']) ? sanitize_title($data['slug']) : '';              // new slug

        // Only allow certain statuses.
        $raw_status   = isset($data['status']) ? sanitize_key($data['status']) : '';
        $valid_status = ['publish', 'draft', 'pending', 'private'];
        $status       = in_array($raw_status, $valid_status, true) ? $raw_status : '';

        // Grab the acf object from the payload.
        // Supports it being sent either as an array OR as a JSON string.
        $acf_payload = $data['acf'] ?? [];
        if (is_string($acf_payload)) {
          $decoded = json_decode($acf_payload, true);
          if (is_array($decoded)) {
            $acf_payload = $decoded;
          }
        }
        if (!is_array($acf_payload)) {
          $acf_payload = [];
        }

        // Sanitize every field name + value before saving.
        // - keys: forced into safe meta keys
        // - values: recursively sanitized (strings cleaned/ arrays cleaned item by item)
        $sanitized_payload = [];
        foreach ($acf_payload as $raw_key => $raw_value) {
          $clean_key = sanitize_key((string) $raw_key);
          if ($clean_key === '') continue;

          $sanitized_payload[$clean_key] = ar_recursive_sanitize_meta_value($raw_value);
        }
        $acf_payload = $sanitized_payload;
        // now we have a clean, safe payload ready to save

        // assign the template to the page unless caller disables it.
        $set_template = array_key_exists('set_template', $data) ? (bool) $data['set_template'] : true;

        $created = false; // did we create a brand new page?
        $post_id = 0;     // later will be replaced by the actual page ID

        // CASE A: caller gave us a post_id, we then use taht post ID to update that exact page with the same ID.
        if ($post_id_input > 0) {

          // Make sure the page exists and it actually a page
          $target = get_post($post_id_input);
          if (!$target || $target->post_type !== 'page') {
            return new WP_REST_Response(['ok' => false, 'error' => __('Target page not found.', 'automation-renderer')], 404);
          }

          // Permission check. User must be allowed to edit this post ID
          // If not, stops and returns a rest response
          if (!current_user_can('edit_post', $post_id_input)) {
            return new WP_REST_Response(['ok' => false, 'error' => __('You are not allowed to edit this page.', 'automation-renderer')], 403);
          }

          // Build the fields we want to update.
          $postarr = ['ID' => $post_id_input];
          if ($title_input !== '') $postarr['post_title'] = $title_input;
          if ($slug_input  !== '') $postarr['post_name']  = $slug_input;
          if ($status     !== '') $postarr['post_status'] = $status;

          // Only call wp_update_post if we actually changed something.
          if (count($postarr) > 1) {
            $result = wp_update_post(wp_slash($postarr), true);
            if (is_wp_error($result)) {
              return new WP_REST_Response(['ok' => false, 'error' => $result->get_error_message()], 500);
            }
            $post_id = (int) $result;
          } else {
            $post_id = $post_id_input;
          }

        } else {
          // CASE B: no post_id, but finding existing by slug or title, otherwise create new.

          $slug_candidate = $slug_input !== '' ? $slug_input : sanitize_title($title_input);
          $existing = $slug_candidate ? get_page_by_path($slug_candidate, OBJECT, 'page') : null;

          if ($existing) {
            // Found an existing page with that slug, and then update it.
            $post_id = (int) $existing->ID;

            if (!current_user_can('edit_post', $post_id)) {
              return new WP_REST_Response(['ok' => false, 'error' => __('You are not allowed to edit this page.', 'automation-renderer')], 403);
            }

            $postarr = ['ID' => $post_id];
            if ($title_input !== '') $postarr['post_title'] = $title_input;
            if ($status     !== '') $postarr['post_status'] = $status;

            if (count($postarr) > 1) {
              $result = wp_update_post(wp_slash($postarr), true);
              if (is_wp_error($result)) {
                return new WP_REST_Response(['ok' => false, 'error' => $result->get_error_message()], 500);
              }
            }

          } else {
            // No existing page -> create one.
            if ($title_input === '') {
              return new WP_REST_Response(['ok' => false, 'error' => __('Title is required when creating a page.', 'automation-renderer')], 400);
            }

            if (!current_user_can('publish_pages')) {
              return new WP_REST_Response(['ok' => false, 'error' => __('You are not allowed to create pages.', 'automation-renderer')], 403);
            }

            // Pick a slug; if still empty, generate a random one.
            $post_slug = $slug_candidate !== '' ? $slug_candidate : sanitize_title($title_input);
            if ($post_slug === '') {
              $post_slug = 'automation-page-' . strtolower(wp_generate_password(6, false, false));
            }

            $postarr = [
              'post_title'  => $title_input,
              'post_name'   => $post_slug,
              'post_status' => $status !== '' ? $status : 'publish',
              'post_type'   => 'page',
            ];

            $result = wp_insert_post(wp_slash($postarr), true);
            if (is_wp_error($result)) {
              return new WP_REST_Response(['ok' => false, 'error' => $result->get_error_message()], 500);
            }

            $post_id  = (int) $result;
            $created  = true;
          }
        }

        // By this point, we should have updated an existing page or created one. 
        // If not, something went wrong so it stops and returns a 500 server error
        if (!$post_id) {
          return new WP_REST_Response(['ok' => false, 'error' => __('Unable to resolve a page for this request.', 'automation-renderer')], 500);
        }

        // If set template is true, assigns my custom template to pag
        if ($set_template) {
          update_post_meta($post_id, '_wp_page_template', AR_TPL_SLUG);
          ar_purge_template_pages_transient(); // clears cached list of template pages
        }


        $updated_fields = [];

        if (!empty($acf_payload)) {

        foreach ($acf_payload as $meta_key => $value) {

            $field_key = $meta_key; // what we’ll store / report as “updated”
            $saved_via_acf = false;

            // Try ACF first (if ACF is installed)
            if (function_exists('update_field')) {

            // 1) Try saving using the key exactly as provided (could be field name OR field_XXXX)
            $result = update_field($meta_key, $value, $post_id);

            if ($result !== false && $result !== null) {
                $saved_via_acf = true;

            } else {
                // 2) If someone sent a field key like "field_abc123", resolve it to the real field name
                $is_field_key = (strpos($meta_key, 'field_') === 0);

                if ($is_field_key && function_exists('acf_get_field')) {
                $definition = acf_get_field($meta_key);

                if (!empty($definition['name'])) {
                    $field_name = (string) $definition['name'];

                    $result2 = update_field($field_name, $value, $post_id);

                    if ($result2 !== false && $result2 !== null) {
                    $saved_via_acf = true;

                    // Report/store the friendly field name instead of the "field_..." key
                    $resolved_key = sanitize_key($field_name);
                    if ($resolved_key !== '') {
                        $field_key = $resolved_key;
                    }
                    }
                }
                }
            }
            }

    // BACKUPPPP: If ACF didn't save it (or ACF isn't installed), store as normal WP post meta
    // this ensures data is always saved, even if ACF isn't available
    if (!$saved_via_acf) {
      update_post_meta($post_id, $field_key, $value);
    }

    // track which fields we updated so we can report it back
    $updated_fields[] = $field_key;
  }

  error_log('[ACF Automation] Synced custom fields for post #' . $post_id . '.');
}


        // Save meta title/description
        // check both top-level data and acf payload, use whichever is provided
        $meta_title       = $data['meta_title'] ?? ($acf_payload['meta_title'] ?? null);
        $meta_desc        = $data['meta_description'] ?? ($acf_payload['meta_description'] ?? null);
        $meta_title_saved = false;
        $meta_desc_saved  = false;

        if (is_string($meta_title) && trim($meta_title) !== '') {
          // clean up any HTML tags before saving
          $mt = wp_strip_all_tags($meta_title);
          update_post_meta($post_id, 'meta_title', $mt);
          $meta_title_saved = true;
          $updated_fields[] = 'meta_title';
        }

        if (is_string($meta_desc) && trim($meta_desc) !== '') {
          // same here—strip tags from the description
          $md = wp_strip_all_tags($meta_desc);
          update_post_meta($post_id, 'meta_description', $md);
          $meta_desc_saved = true;
          $updated_fields[] = 'meta_description';
        }

        // Optional: allow arbitrary meta writes via data["meta"].
        // this lets the Python script save any custom meta fields outside of ACF
        if (!empty($data['meta']) && is_array($data['meta'])) {
          foreach ($data['meta'] as $m_key => $m_val) {
            if (!is_string($m_key) || $m_key === '') continue;
            // save each custom meta field directly
            update_post_meta($post_id, $m_key, $m_val);
            $updated_fields[] = $m_key;
          }
        }

        // Clear caches so WP will serve fresh.
        // without this, WordPress might serve stale data to visitors
        clean_post_cache($post_id);

        // Return a JSON response describing what happened.
        // the Python script uses this to confirm the update worked
        return new WP_REST_Response([
          'ok'                => true,
          'post_id'           => $post_id,
          'url'               => get_permalink($post_id),
          'created'           => $created,
          'action'            => $created ? 'created' : 'updated',
          'template_assigned' => $set_template,
          'acf_count'         => count($acf_payload),
          'updated_fields'    => array_values(array_unique($updated_fields)),
          'meta_title_saved'  => $meta_title_saved,
          'meta_desc_saved'   => $meta_desc_saved,
          'status'            => get_post_status($post_id),
          'title'             => get_the_title($post_id),
          'slug'              => get_post_field('post_name', $post_id),
        ]);
      },
    ]);
  });
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
        wp_enqueue_style('ar-automation-css', plugins_url('assets/automation.css', __FILE__), [], AR_AUTOMATION_VERSION);
        wp_enqueue_script('ar-automation-js', plugins_url('assets/automation.js', __FILE__), ['jquery'], AR_AUTOMATION_VERSION, true);
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

function ar_register_acf_fields(): void {
    add_action('acf/init', function () {
        // only proceed if ACF is installed and ready
        if (!function_exists('acf_add_local_field_group')) return;

        // collect all fields in this array, then register them all at once
        $F = [];

        // helper function to quickly add a field to our array
        $add = function(&$F, $name, $label = null, $type = 'text', $extra = []) {
            $F[] = array_merge([
                'key' => 'field_ar_' . $name,
                'label' => $label ?: ucwords(str_replace(['_', 'url'], [' ', 'URL'], $name)),
                'name' => $name,
                'type' => $type,
                'wrapper' => ['width' => '33'],
            ], $extra);
        };
        // helper to organize fields into visual tabs (like sections in the admin)
        $tab = function(&$F, $label, $icon = '') {
            $F[] = [
                'key' => 'tab_ar_' . sanitize_title($label),
                'label' => ($icon ? $icon . ' ' : '') . $label,
                'type'  => 'tab',
                'placement' => 'top',
            ];
        };
        // helper to add informational messages (helps editors understand what each section is for)
        $msg = function(&$F, $name, $label, $message, $style='info') {
            $F[] = [
                'key' => 'msg_ar_' . $name,
                'label' => $label,
                'name'  => 'msg_' . $name,
                'type'  => 'message',
                'message' => $message,
                'new_lines' => 'wpautop',
                'esc_html'  => 0,
                'wrapper'   => ['width'=>'100'],
            ];
        };

        /* Hero */
        $tab($F,'Hero');
        $msg($F,'hero_intro','Hero Section', '<strong>Hero:</strong> Brand, phone, headline & form embed.');
        $add($F,'phone_number','Phone Number');
        $add($F,'logo','Logo URL','url');
        $add($F,'h3_home');
        $add($F,'heading_home');
        $add($F,'h2_home');
        $add($F,'link_quote','Quote Button URL','url');
        $add($F,'form','Hero Form Shortcode','text',["wrapper"=>['width'=>'33'], 'instructions'=>'Enter full GF shortcode e.g. [gravityform id="1" title="false" description="false" ajax="true"]']);
        $add($F,'form2','Bottom Form Shortcode','text',["wrapper"=>['width'=>'33'], 'instructions'=>'Optional secondary form displayed near page bottom.']);
        $add($F,'form3','Popup Form Shortcode','text',["wrapper"=>['width'=>'33'], 'instructions'=>'Form used inside the Get Quote popup modal.']);

        /* Section 3 (Triplet) */
        $tab($F,'Section 3');
        foreach ([1,2,3] as $i) {
            $add($F,"h3_section_3_$i","H3 #$i");
            $add($F,"p_section_3_$i","Paragraph #$i",'textarea',['wrapper'=>['width'=>'66']]);
        }

        /* Section 2 */
        $tab($F,'Section 2');
        $add($F,'h2_section_2','Heading');
        $add($F,'p_section_2','Paragraph','textarea',['wrapper'=>['width'=>'66']]);
        $add($F,'section_2_img','Image URL','url');
        $add($F,'section_2_img_alt','Image Alt');

        /* Product Cards */
        $tab($F,'Product Cards');
        foreach ([1,2,3] as $i) {
            $add($F,"card{$i}_title","Card {$i} Title");
            $add($F,"card{$i}_image_url","Card {$i} Image URL",'url');
            $add($F,"card{$i}_image_alt","Card {$i} Image Alt");
            $add($F,"card{$i}_description","Card {$i} Description",'textarea',['wrapper'=>['width'=>'100']]);
        }

        /* Services (1–8) */
        $tab($F,'Services');
        foreach (range(1,8) as $i) {
            $add($F,"service_title_$i","Title $i");
            $add($F,"service_description_$i","Description $i",'textarea',['wrapper'=>['width'=>'66']]);
        }

        /* Testimonials */
        $tab($F,'Testimonials');
        foreach ([1,2,3] as $i) {
            $add($F,"testimonial_text_$i","Text $i",'textarea',['wrapper'=>['width'=>'66']]);
            $add($F,"testimonial_name_$i","Name $i");
        }

        /* Section 5 */
        $tab($F,'Section 5');
        $add($F,'h2_section_5','Heading');
        $add($F,'p_section_5_1','Paragraph 1','textarea',['wrapper'=>['width'=>'66']]);
        $add($F,'section_5_img','Image URL','url');
        $add($F,'section_5_img_alt','Image Alt');
        $add($F,'p_section_5_5','Paragraph 2','textarea',['wrapper'=>['width'=>'50']]);
        $add($F,'p_section_5_6','Paragraph 3','textarea',['wrapper'=>['width'=>'50']]);

        /* Section 6 */
        $tab($F,'Section 6');
        $add($F,'p_section_6','Paragraph 1','textarea',['wrapper'=>['width'=>'33']]);
        $add($F,'p_section_6_2','Paragraph 2','textarea',['wrapper'=>['width'=>'33']]);
        $add($F,'p_section_6_3','Paragraph 3','textarea',['wrapper'=>['width'=>'33']]);

        /* Section 4 (Service Areas) */
        $tab($F,'Service Areas');
        $add($F,'p_section_4','Intro Paragraph','textarea',['wrapper'=>['width'=>'100']]);
        $add($F,'section_4_img','Image URL','url');
        $add($F,'section_4_img_alt','Image Alt');
        foreach (range(1,30) as $i) {
            $add($F,"service_area_{$i}","Area $i");
            $add($F,"service_area_{$i}_url","Area $i URL",'url');
        }

        /* Section 7 (Collapsibles) */
        $tab($F,'FAQs');
        $add($F,'h2_section_7','Section Heading');
        foreach ([1,2,3] as $i) {
            $add($F,"collapsible_title_$i","FAQ Title $i");
            $add($F,"collapsible_content_$i","FAQ Content $i",'textarea',['wrapper'=>['width'=>'66']]);
        }
        $add($F,'collapsible_content_4','FAQ Content 4 (Fixed Title)','textarea',['wrapper'=>['width'=>'66']]);

        /* Partners */
        $tab($F,'Partners');
        foreach (range(1,4) as $i) {
            $add($F,"Partner_{$i}","Partner $i Name");
            $add($F,"Partner_{$i}_url","Partner $i URL",'url');
        }

        /* Meta Data */
        $tab($F,'Meta Data');
        $add($F,'meta_title','Meta Title','text',[ 'wrapper'=>['width'=>'34'] ]);
        $add($F,'meta_description','Meta Description','textarea',[ 'wrapper'=>['width'=>'66'], 'rows'=>3, 'new_lines'=>'br' ]);
        $add($F,'keywords','Meta Keywords (comma-separated)','text',[ 'wrapper'=>['width'=>'34'] ]);

        // register all the fields at once
        // only show these fields on pages using the automation template
        acf_add_local_field_group([
            'key' => 'group_ar_automation_all_fields',
            'title' => 'Automation Page Fields',
            'fields' => $F,
            'location' => [[
                [
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => AR_TPL_SLUG,
                ],
            ]],
            'position' => 'normal',
            'style' => 'seamless',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'active' => true,
        ]);
    });
}

function ar_register_admin_ui(): void {
    add_action('admin_head', static function (): void {
        global $pagenow;
        // only enhance the edit page screens, not new page creation
        if (!in_array($pagenow, ['post.php', 'post-new.php'], true)) {
            return;
        }
        // need to have a post ID to do anything
        if (empty($_GET['post'])) {
            return;
        }
        $post_id = (int) $_GET['post'];
        // only show the enhanced UI on pages using our template
        if (!$post_id || !ar_is_our_template($post_id)) {
            return;
        }
        ?>
        <!-- styles for the section navigation sidebar -->
        <style>
            .ar-acf-nav {
                position: sticky;
                top: 70px;
                float: right;
                width: 180px;
                margin: 0 0 0 20px;
                background: #fff;
                border: 1px solid #d0d7de;
                border-radius: 8px;
                padding: 8px 10px;
                font-size: 12px;
                line-height: 1.35;
                box-shadow: 0 2px 4px rgba(0, 0, 0, .05);
            }
            .ar-acf-nav h4 { margin: 4px 0 6px; font-size: 13px; font-weight: 600; }
            .ar-acf-nav a {
                display: block;
                padding: 4px 6px;
                border-radius: 4px;
                text-decoration: none;
                color: #1d2327;
            }
            .ar-acf-nav a:hover,
            .ar-acf-nav a.is-active { background: #2271b1; color: #fff; }
            .acf-field-tab {
                background: #f6f8fa;
                border: 1px solid #d0d7de;
                border-radius: 6px;
                margin-top: 28px;
                padding: 10px 14px;
                font-size: 15px;
                font-weight: 600;
                position: relative;
            }
            .acf-field-tab:first-of-type { margin-top: 8px; }
            .acf-field[data-name] {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                padding: 10px 14px 6px;
                margin: 8px 0;
                transition: border-color .15s, box-shadow .15s;
            }
            .acf-field[data-name]:hover {
                border-color: #b4c3d1;
                box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
            }
            .acf-field .acf-label label {
                font-weight: 600;
                font-size: 12.5px;
                text-transform: uppercase;
                letter-spacing: .5px;
                color: #334155;
            }
            .acf-field textarea,
            .acf-field input[type="text"],
            .acf-field input[type="url"] {
                font-family: inherit;
            }
            .acf-field textarea {
                min-height: 80px;
                resize: vertical;
            }
            #poststuff #acf-group_ar_automation_all_fields.acf-postbox {
                max-width: 1200px;
            }
            .acf-fields.-top > .acf-field { border-top: none !important; }
            .acf-field-message {
                border: 1px solid #d0d7de;
                background: linear-gradient(135deg, #f0f6ff, #f8fafc);
                border-radius: 6px;
            }
            .acf-field-message .acf-label label {
                font-size: 14px;
                color: #0f172a;
            }
            .acf-field-message .acf-input p {
                margin: 6px 0 4px;
                font-size: 13px;
            }
            #publishing-action input[type=submit] {
                box-shadow: 0 2px 4px rgba(0, 0, 0, .12);
            }
            .notice, .update-nag { max-width: 1200px; }
            @media (max-width: 1300px) {
                .ar-acf-nav { display: none; }
            }
        </style>
        <!-- JavaScript to build a dynamic navigation sidebar for jumping between sections -->
        <script>
        (function(){
            // find all the tab headers (section titles) in the field group
            const tabButtons = document.querySelectorAll('.acf-field-tab');
            if (!tabButtons.length) return; // nothing to do if there are no tabs

            // build the sidebar navigation element
            const nav = document.createElement('div');
            nav.className = 'ar-acf-nav';
            nav.innerHTML = '<h4>Sections</h4>';
            tabButtons.forEach(tb => {
                // extract the section title text
                const raw = tb.querySelector('.acf-tab-button')?.textContent || tb.querySelector('span')?.textContent || 'Section';
                const label = raw.trim();
                // generate a safe ID for linking to this section
                const id = 'ar-tab-' + label.toLowerCase().replace(/[^a-z0-9]+/g, '-');
                tb.setAttribute('data-ar-tab-id', id);
                // create a clickable link for the sidebar
                const a = document.createElement('a');
                a.href = '#' + id;
                a.textContent = label;
                a.addEventListener('click', e => {
                    e.preventDefault();
                    // scroll smoothly to the section
                    tb.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    // highlight this link in the sidebar
                    document.querySelectorAll('.ar-acf-nav a').forEach(x => x.classList.remove('is-active'));
                    a.classList.add('is-active');
                });
                nav.appendChild(a);
            });
            });
            // add the sidebar to the page
            const target = document.querySelector('#postbox-container-2') || document.querySelector('#post-body-content');
            if (target) target.prepend(nav);

            // auto-highlight the sidebar link as the editor scrolls through sections
            const onScroll = () => {
                let current = null;
                // find which section is currently visible
                tabButtons.forEach(tb => {
                    const r = tb.getBoundingClientRect();
                    // top < 140 means it's near the top of the screen
                    if (r.top < 140) current = tb;
                });
                if (current) {
                    const id = current.getAttribute('data-ar-tab-id');
                    // update the sidebar to highlight the current section
                    document.querySelectorAll('.ar-acf-nav a').forEach(a => {
                        a.classList.toggle('is-active', a.getAttribute('href') === '#' + id);
                    });
                }
            };
            // listen for scroll events and update the highlighting
            document.addEventListener('scroll', onScroll, { passive: true });
            // also run it once on page load
            onScroll();
        })();
        </script>
        <?php
    });
}

function ar_register_editor_controls(): void {
    add_action('admin_init', static function (): void {
        // only do this if editing a page
        if (!isset($_GET['post'])) {
            return;
        }
        $post_id = (int) $_GET['post'];
        // make sure it's really using our template
        if (!$post_id || get_post_type($post_id) !== 'page' || !ar_is_our_template($post_id)) {
            return;
        }
        // hide the default WordPress editor; use ACF fields instead
        remove_post_type_support('page', 'editor');
    });
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
