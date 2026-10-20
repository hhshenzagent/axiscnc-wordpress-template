<?php
/**
 * Blog and article helpers: readability, bylines, TOC and related posts.
 *
 * @package b2b-starter
 */

namespace Starter\Theme;

defined('ABSPATH') || exit;

function blog_reading_time($post = 0): int {
    $content = (string) get_post_field('post_content', $post);
    $words = preg_split('/\s+/u', wp_strip_all_tags($content) ?: '', -1, PREG_SPLIT_NO_EMPTY) ?: [];
    return max(1, (int) ceil(count($words) / 220));
}

function blog_primary_category($post = 0): ?\WP_Term {
    $categories = get_the_category($post);
    return $categories[0] ?? null;
}

function blog_modified_is_visible($post = 0): bool {
    $post = get_post($post);
    if (!$post instanceof \WP_Post) return false;
    return (int) get_post_modified_time('U', false, $post) - (int) get_post_time('U', false, $post) >= DAY_IN_SECONDS;
}

/**
 * Add stable IDs to H2/H3 headings and return a table of contents.
 *
 * @return array{content:string, items:array<int,array{id:string,text:string,level:int}>}
 */
function blog_article_content($post = 0): array {
    $content = (string) apply_filters('the_content', (string) get_post_field('post_content', $post));
    $items = [];

    $callback = static function (array $matches) use (&$items): string {
        $level = (int) $matches[1];
        $attributes = (string) $matches[2];
        $inner = (string) $matches[3];
        $text = wp_strip_all_tags($inner);
        $id = sanitize_title($text);

        if (preg_match('/\bid=["\']([^"\']+)["\']/', $attributes, $match)) {
            $id = (string) $match[1];
        }

        $unique = $id;
        $suffix = 2;
        while (in_array($unique, array_column($items, 'id'), true)) {
            $unique = $id . '-' . $suffix;
            $suffix++;
        }

        $attributes = (string) preg_replace('/\sid=["\'][^"\']*["\']/i', '', $attributes);
        $items[] = ['id' => $unique, 'text' => $text, 'level' => $level];

        return sprintf('<h%1$d id="%2$s"%3$s>%4$s</h%1$d>', $level, esc_attr($unique), $attributes, $inner);
    };

    $content = (string) preg_replace_callback('/<h([23])((?:[^>]*)?)>(.*?)<\/h\1>/is', $callback, $content);

    return [
        'content' => $content,
        'items' => $items,
    ];
}

function blog_related_posts($post = 0, int $limit = 3): array {
    $post = get_post($post);
    if (!$post instanceof \WP_Post) return [];

    $category_ids = wp_get_post_categories((int) $post->ID);
    $args = [
        'post_type' => 'post',
        'posts_per_page' => $limit,
        'post__not_in' => [(int) $post->ID],
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
    ];

    if ($category_ids) {
        $args['category__in'] = $category_ids;
    }

    return get_posts($args);
}

function blog_publisher_logo_url(): string {
    $logo_id = (int) get_theme_mod('custom_logo');
    $logo = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';
    return $logo ?: (string) get_theme_file_uri('assets/favicon.svg');
}

