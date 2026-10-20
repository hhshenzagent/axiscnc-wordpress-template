<?php
/** Author information belongs to a WordPress user, never to an individual article. */
namespace Starter\Theme;
defined('ABSPATH') || exit;

function article_author_profile(int $user_id): array {
    $user = get_userdata($user_id);
    if (!$user instanceof \WP_User) return [];
    $platforms = ['linkedin' => 'LinkedIn', 'x' => 'X', 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube'];
    $socials = [];
    $rows = function_exists('get_field') ? get_field('starter_author_socials', 'user_' . $user_id) : [];
    foreach (is_array($rows) ? $rows : [] as $row) {
        if (!is_array($row)) continue;
        $platform = (string) ($row['platform'] ?? '');
        $raw = trim((string) ($row['url'] ?? ''));
        $url = esc_url_raw($raw, ['http', 'https']);
        if (!isset($platforms[$platform]) || isset($socials[$platform]) || $url === '' || !in_array(wp_parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true) || !wp_parse_url($url, PHP_URL_HOST)) continue;
        $socials[$platform] = ['platform' => $platform, 'label' => $platforms[$platform], 'url' => $url];
    }
    $photo = (int) get_user_meta($user_id, 'starter_author_photo', true);
    if (!$photo || !wp_attachment_is_image($photo)) $photo = 0;
    return ['id' => $user_id, 'name' => trim((string) $user->display_name), 'bio' => trim((string) get_user_meta($user_id, 'description', true)), 'title' => trim((string) get_user_meta($user_id, 'starter_author_title', true)), 'photo' => $photo, 'socials' => array_values($socials)];
}

function article_author_avatar(array $profile, int $size, string $class): string {
    if (empty($profile['photo'])) return '';
    return (string) wp_get_attachment_image((int) $profile['photo'], 'thumbnail', false, ['class' => $class, 'width' => $size, 'height' => $size, 'alt' => '', 'loading' => 'lazy']);
}

/** Small monochrome symbols; labels carry the accessible names. */
function article_author_social_icon(string $platform): string {
    $symbols = [
        'linkedin' => '<rect x="2" y="2" width="20" height="20" rx="2"/><path d="M6 9h3v10H6zm0-4h3v3H6zm6 4h3v1c3-3 5-1 5 2v7h-3v-6c0-2-2-2-2 0v6h-3z" fill="white"/>',
        'x' => '<path d="M3 3h5l5 7 6-7h2l-7 9 7 9h-5l-6-8-6 8H2l7-10zm3 2 11 14h2L8 5z"/>',
        'facebook' => '<path d="M14 22v-9h3l.5-4H14V7c0-1 .5-2 2-2h2V1h-3c-4 0-5 2-5 5v3H7v4h3v9z"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.2"/>',
        'tiktok' => '<path d="M14 2h3c0 3 2 5 5 5v3c-2 0-4-1-5-2v8a6 6 0 1 1-6-6v3a3 3 0 1 0 3 3z"/>',
        'youtube' => '<rect x="1" y="4" width="22" height="16" rx="5"/><path d="m10 8 7 4-7 4z" fill="white"/>',
    ];
    return isset($symbols[$platform]) ? '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false">' . $symbols[$platform] . '</svg>' : '';
}

