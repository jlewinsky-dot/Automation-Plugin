<?php
/**
 * REST API endpoints for ACF Automation plugin
 */

if (!defined('ABSPATH')) {
    exit;
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

            $field_key = $meta_key; // what we'll store / report as "updated"
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
