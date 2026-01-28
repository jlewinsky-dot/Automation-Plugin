<?php
/**
 * ACF field registration for ACF Automation plugin
 */

if (!defined('ABSPATH')) {
    exit;
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
