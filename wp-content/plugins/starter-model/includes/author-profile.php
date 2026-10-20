<?php
/** Shared, user-owned author details. Native name and biography remain the source of truth. */
namespace Starter\Model\Authors;
defined('ABSPATH') || exit;

function platforms(): array {
    return ['linkedin' => 'LinkedIn', 'x' => 'X (Twitter)', 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube'];
}

function register_fields(): void {
    if (!function_exists('acf_add_local_field_group')) return;
    acf_add_local_field_group([
        'key' => 'group_starter_author', 'title' => 'Article author profile',
        'fields' => [
            ['key' => 'field_starter_author_help', 'label' => 'Shared author information', 'name' => '', 'type' => 'message', 'message' => 'Use the native Display name publicly as and Biographical Info above for the author name and introduction. These details appear on every article assigned to this user. Leave the biography empty to hide the footer module. Replace fictional example information before publishing a real site.', 'esc_html' => 1],
            ['key' => 'field_starter_author_photo', 'label' => 'Author photo', 'name' => 'starter_author_photo', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'thumbnail', 'library' => 'all', 'mime_types' => 'jpg,jpeg,png,webp', 'instructions' => 'Upload a square portrait. Used in the article byline and author introduction. Optional; removing it does not restore the example photo.'],
            ['key' => 'field_starter_author_title', 'label' => 'Public role or title', 'name' => 'starter_author_title', 'type' => 'text', 'maxlength' => 160, 'instructions' => 'For example, Equipment Content Editor. Optional; this is not the WordPress account permission role.'],
            ['key' => 'field_starter_author_socials', 'label' => 'Social profiles (optional)', 'name' => 'starter_author_socials', 'type' => 'repeater', 'layout' => 'table', 'max' => 6, 'button_label' => 'Add social profile', 'instructions' => 'Add only profiles belonging to this author. Empty rows are omitted. Leave all rows empty to hide this entire line. X and Twitter use the same entry.', 'sub_fields' => [
                ['key' => 'field_starter_author_platform', 'label' => 'Platform', 'name' => 'platform', 'type' => 'select', 'choices' => platforms(), 'allow_null' => 1],
                ['key' => 'field_starter_author_url', 'label' => 'Profile URL', 'name' => 'url', 'type' => 'url', 'instructions' => 'A complete https:// profile URL.'],
            ]],
        ],
        'location' => [[['param' => 'user_form', 'operator' => '==', 'value' => 'all']]],
        'style' => 'default', 'position' => 'normal', 'show_in_rest' => 0,
    ]);
}
add_action('acf/init', __NAMESPACE__ . '\\register_fields');

/** Deliberate Starter example, initialized once on first activation or an explicit seed. */
function initialize_example_author(): int {
    $stored = get_option('starter_example_author_id', null);
    // Even deletion is a saved choice: a later activation/import must not resurrect the example.
    if ($stored !== null) return get_userdata((int) $stored) ? (int) $stored : 0;
    $login = 'starter-example-author';
    if (get_user_by('login', $login)) return 0; // Never claim an existing account with the same login.
    $image = dirname(__DIR__) . '/assets/images/example-author.webp';
    if (!is_readable($image)) throw new \RuntimeException('Starter example portrait is missing');
    $user_id = wp_insert_user([
        'user_login' => $login, 'user_pass' => wp_generate_password(48, true, true),
        'user_email' => 'starter-example-author@example.invalid', 'role' => 'author',
        'display_name' => 'Alex Morgan', 'first_name' => 'Alex', 'last_name' => 'Morgan',
        'description' => "Alex writes practical product guides, comparison articles and buyer checklists. His articles help buyers understand configurations, plan their requirements and prepare useful questions for suppliers.\n\nThis is a fictional Starter example profile. Replace it with accurate information about your author before publishing.",
    ]);
    if (is_wp_error($user_id)) throw new \RuntimeException($user_id->get_error_message());
    update_user_meta($user_id, '_starter_example_author', 1);
    $upload = wp_upload_bits('starter-example-author.webp', null, file_get_contents($image));
    if ($upload['error']) throw new \RuntimeException($upload['error']);
    $attachment = wp_insert_attachment(['post_title' => 'Alex Morgan — fictional example portrait', 'post_mime_type' => 'image/webp', 'post_status' => 'inherit', 'post_author' => $user_id], $upload['file'], 0, true);
    if (is_wp_error($attachment)) throw new \RuntimeException($attachment->get_error_message());
    require_once ABSPATH . 'wp-admin/includes/image.php';
    wp_update_attachment_metadata($attachment, wp_generate_attachment_metadata($attachment, $upload['file']));
    update_post_meta($attachment, '_wp_attachment_image_alt', 'Alex Morgan, fictional example author');
    update_post_meta($attachment, '_starter_example_author', 1);
    // ACF references are explicit, including when activation happens after acf/init.
    foreach (['starter_author_photo' => [$attachment, 'field_starter_author_photo'], 'starter_author_title' => ['Product Content Editor', 'field_starter_author_title']] as $name => [$value, $key]) {
        update_user_meta($user_id, $name, $value); update_user_meta($user_id, '_' . $name, $key);
    }
    register_fields();
    $socials = [];
    foreach (platforms() as $platform => $label) $socials[] = ['platform' => $platform, 'url' => 'https://example.com/demo-author/' . $platform];
    if (function_exists('update_field')) update_field('field_starter_author_socials', $socials, 'user_' . $user_id);
    update_option('starter_example_author_id', (int) $user_id, false);
    return (int) $user_id;
}
// AXISCNC does not initialize fictional author identities or portraits.

/** Let native avatars and the SEO provider use the same optional uploaded portrait. */
add_filter('get_avatar_data', static function (array $args, $identity): array {
    $user_id = is_numeric($identity) ? (int) $identity : 0;
    if ($identity instanceof \WP_User) $user_id = (int) $identity->ID;
    elseif ($identity instanceof \WP_Comment) $user_id = (int) $identity->user_id;
    elseif (is_string($identity) && is_email($identity)) {
        $user = get_user_by('email', $identity); $user_id = $user ? (int) $user->ID : 0;
    }
    $photo = $user_id ? (int) get_user_meta($user_id, 'starter_author_photo', true) : 0;
    if ($photo && wp_attachment_is_image($photo)) {
        $url = wp_get_attachment_image_url($photo, 'thumbnail');
        if ($url) { $args['url'] = $url; $args['found_avatar'] = true; }
    }
    return $args;
}, 10, 2);

