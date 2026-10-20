<?php
/** Presentation enriches native menu records; WordPress retains all link content. */
namespace Starter\Theme;
defined('ABSPATH') || exit;

add_action('after_setup_theme', static function (): void {
    register_nav_menus([
        'footer_components' => __('Footer: component catalogue', 'b2b-starter'),
        'footer_resources' => __('Footer: buyer resources', 'b2b-starter'),
    ]);
});
add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style('axis-navigation', get_theme_file_uri('assets/css/axis-navigation.css'), ['axis-brand', 'starter-footer'], (string) filemtime(get_theme_file_path('assets/css/axis-navigation.css')));
}, 110);
add_action('customize_register', static function ($customizer): void {
    $settings = [
        'footer_components_title' => ['Component links heading', 'Precision components', 'text'],
        'footer_resources_title' => ['Resource links heading', 'Buyer resources', 'text'],
        'axis_footer_cta_title' => ['Footer inquiry heading', 'Have a drawing ready?', 'text'],
        'axis_footer_cta_text' => ['Footer inquiry introduction', 'Share your drawing, material, quantity and finish requirements.', 'textarea'],
        'axis_footer_disclosure' => ['Visual illustration disclosure', 'Product, assembly, application and workshop images are AI-generated concept illustrations. Specifications and supplier evidence are confirmed for each project.', 'textarea'],
    ];
    foreach ($settings as $key => [$label, $default, $type]) {
        $customizer->add_setting($key, ['default' => $default, 'sanitize_callback' => $type === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field']);
        $customizer->add_control($key, ['label' => __($label, 'b2b-starter'), 'section' => 'starter_footer', 'type' => $type]);
    }
}, 30);

add_filter('nav_menu_item_title', static function ($title, $item, $args, $depth) {
    if (($args->theme_location ?? '') !== 'primary' || $depth < 1) return $title;
    $image = '';
    if ($item->type === 'post_type' && $item->object === 'starter_product') {
        $id = get_post_thumbnail_id((int) $item->object_id);
        if ($id) $image = wp_get_attachment_image($id, 'thumbnail', false, ['class' => 'axis-menu-thumb', 'alt' => '', 'loading' => 'lazy']);
    }
    $description = trim(wp_strip_all_tags((string) $item->description));
    return $image . '<span class="axis-menu-copy"><span class="axis-menu-label">' . $title . '</span>' .
        ($description !== '' ? '<span class="axis-menu-description">' . esc_html($description) . '</span>' : '') . '</span>';
}, 10, 4);

/** Saved-empty homepage sections remove their corresponding menu shortcuts too. */
add_filter('wp_nav_menu_objects', static function ($items) {
    static $available;
    if ($available === null) {
        $front = (int) get_option('page_on_front');
        $home = home_v2_data($front);
        $lead = get_field('axis_support_lead', $front);
        $members = get_field('axis_support_members', $front);
        $support = is_array($lead) && trim((string) ($lead['role'] ?? '')) !== '';
        foreach (is_array($members) ? $members : [] as $member) $support = $support || (is_array($member) && trim((string) ($member['role'] ?? '')) !== '');
        $capabilities = get_field('axis_capability_items', $front);
        $capabilities = array_filter(is_array($capabilities) ? $capabilities : [], static fn ($row) => is_array($row) && trim((string) ($row['label'] ?? '')) !== '' && trim((string) ($row['headline'] ?? '')) !== '');
        $available = ['workflow' => (bool) $home['process'], 'applications' => (bool) $home['applications'], 'machining-faqs' => (bool) $home['faq'], 'our-approach' => has_rich_content($home['company']['body']), 'project-support' => $support, 'machining-capabilities' => (bool) $capabilities];
    }
    $removed = [];
    foreach ($items as $item) {
        $parts = wp_parse_url($item->url);
        $fragment = $parts['fragment'] ?? '';
        if (($parts['path'] ?? '/') === '/' && isset($available[$fragment]) && !$available[$fragment]) $removed[] = (int) $item->ID;
    }
    do {
        $count = count($removed);
        foreach ($items as $item) if (in_array((int) $item->menu_item_parent, $removed, true) && !in_array((int) $item->ID, $removed, true)) $removed[] = (int) $item->ID;
    } while (count($removed) !== $count);
    return array_values(array_filter($items, static fn ($item) => !in_array((int) $item->ID, $removed, true)));
});

add_filter('nav_menu_css_class', static function ($classes, $item) {
    if (wp_parse_url($item->url, PHP_URL_FRAGMENT)) $classes = array_filter($classes, static fn ($class) => strpos($class, 'current-') !== 0);
    if (is_singular('starter_category') || is_tax('product_collection')) {
        if (in_array('axis-nav-products', $classes, true)) $classes = array_filter($classes, static fn ($class) => strpos($class, 'current-') !== 0);
        if (in_array('axis-nav-capabilities', $classes, true)) $classes[] = 'current-menu-ancestor';
    }
    return $classes;
}, 30, 2);
add_filter('nav_menu_link_attributes', static function ($atts, $item) {
    if (wp_parse_url($item->url, PHP_URL_FRAGMENT)) unset($atts['aria-current']);
    return $atts;
}, 30, 2);
