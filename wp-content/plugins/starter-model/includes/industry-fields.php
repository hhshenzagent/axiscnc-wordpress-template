<?php
namespace Starter\Model;
defined('ABSPATH') || exit;

/** One complete editor for the default industry solution template. */
function register_industry_fields(callable $field, callable $tab): void {
    $pairs = static function (string $key, string $name, string $label, string $first, string $second, int $max = 12) use ($field): array {
        return $field($key, $name, $label, 'repeater', [
            'layout' => 'table', 'max' => $max, 'button_label' => 'Add item', 'collapsed' => $key . '_label',
            'instructions' => 'Add, remove and drag to reorder. Only complete rows appear on the website.',
            'sub_fields' => [
                $field($key . '_label', 'label', $first, 'text', ['instructions' => '']),
                $field($key . '_value', 'value', $second, 'textarea', ['rows' => 2, 'instructions' => '']),
            ],
        ]);
    };
    acf_add_local_field_group([
        'key' => 'group_starter_industry', 'title' => 'Industry details', 'show_in_rest' => true,
        'label_placement' => 'top', 'instruction_placement' => 'label',
        'fields' => [
            $tab('field_i_tab_intro', 'tab_industry_intro', 'Overview'),
            $field('field_i_challenge', 'challenge', 'Site constraints introduction (optional)', 'textarea', ['rows' => 3]),
            $field('field_i_constraints_title', 'industry_constraints_title', 'Site constraints heading', 'text', ['default_value' => 'Start with the site constraints']),
            $pairs('field_i_constraints', 'industry_constraints', 'Site constraints', 'Constraint', 'What to consider', 6),
            $tab('field_i_tab_body', 'tab_industry_body', 'Applications'),
            $field('field_i_applications_title', 'industry_applications_title', 'Applications heading', 'text', ['default_value' => 'A solution shaped around your work']),
            $field('field_i_modules', 'industry_modules', 'Media & text modules', 'repeater', [
                'layout' => 'block', 'max' => 12, 'button_label' => 'Add application module', 'collapsed' => 'field_i_module_title',
                'instructions' => 'Choose an image or uploaded video, edit the content and drag to reorder. Empty modules are hidden.',
                'sub_fields' => [
                    $field('field_i_module_title', 'title', 'Module title', 'text'),
                    $field('field_i_module_type', 'media_type', 'Media type', 'button_group', ['choices' => ['image' => 'Image', 'video' => 'Video'], 'default_value' => 'image']),
                    $field('field_i_module_image', 'image', 'Image', 'image', ['return_format' => 'id', 'preview_size' => 'medium', 'instructions' => 'Use a landscape photograph showing this application. Recommended ratio: 3:2.', 'conditional_logic' => [[['field' => 'field_i_module_type', 'operator' => '!=', 'value' => 'video']]]]),
                    $field('field_i_module_video', 'video', 'Uploaded video', 'file', ['return_format' => 'id', 'mime_types' => 'mp4,webm,ogv', 'instructions' => 'Choose a browser-playable video. MP4 (H.264) is recommended. Playback uses controls; no autoplay.', 'conditional_logic' => [[['field' => 'field_i_module_type', 'operator' => '==', 'value' => 'video']]]]),
                    $field('field_i_module_poster', 'video_poster', 'Video cover (optional)', 'image', ['return_format' => 'id', 'preview_size' => 'medium', 'conditional_logic' => [[['field' => 'field_i_module_type', 'operator' => '==', 'value' => 'video']]]]),
                    $field('field_i_module_body', 'body', 'Content', 'wysiwyg', ['tabs' => 'all', 'toolbar' => 'full', 'media_upload' => 1]),
                    $field('field_i_module_position', 'image_position', 'Media position', 'select', ['choices' => ['auto' => 'Alternate automatically', 'left' => 'Media on the left', 'right' => 'Media on the right'], 'default_value' => 'auto']),
                    $field('field_i_module_link', 'link', 'Optional link', 'link', ['return_format' => 'array']),
                ],
            ]),
            $field('field_i_body', 'industry_body', 'Additional solution details (optional)', 'wysiwyg', ['tabs' => 'all', 'toolbar' => 'full', 'media_upload' => 1, 'instructions' => 'A complete rich text editor for additional technical details. Existing solution content remains here. Clear it to hide this reading section.']),
            $tab('field_i_tab_equipment', 'tab_industry_equipment', 'Equipment'),
            $field('field_i_products_title', 'industry_products_title', 'Equipment heading', 'text', ['default_value' => 'Compare equipment for this application']),
            $field('field_i_products_intro', 'industry_products_intro', 'Equipment introduction (optional)', 'textarea', ['rows' => 3]),
            $field('field_i_products', 'industry_products', 'Products for this application', 'relationship', [
                'post_type' => ['starter_product'], 'post_status' => ['publish'], 'return_format' => 'id', 'filters' => ['search'], 'max' => 12,
                'instructions' => 'Choose relevant products and drag to reorder. Leave empty to hide the section and its navigation link. Only published products appear.',
            ]),
            $tab('field_i_tab_planning', 'tab_industry_planning', 'Planning'),
            $field('field_i_planning_title', 'industry_planning_title', 'Planning heading', 'text', ['default_value' => 'Prepare your application brief']),
            $field('field_i_planning_intro', 'industry_planning_intro', 'Planning introduction (optional)', 'textarea', ['rows' => 3]),
            $pairs('field_i_steps', 'industry_steps', 'Preparation steps', 'Step', 'Description', 8),
            $pairs('field_i_planning_rows', 'industry_planning_rows', 'Planning table', 'Planning item', 'Information to share', 20),
            $field('field_i_note', 'industry_note', 'Application note (optional)', 'text'),
            $field('field_i_outcome', 'outcome', 'Planning outcome (optional)', 'textarea', ['rows' => 3]),
            $tab('field_i_tab_cta', 'tab_industry_cta', 'FAQ & inquiry'),
            $field('field_i_faq_title', 'industry_faq_title', 'FAQ heading', 'text', ['default_value' => 'Questions before you choose']),
            $pairs('field_i_faq', 'industry_faq', 'FAQs', 'Question', 'Answer', 12),
            $field('field_i_show_inquiry', 'industry_show_inquiry', 'Show inquiry form', 'true_false', ['ui' => 1, 'default_value' => 1, 'instructions' => 'Uses the same Fluent Forms inquiry form, attachments and attribution as the rest of the website.']),
            $field('field_i_cta_title', 'industry_cta_title', 'Inquiry heading', 'text'),
            $field('field_i_cta_text', 'industry_cta_text', 'Inquiry introduction (optional)', 'textarea', ['rows' => 3]),
            $field('field_i_cta_button', 'industry_cta_button', 'Hero inquiry button label', 'text'),
        ],
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'starter_industry']]],
    ]);
}

