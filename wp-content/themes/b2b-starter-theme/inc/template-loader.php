<?php
/**
 * Controlled template registry and renderers.
 *
 * @package b2b-starter
 */

namespace Starter\Theme;

defined('ABSPATH') || exit;

/** Name the existing native template panel without replacing its controls or save handler. */
add_action('add_meta_boxes', static function (string $post_type, \WP_Post $post): void {
    if (!in_array($post_type, ['page', 'post', 'starter_product', 'starter_category', 'starter_industry', 'starter_guide', 'axis_capability', 'axis_solution', 'axis_material', 'axis_resource'], true)
        || !get_page_templates($post)) return;
    global $wp_meta_boxes;
    foreach ($wp_meta_boxes[$post_type] ?? [] as $context => $priorities) {
        foreach ($priorities as $priority => $boxes) {
            if (!empty($boxes['pageparentdiv'])) {
                $wp_meta_boxes[$post_type][$context][$priority]['pageparentdiv']['title'] = __('Page Template', 'b2b-starter');
            }
        }
    }
}, 20, 2);

function product_templates(): array {
    return [
        'standard' => 'Standard product layout',
        'simple' => 'Simple product layout',
    ];
}

// The native default is Standard; avoid a second selectable file for the same layout.
add_filter('default_page_template_title', static function (string $label): string {
    global $post;
    if (!$post instanceof \WP_Post) return $label;
    if ($post->post_type === 'starter_product') return __('Product: Standard', 'b2b-starter');
    if ($post->post_type === 'starter_category') return __('Category: Standard', 'b2b-starter');
    return $label;
});

function category_templates(): array {
    return [
        'standard' => 'Default product category',
    ];
}

function home_templates(): array {
    return [
        'corporate' => 'Default manufacturer homepage',
        'product-led' => 'Product-led catalogue',
        'conversion' => 'Conversion / RFQ first',
        'industrial' => 'Industrial capability first',
    ];
}

function normalize_template_choice($value, array $allowed, string $default): string {
    $value = sanitize_key((string) $value);
    return isset($allowed[$value]) ? $value : $default;
}

/** Upgrade one legacy record, preserving explicit native choices and archived evidence. */
function migrate_product_template_choice(int $id): bool {
    if (get_post_type($id) !== 'starter_product') return true;
    $retired = [
        'page-templates/product-standard.php', 'page-templates/product-technical.php',
        'page-templates/product-project.php', 'page-templates/product-compact.php',
    ];
    $previous = get_post_meta($id, '_wp_page_template', true);
    if (in_array($previous, $retired, true)) {
        update_post_meta($id, '_starter_legacy_native_product_template', $previous);
        if (get_post_meta($id, '_starter_legacy_native_product_template', true) !== $previous) return false;
        update_post_meta($id, '_wp_page_template', 'default');
        if (get_post_meta($id, '_wp_page_template', true) !== 'default') return false;
    }
    if (metadata_exists('post', $id, 'product_template')) {
        $legacy = get_post_meta($id, 'product_template', true);
        if (!metadata_exists('post', $id, '_wp_page_template')) {
            $target = sanitize_key((string) $legacy) === 'simple' ? 'page-templates/product-simple.php' : 'default';
            if ($target !== 'default' && !locate_template($target)) return false;
            update_post_meta($id, '_wp_page_template', $target);
            if (get_post_meta($id, '_wp_page_template', true) !== $target) return false;
        }
        update_post_meta($id, '_starter_legacy_product_template', $legacy);
        if (get_post_meta($id, '_starter_legacy_product_template', true) !== $legacy) return false;
        delete_post_meta($id, 'product_template');
        if (metadata_exists('post', $id, 'product_template')) return false;
    }
    // ACF may write the field-key reference after its value hook has already migrated it.
    if (metadata_exists('post', $id, '_starter_legacy_product_template')) {
        delete_post_meta($id, '_product_template');
        if (metadata_exists('post', $id, '_product_template')) return false;
    }
    return true;
}

/** One upgrade scan; completed requests use the small autoloaded version marker. */
function migrate_product_template_choices(): void {
    if (get_option('starter_product_template_migration_version') === '1') return;
    $retired = [
        'page-templates/product-standard.php', 'page-templates/product-technical.php',
        'page-templates/product-project.php', 'page-templates/product-compact.php',
    ];
    $query = ['post_type' => 'starter_product', 'post_status' => array_keys(get_post_stati()), 'posts_per_page' => -1, 'fields' => 'ids'];
    $ids = array_unique(array_merge(
        get_posts($query + ['meta_query' => [['key' => '_wp_page_template', 'value' => $retired, 'compare' => 'IN']]]),
        get_posts($query + ['meta_key' => 'product_template'])
    ));
    $complete = true;
    foreach ($ids as $id) if (!migrate_product_template_choice((int) $id)) $complete = false;
    if ($complete) update_option('starter_product_template_migration_version', '1', true);
}
add_action('init', __NAMESPACE__ . '\\migrate_product_template_choices', 30);

/** Later legacy imports migrate only their target; failed records keep upgrade retryable. */
function migrate_product_template_meta($meta_id, $post_id, $meta_key, $meta_value): void {
    if (!in_array($meta_key, ['product_template', '_product_template', '_wp_page_template'], true)) return;
    static $busy = [];
    $id = (int) $post_id;
    if (isset($busy[$id])) return;
    $busy[$id] = true;
    try {
        if (!migrate_product_template_choice($id)) delete_option('starter_product_template_migration_version');
    } finally { unset($busy[$id]); }
}
add_action('added_post_meta', __NAMESPACE__ . '\\migrate_product_template_meta', 30, 4);
add_action('updated_post_meta', __NAMESPACE__ . '\\migrate_product_template_meta', 30, 4);

function category_template_value($term = null): string {
    return 'standard';
}

function home_template_value($post = 0): string {
    $value = field_text('home_template', $post ?: false);
    return normalize_template_choice($value, home_templates(), 'corporate');
}

function render_product_single(string $template = 'standard'): void {
    $allowed = product_templates();
    $template = normalize_template_choice($template, $allowed, 'standard');
    get_header();
    while (have_posts()) : the_post();
        set_query_var('product_template_context', [
            'product' => product_data(),
            'factory' => factory_profile_data(),
        ]);
        get_template_part('templates/products/' . $template);
    endwhile;
    get_footer();
}

function render_category_archive(string $template = 'standard'): void {
    $allowed = category_templates();
    $template = normalize_template_choice($template, $allowed, 'standard');
    get_header();
    set_query_var('category_template_context', [
        'category' => product_category_data(),
        'product_query' => category_product_query(),
        'factory' => factory_profile_data(),
    ]);
    get_template_part('templates/categories/' . $template);
    get_footer();
}

function render_home_layout(string $template = 'corporate'): void {
    $allowed = home_templates();
    $template = normalize_template_choice($template, $allowed, 'corporate');
    get_header();
    while (have_posts()) : the_post();
        set_query_var('home_template_context', [
            'home' => homepage_data(),
            'factory' => factory_profile_data(),
        ]);
        get_template_part('templates/home/' . str_replace('-', '-', $template));
    endwhile;
    get_footer();
}

