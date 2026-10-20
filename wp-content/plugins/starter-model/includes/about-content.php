<?php
/** Additions to the single About template content group; existing field identities stay stable. */
namespace Starter\Model;
defined('ABSPATH') || exit;

function about_media_fields(callable $field, callable $tab): array {
    return [
        $tab('field_about_tab_modules', 'tab_about_modules', 'Media & text modules'),
        $field('field_about_content_sections', 'about_content_sections', 'Media & text modules', 'repeater', [
            'layout' => 'block', 'button_label' => 'Add module', 'collapsed' => 'field_about_section_title',
            'instructions' => 'Add, remove and drag to reorder modules. Choose media on the left or right, or alternate automatically. A title needs an image or content; empty modules are hidden.',
            'sub_fields' => [
                $field('field_about_section_title', 'title', 'Module title', 'text'),
                $field('field_about_section_image', 'image', 'Image', 'image', ['return_format' => 'id', 'preview_size' => 'medium', 'instructions' => 'Select an approved company, team, factory or process image. Leave empty for a text-only module.']),
                $field('field_about_section_body', 'body', 'Content', 'wysiwyg', ['tabs' => 'all', 'toolbar' => 'full', 'media_upload' => 1, 'delay' => 0, 'instructions' => 'Describe this part of the company. Use verified facts and approved media.']),
                $field('field_about_section_position', 'image_position', 'Image position', 'select', ['choices' => ['auto' => 'Alternate automatically', 'left' => 'Image on the left', 'right' => 'Image on the right'], 'default_value' => 'auto']),
                $field('field_about_section_link', 'link', 'Optional link', 'link', ['return_format' => 'array', 'instructions' => 'Optional related page or document. Complete both URL and link text to show it.']),
            ],
        ]),
    ];
}

function about_gallery_heading_fields(callable $field): array {
    return [
        $field('field_about_gallery_title', 'about_gallery_title', 'Gallery heading', 'text', ['instructions' => 'Optional heading above the photographs. Leave empty to use “Company photographs” when photos exist.']),
        $field('field_about_gallery_intro', 'about_gallery_intro', 'Gallery introduction', 'textarea', ['rows' => 3, 'instructions' => 'Optional context for the photographs. Hidden when the gallery is empty.']),
    ];
}

function about_faq_fields(callable $field, callable $tab): array {
    return [
        $tab('field_about_tab_faq', 'tab_about_faq', 'FAQ'),
        $field('field_about_faq_title', 'about_faq_title', 'FAQ heading', 'text', ['instructions' => 'Optional heading. Leave empty to use “FAQs” when a complete question and answer exists.']),
        $field('field_about_faq_items', 'about_faq_items', 'Frequently asked questions', 'repeater', [
            'layout' => 'row', 'button_label' => 'Add question', 'collapsed' => 'field_about_faq_question',
            'instructions' => 'Add, remove or drag to reorder questions. Only complete question-and-answer pairs appear; leave empty to hide the FAQ.',
            'sub_fields' => [
                $field('field_about_faq_question', 'question', 'Question', 'text'),
                $field('field_about_faq_answer', 'answer', 'Answer', 'textarea', ['rows' => 4]),
            ],
        ]),
    ];
}

