<?php
/** Public contact details and social channels share the private Business Profile. */
namespace Starter\Model;
defined('ABSPATH') || exit;

function profile_contact_field_keys(): array {
    return [
        'contact_email' => 'field_profile_contact_email',
        'contact_phone' => 'field_profile_contact_phone',
        'contact_address' => 'field_profile_contact_address',
        'contact_hours' => 'field_profile_contact_hours',
    ];
}

/** Compatibility reads only the active stylesheet's previous values, without writing. */
function legacy_contact_value(string $name, string $fallback = ''): string {
    if (!isset(profile_contact_field_keys()[$name])) return $fallback;
    $value = get_theme_mod($name, '');
    return is_string($value) && $value !== '' ? $value : $fallback;
}

/** Metadata presence, including a saved blank, takes priority over legacy settings. */
function profile_contact_value(string $name, string $fallback = ''): string {
    if (!isset(profile_contact_field_keys()[$name])) return $fallback;
    $id = profile_post_id();
    if ($id && metadata_exists('post', $id, $name)) {
        $value = get_post_meta($id, $name, true);
        return is_string($value) ? $value : '';
    }
    return legacy_contact_value($name, $fallback);
}

foreach (profile_contact_field_keys() as $name => $key) {
    add_filter('acf/load_value/key=' . $key, static function ($value, $post_id) use ($name) {
        if (!is_numeric($post_id) || get_post_type((int) $post_id) !== 'starter_profile'
            || metadata_exists('post', (int) $post_id, $name)) return $value;
        return legacy_contact_value($name);
    }, 10, 2);
}

add_action('acf/init', static function (): void {
    if (!function_exists('acf_add_local_field_group')) return;
    acf_add_local_field_group([
        'key' => 'group_profile_contacts', 'title' => 'Business contact details', 'show_in_rest' => false,
        'menu_order' => -10,
        'fields' => [
            ['key' => 'field_profile_contact_email', 'name' => 'contact_email', 'label' => 'Public email', 'type' => 'email', 'instructions' => 'Shown on the website. Inquiry recipients and SMTP are configured separately.'],
            ['key' => 'field_profile_contact_phone', 'name' => 'contact_phone', 'label' => 'Phone', 'type' => 'text'],
            ['key' => 'field_profile_contact_address', 'name' => 'contact_address', 'label' => 'Address', 'type' => 'textarea', 'rows' => 3, 'new_lines' => ''],
            ['key' => 'field_profile_contact_hours', 'name' => 'contact_hours', 'label' => 'Working hours', 'type' => 'text'],
        ],
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'starter_profile']]],
    ]);
    acf_add_local_field_group([
        'key' => 'group_footer_social', 'title' => 'Footer social channels', 'show_in_rest' => true,
        'fields' => [[
            'key' => 'field_footer_social_links', 'name' => 'footer_social_links', 'label' => 'Social channels',
            'type' => 'repeater', 'layout' => 'row', 'min' => 0, 'max' => 12,
            'button_label' => 'Add social channel', 'collapsed' => 'field_footer_social_platform',
            'instructions' => 'Add, remove or drag to reorder. A channel needs a complete http(s) link. Empty means no icons. Contact details are in Business contact details; links in Appearance → Menus.',
            'sub_fields' => [
                ['key' => 'field_footer_social_platform', 'name' => 'platform', 'label' => 'Platform', 'type' => 'select', 'choices' => ['linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'whatsapp' => 'WhatsApp', 'x' => 'X', 'tiktok' => 'TikTok', 'other' => 'Other'], 'default_value' => 'linkedin'],
                ['key' => 'field_footer_social_url', 'name' => 'url', 'label' => 'Channel URL', 'type' => 'url', 'instructions' => 'Full profile/channel URL. For WhatsApp, use your https://wa.me/ link.'],
                ['key' => 'field_footer_social_label', 'name' => 'label', 'label' => 'Accessible name', 'type' => 'text', 'instructions' => 'Optional for a known platform; required for Other. Names the icon for screen readers.'],
                ['key' => 'field_footer_social_icon', 'name' => 'icon', 'label' => 'Custom icon', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'thumbnail', 'mime_types' => 'png,jpg,jpeg,webp', 'instructions' => 'Optional square image, preferably transparent. Displayed in monochrome; the original upload is preserved.'],
            ],
        ]],
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'starter_profile']]],
    ]);
});
// Previous links preload only until the first saved list; a saved empty value wins.
add_filter('acf/load_value/name=footer_social_links', static function ($value, $post_id) {
    if (!is_numeric($post_id) || metadata_exists('post', (int) $post_id, 'footer_social_links')) return $value;
    $rows = [];
    foreach (['linkedin', 'youtube'] as $platform) {
        $url = trim((string) get_theme_mod('social_' . $platform, ''));
        if ($url !== '') $rows[] = ['field_footer_social_platform' => $platform, 'field_footer_social_url' => $url, 'field_footer_social_label' => '', 'field_footer_social_icon' => 0];
    }
    return $rows ?: $value;
}, 10, 2);

