<?php
/**
 * Admin UI enhancements for ACF Automation plugin
 */

if (!defined('ABSPATH')) {
    exit;
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
