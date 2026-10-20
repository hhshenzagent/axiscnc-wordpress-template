<?php
namespace Starter\Model;
defined('ABSPATH') || exit;

/** Canonical reusable field definitions; each native template owns its complete editor. */
function register_category_fields(callable $field, callable $tab, callable $tables, callable $pair, callable $rows_help): void {
    $library = [
        'key' => 'group_collection_content', 'title' => 'Category content', 'show_in_rest' => true,
        'label_placement' => 'top', 'instruction_placement' => 'label',
        'fields' => [
            $tab('field_tc_tab_hero', 'tab_category_hero', 'Introduction'),
            $field('field_tc_overline', 'category_overline', 'Hero eyebrow', 'text'),
            $field('field_tc_intro', 'category_intro', 'Hero introduction', 'textarea', ['rows' => 5, 'instructions' => 'Explain what belongs in this range and which buyers it serves.']),
            $field('field_tc_hero_image', 'category_hero_image', 'Hero image', 'image', ['return_format' => 'array', 'preview_size' => 'medium']),
            $field('field_tc_card_image', 'category_card_image', 'Catalogue card image (optional)', 'image', ['return_format' => 'id', 'preview_size' => 'medium', 'instructions' => 'Use a square cover for product-family cards. Leave empty to use the category hero image.']),
            $field('field_tc_show_products', 'category_show_products', 'Show products in this range', 'true_false', [
                'ui' => 1, 'default_value' => 1,
                'instructions' => 'Automatically lists published products assigned to this category, including child categories. Assign categories in each product editor. Turn off to build the page with media/text modules instead.',
            ]),
            $tab('field_tc_tab_sections', 'tab_category_sections', 'Media & text modules'),
            $field('field_tc_content_sections', 'category_content_sections', 'Media & text modules', 'repeater', [
                'layout' => 'block', 'max' => 20, 'button_label' => 'Add module', 'collapsed' => 'field_tc_section_title',
                'instructions' => 'Add, remove and drag to reorder sections below the products. Choose an image or uploaded video for each module. A title needs valid media or content. Empty modules are hidden.',
                'sub_fields' => [
                    $field('field_tc_section_title', 'title', 'Module title', 'text'),
                    $field('field_tc_section_source', 'content_source', 'Content source', 'select', ['choices' => ['custom' => 'Custom content', 'shared_factory' => 'Shared factory introduction'], 'default_value' => 'custom', 'instructions' => 'Shared factory content comes from Business Profile.']),
                    $field('field_tc_section_media_type', 'media_type', 'Media type', 'button_group', ['choices' => ['image' => 'Image', 'video' => 'Video'], 'default_value' => 'image', 'instructions' => 'Choose what appears beside the text. Switching types preserves the other saved media. Shared factory images come from Business Profile.']),
                    $field('field_tc_section_image', 'image', 'Image', 'image', ['return_format' => 'id', 'preview_size' => 'medium', 'conditional_logic' => [[['field' => 'field_tc_section_source', 'operator' => '==', 'value' => 'custom'], ['field' => 'field_tc_section_media_type', 'operator' => '!=', 'value' => 'video']]]]),
                    $field('field_tc_section_video', 'video', 'Video', 'file', ['return_format' => 'id', 'library' => 'all', 'mime_types' => 'mp4,webm,ogv', 'conditional_logic' => [[['field' => 'field_tc_section_media_type', 'operator' => '==', 'value' => 'video']]], 'instructions' => 'Upload or choose a browser-playable video. MP4 (H.264) is recommended. Videos use controls and do not autoplay.']),
                    $field('field_tc_section_video_poster', 'video_poster', 'Video cover image (optional)', 'image', ['return_format' => 'id', 'preview_size' => 'medium', 'conditional_logic' => [[['field' => 'field_tc_section_media_type', 'operator' => '==', 'value' => 'video']]], 'instructions' => 'Shown before playback. Leave empty for no custom cover image.']),
                    $field('field_tc_section_body', 'body', 'Content', 'wysiwyg', ['tabs' => 'all', 'toolbar' => 'full', 'media_upload' => 1, 'delay' => 0, 'conditional_logic' => [[['field' => 'field_tc_section_source', 'operator' => '==', 'value' => 'custom']]]]),
                    $field('field_tc_section_position', 'image_position', 'Media position', 'select', ['choices' => ['auto' => 'Alternate automatically', 'left' => 'Media on the left', 'right' => 'Media on the right'], 'default_value' => 'auto']),
                    $field('field_tc_section_link', 'link', 'Optional link', 'link', ['return_format' => 'array', 'conditional_logic' => [[['field' => 'field_tc_section_source', 'operator' => '==', 'value' => 'custom']]]]),
                ],
            ]),
            $tab('field_tc_tab_guide', 'tab_category_guide', 'Buying guide'),
            $field('field_tc_guide_title', 'category_guide_title', 'Buying guide title', 'text', ['instructions' => 'The heading above the complete guide. Leave empty to use “Buying guide”.']),
            $field('field_tc_long_description', 'category_long_description', 'Buying guide', 'wysiwyg', [
                'tabs' => 'all', 'toolbar' => 'full', 'media_upload' => 1, 'delay' => 0,
                'instructions' => 'Rich editorial content for search intent, technical context, applications and buying guidance.',
            ]),
            $tab('field_tc_tab_selection', 'tab_category_selection', 'Attributes & FAQ'),
            $field('field_tc_spec_items', 'category_spec_items', 'Category specifications', 'repeater', [
                'layout' => 'table', 'max' => 40, 'button_label' => 'Add row', 'collapsed' => 'field_tc_spec_label',
                'sub_fields' => [
                    $field('field_tc_spec_label', 'label', 'Label', 'text'),
                    $field('field_tc_spec_value', 'value', 'Value', 'text'),
                ],
            ]),
            $tables('field_tc_attribute_tables', 'category_attribute_tables', 'Additional attribute tables'),
            $field('field_tc_faq_items', 'category_faq_items', 'Category FAQ', 'repeater', [
                'layout' => 'row', 'max' => 30, 'button_label' => 'Add question', 'collapsed' => 'field_tc_faq_question',
                'sub_fields' => [
                    $field('field_tc_faq_question', 'question', 'Question', 'text'),
                    $field('field_tc_faq_answer', 'answer', 'Answer', 'textarea'),
                ],
            ]),
            $tab('field_tc_tab_editorial', 'tab_category_editorial', 'Inquiry'),
            $field('field_tc_cta_title', 'category_cta_title', 'CTA title', 'text'),
            $field('field_tc_cta_text', 'category_cta_text', 'CTA text', 'textarea', ['rows' => 3]),
            $field('field_tc_cta_button', 'category_cta_button', 'CTA button label', 'text'),
            $field('field_tc_fact_items', 'category_fact_items', 'Key facts', 'repeater', [
                'layout' => 'table', 'max' => 40, 'button_label' => 'Add row', 'collapsed' => 'field_tc_fact_label',
                'sub_fields' => [
                    $field('field_tc_fact_label', 'label', 'Label', 'text'),
                    $field('field_tc_fact_value', 'value', 'Value', 'text'),
                ],
            ]),
            $pair('field_tc_feature_items', 'category_feature_items', 'Range benefits'),
            $field('field_tc_features', 'category_features', 'Range benefits', 'textarea', array_merge(['rows' => 8], $rows_help('Use Title | Description.'))),
            $pair('field_tc_selection_items', 'category_selection_items', 'Selection guide'),
            $field('field_tc_selection_guide', 'category_selection_guide', 'Selection guide', 'textarea', array_merge(['rows' => 10], $rows_help('Use Step | Buying guidance.'))),
            $tab('field_tc_tab_applications', 'tab_category_applications', 'Applications'),
            $field('field_tc_applications', 'category_applications', 'Typical applications', 'textarea', array_merge(['rows' => 6], $rows_help('Enter an application.'))),
            $pair('field_tc_use_case_items', 'category_use_case_items', 'Use cases'),
            $field('field_tc_use_cases', 'category_use_cases', 'Use cases', 'textarea', array_merge(['rows' => 10], $rows_help('Use Title | Description.'))),
            $tab('field_tc_tab_trust', 'tab_category_trust', 'Factory, standards & RFQ'),
            $field('field_tc_standards', 'category_standards', 'Standards & compliance', 'textarea', array_merge(['rows' => 6], $rows_help('Enter a standard, certification or compliance note.'))),
            $pair('field_tc_process_items', 'category_process_items', 'RFQ process'),
            $field('field_tc_process', 'category_process', 'RFQ process', 'textarea', array_merge(['rows' => 8], $rows_help('Use Step | Description.'))),
            $field('field_tc_checklist', 'category_checklist', 'RFQ checklist', 'textarea', array_merge(['rows' => 6], $rows_help('Enter information buyers should send.'))),
            $tab('field_tc_tab_resources', 'tab_category_resources', 'Resources & FAQ'),
            $field('field_tc_resources', 'category_resources', 'Resources & downloads', 'textarea', array_merge(['rows' => 6], $rows_help('Use Label | URL.'))),
            $field('field_tc_key_facts', 'category_key_facts', 'Previous key facts', 'textarea', array_merge(['rows' => 6], $rows_help('Use Label | Value.'))),
            $field('field_tc_specifications', 'category_specifications', 'Previous category specifications', 'textarea', array_merge(['rows' => 10], $rows_help('Use Label | Value or range.'))),
            $field('field_tc_faq', 'category_faq', 'Previous category faq', 'textarea', array_merge(['rows' => 8], $rows_help('Use Question | Answer.'))),
        ],
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'starter_field_library']]],
    ];
    register_category_editors($library, $field);

}

