<?php
/** Native, editable inquiry confirmation page. Initialization/migration are explicit writes. */
namespace Starter\Model\InquiryThankYou;

defined('ABSPATH') || exit;

const PAGE_OPTION = 'starter_inquiry_thank_you_page_id';

/** Reads only; an unpublished, protected or foreign target cannot become a success destination. */
function page_url(int $page_id, bool $allow_plain_permalink = false): string {
    if ($page_id <= 0) return '';
    $page = get_post($page_id);
    if (!$page || $page->post_type !== 'page' || $page->post_status !== 'publish' || $page->post_password !== '') return '';
    $url = (string) get_permalink($page_id);
    $target = wp_parse_url($url);
    $home = wp_parse_url(home_url('/'));
    $plain_permalink = $allow_plain_permalink && ($target['query'] ?? '') === 'page_id=' . $page_id;
    if (!$target || !$home || !in_array($target['scheme'] ?? '', ['http', 'https'], true)
        || ($target['scheme'] ?? '') !== ($home['scheme'] ?? '')
        || strtolower($target['host'] ?? '') !== strtolower($home['host'] ?? '')
        || ($target['port'] ?? null) !== ($home['port'] ?? null)
        || isset($target['user']) || isset($target['pass']) || (isset($target['query']) && !$plain_permalink) || isset($target['fragment'])) return '';
    return $url;
}

/** Plugin activation/new-site initialization; never overwrite an existing page's title or body. */
function initialize(int $selected_page_id = 0): int {
    $page_id = $selected_page_id ?: (int) get_option(PAGE_OPTION, 0);
    if (!$page_id) {
        if (get_page_by_path('thank-you', OBJECT, 'page')) {
            throw new \RuntimeException('Thank-you route already exists; explicitly select a published native Page before migration.');
        }
        // Keep WordPress's optional auto-add-pages setting from adding this utility page to menus.
        $no_auto_add = static function ($options) {
            if (is_array($options)) $options['auto_add'] = [];
            return $options;
        };
        add_filter('option_nav_menu_options', $no_auto_add);
        try {
            $page_id = wp_insert_post([
                'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'thank-you',
                'post_title' => 'Thank you',
                'post_content' => '<p>Your inquiry has been received. Thank you for sharing your requirements.</p>'
                    . '<p>Our team will review the details and contact you using the information you provided.</p>'
                    . '<p>You can continue exploring our products or return to the homepage.</p>'
                    . '<p><a href="' . esc_url(home_url('/products/')) . '">Browse products</a> or '
                    . '<a href="' . esc_url(home_url('/')) . '">Return to homepage</a></p>',
                'meta_input' => ['_wp_page_template' => 'default'],
            ], true);
        } finally {
            remove_filter('option_nav_menu_options', $no_auto_add);
        }
        if (is_wp_error($page_id)) throw new \RuntimeException($page_id->get_error_message());
        $page_id = (int) $page_id;
    }
    if (!page_url($page_id, true) || !in_array(get_post_meta($page_id, '_wp_page_template', true), ['', 'default'], true)) {
        throw new \RuntimeException('Thank-you target must be a published, unprotected native Page with a same-origin permalink and default template.');
    }
    update_option(PAGE_OPTION, $page_id, false);
    update_post_meta($page_id, 'rank_math_robots', ['noindex']);
    return $page_id;
}

/** New form defaults read the initialized page; missing targets retain a safe inline confirmation. */
function confirmation(): array {
    $page_id = (int) get_option(PAGE_OPTION, 0);
    return page_url($page_id) ? [
        'redirectTo' => 'customPage', 'customPage' => $page_id,
        'enable_query_string' => 'no', 'query_strings' => '',
    ] : [
        'redirectTo' => 'samePage', 'messageToShow' => 'Thank you. Your request has been received.',
        'samePageFormBehavior' => 'hide_form',
    ];
}

/** Authorized existing-site migration: only the chosen published shared RFQ's confirmation changes. */
function migrate_confirmation(int $form_id): array {
    if (!defined('WP_CLI') || !WP_CLI) throw new \RuntimeException('Explicit WP-CLI migration required.');
    if ($form_id <= 0 || $form_id !== (int) get_option('starter_rfq_form_id')) throw new \RuntimeException('Select the inventoried Starter RFQ form.');
    $page_id = (int) get_option(PAGE_OPTION, 0);
    if (!page_url($page_id)) throw new \RuntimeException('Initialize and verify the thank-you Page before migrating confirmation.');
    global $wpdb;
    $forms = $wpdb->prefix . 'fluentform_forms';
    $meta = $wpdb->prefix . 'fluentform_form_meta';
    if ($wpdb->get_var($wpdb->prepare("SELECT status FROM {$forms} WHERE id=%d", $form_id)) !== 'published') throw new \RuntimeException('Published RFQ form required.');
    $rows = $wpdb->get_results($wpdb->prepare("SELECT id, value FROM {$meta} WHERE form_id=%d AND meta_key='formSettings'", $form_id));
    if (count($rows) !== 1) throw new \RuntimeException('Exactly one saved formSettings row required; inspect before migration.');
    $settings = json_decode((string) $rows[0]->value, true);
    if (!is_array($settings)) throw new \RuntimeException('Invalid formSettings; preserve and inspect before migration.');
    $before = $settings['confirmation'] ?? null;
    $settings['confirmation'] = confirmation();
    if ($wpdb->update($meta, ['value' => wp_json_encode($settings)], ['id' => (int) $rows[0]->id]) === false) throw new \RuntimeException('Confirmation migration failed.');
    $saved = json_decode((string) $wpdb->get_var($wpdb->prepare("SELECT value FROM {$meta} WHERE id=%d", (int) $rows[0]->id)), true);
    if ($saved !== $settings) throw new \RuntimeException('Confirmation readback differs; inspect before retrying.');
    return ['form_id' => $form_id, 'page_id' => $page_id, 'url' => page_url($page_id), 'before' => $before, 'after' => $saved['confirmation']];
}

/** Public hooks read only; Page visits never create entries, pages or notification messages. */
function boot(): void {
    add_filter('wp_robots', static function (array $robots): array {
        if (($page_id = (int) get_option(PAGE_OPTION, 0)) > 0 && is_page($page_id)) { unset($robots['index']); $robots['noindex'] = true; }
        return $robots;
    });
    add_filter('rank_math/frontend/robots', static function (array $robots): array {
        if (($page_id = (int) get_option(PAGE_OPTION, 0)) > 0 && is_page($page_id)) $robots['index'] = 'noindex';
        return $robots;
    });
    add_filter('rank_math/sitemap/entry', static function ($entry, $type, $object) {
        return $type === 'post' && (int) ($object->ID ?? 0) === (int) get_option(PAGE_OPTION, 0) ? false : $entry;
    }, 10, 3);
    add_filter('wp_sitemaps_posts_query_args', static function (array $args, string $post_type): array {
        $page_id = (int) get_option(PAGE_OPTION, 0);
        if ($post_type === 'page' && $page_id) $args['post__not_in'] = array_values(array_unique(array_merge($args['post__not_in'] ?? [], [$page_id])));
        return $args;
    }, 10, 2);
    add_filter('wp_list_pages_excludes', static function (array $ids): array {
        $page_id = (int) get_option(PAGE_OPTION, 0);
        return $page_id ? array_values(array_unique(array_merge($ids, [$page_id]))) : $ids;
    });
}

