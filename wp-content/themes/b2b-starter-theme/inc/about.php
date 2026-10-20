<?php
/** About content adapter and photo wall; renderers receive only valid saved content. */
namespace Starter\Theme;
defined('ABSPATH') || exit;

function about_content_sections(int $post_id = 0): array {
    $post_id = $post_id ?: (int) get_the_ID();
    $raw = function_exists('get_field') ? get_field('field_about_content_sections', $post_id, true, true) : [];
    $sections = [];
    foreach (is_array($raw) ? $raw : [] as $row) {
        if (!is_array($row)) continue;
        $title = trim(wp_strip_all_tags((string) ($row['title'] ?? '')));
        $image = (int) ($row['image'] ?? 0);
        if ($image <= 0 || !wp_attachment_is_image($image) || !wp_get_attachment_image_url($image, 'large')) $image = 0;
        $body = (string) ($row['body'] ?? '');
        if (!has_rich_content($body)) $body = '';
        if ($title === '' || (!$image && $body === '')) continue;
        $position = (string) ($row['image_position'] ?? 'auto');
        if (!in_array($position, ['left', 'right'], true)) $position = count($sections) % 2 === 0 ? 'left' : 'right';
        $sections[] = ['title' => $title, 'image' => $image, 'body' => $body, 'position' => $position, 'link' => is_array($row['link'] ?? null) ? $row['link'] : [], 'video' => [], 'factory' => []];
    }
    return $sections;
}

function about_gallery_images(int $post_id = 0): array {
    $post_id = $post_id ?: (int) get_the_ID();
    $raw = function_exists('get_field') ? get_field('field_about_gallery', $post_id) : [];
    $images = [];
    foreach (is_array($raw) ? $raw : [] as $photo) {
        $id = is_array($photo) ? (int) ($photo['ID'] ?? $photo['id'] ?? 0) : (int) $photo;
        if ($id <= 0 || !wp_attachment_is_image($id)) continue;
        $url = wp_get_attachment_image_url($id, 'full');
        if (!$url) continue;
        $images[] = ['id' => $id, 'url' => $url, 'alt' => trim((string) get_post_meta($id, '_wp_attachment_image_alt', true)), 'caption' => trim(wp_strip_all_tags((string) wp_get_attachment_caption($id)))];
    }
    return $images;
}

function about_faq_rows(int $post_id = 0): array {
    $post_id = $post_id ?: (int) get_the_ID();
    $raw = function_exists('get_field') ? get_field('field_about_faq_items', $post_id) : [];
    $rows = [];
    foreach (is_array($raw) ? $raw : [] as $row) {
        if (!is_array($row)) continue;
        $question = trim((string) ($row['question'] ?? ''));
        $answer = trim((string) ($row['answer'] ?? ''));
        if ($question !== '' && $answer !== '') $rows[] = ['label' => $question, 'value' => $answer];
    }
    return $rows;
}

function component_about_gallery(array $props): void {
    $images = $props['images'] ?? [];
    if (!$images) return;
    $title = trim((string) ($props['title'] ?? '')) ?: __('Company photographs', 'b2b-starter');
    $intro = trim((string) ($props['intro'] ?? ''));
    echo '<section class="about-gallery" aria-labelledby="about-gallery-heading">';
    printf('<h2 id="about-gallery-heading">%s</h2>', esc_html($title));
    if ($intro !== '') printf('<p class="about-gallery-intro">%s</p>', esc_html($intro));
    $count = count($images);
    $layout_class = [1 => ' is-single', 2 => ' has-two', 5 => ' has-five', 6 => ' has-six'][$count] ?? '';
    printf('<div class="about-gallery-grid%s">', $layout_class);
    foreach ($images as $index => $image) {
        $label = sprintf(__('View photograph %d of %d', 'b2b-starter'), $index + 1, count($images));
        echo '<figure>';
        printf('<a class="about-gallery-link" href="%s" aria-label="%s" data-alt="%s" data-caption="%s">', esc_url($image['url']), esc_attr($label . ($image['alt'] !== '' ? ': ' . $image['alt'] : '')), esc_attr($image['alt']), esc_attr($image['caption']));
        echo wp_get_attachment_image($image['id'], 'large', false, ['loading' => 'lazy', 'decoding' => 'async']);
        echo '</a>';
        if ($image['caption'] !== '') printf('<figcaption>%s</figcaption>', esc_html($image['caption']));
        echo '</figure>';
    }
    echo '</div><dialog class="about-gallery-dialog" aria-label="Company photograph viewer">';
    echo '<button type="button" class="about-gallery-close" aria-label="Close photograph viewer">×</button><p class="about-gallery-count" aria-live="polite"></p>';
    echo '<div class="about-gallery-stage"><button type="button" class="about-gallery-prev" aria-label="Previous photograph">‹</button><img class="about-gallery-viewer-image" alt=""><button type="button" class="about-gallery-next" aria-label="Next photograph">›</button></div>';
    echo '<p class="about-gallery-caption" hidden></p></dialog></section>';
}

