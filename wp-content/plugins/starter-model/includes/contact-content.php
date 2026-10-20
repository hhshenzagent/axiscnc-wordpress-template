<?php
/** Contact editor migration: old copy is preloaded only before the new field is saved. */
namespace Starter\Model;
defined('ABSPATH') || exit;

function contact_legacy_body(int $post_id): string {
    $body = (string) get_post_field('post_content', $post_id);
    // Legacy custom forms retain their original body in the form column.
    if (has_shortcode($body, 'starter_rfq_form') || has_shortcode($body, 'fluentform')) $body = '';
    $items = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) get_post_meta($post_id, 'contact_checklist', true)) ?: []), static fn ($item) => $item !== ''));
    if ($items) {
        $body .= '<h2>What helps us respond faster</h2><ul>';
        foreach ($items as $item) $body .= '<li>' . esc_html($item) . '</li>';
        $body .= '</ul>';
    }
    $steps = function_exists('get_field') ? get_field('field_contact_process_items', $post_id) : [];
    if (!is_array($steps) && !metadata_exists('post', $post_id, 'contact_process_items')) {
        $steps = [];
        foreach (preg_split('/\r?\n/', (string) get_post_meta($post_id, 'contact_process', true)) ?: [] as $line) {
            $parts = array_map('trim', explode('|', $line, 2));
            if ($parts[0] !== '') $steps[] = ['label' => $parts[0], 'value' => $parts[1] ?? ''];
        }
    }
    $valid = array_filter(is_array($steps) ? $steps : [], static fn ($row) => is_array($row) && trim((string) ($row['label'] ?? '')) !== '');
    if ($valid) {
        $body .= '<h2>After your inquiry</h2><ol>';
        foreach ($valid as $step) $body .= '<li><strong>' . esc_html($step['label']) . '</strong>' . (!empty($step['value']) ? ' — ' . esc_html($step['value']) : '') . '</li>';
        $body .= '</ol>';
    }
    return $body;
}

add_filter('acf/load_value/name=contact_body', static function ($value, $post_id) {
    return is_numeric($post_id) && !metadata_exists('post', (int) $post_id, 'contact_body') ? contact_legacy_body((int) $post_id) : $value;
}, 10, 2);

foreach (['contact_checklist', 'contact_process_items', 'contact_process'] as $name) {
    add_filter('acf/prepare_field/name=' . $name, '__return_false');
}

