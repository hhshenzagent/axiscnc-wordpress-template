<?php
namespace Starter\Theme;
defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts',static function():void{
    wp_enqueue_style('axis-brand',get_theme_file_uri('assets/css/axis.css'),['starter','starter-navigation'],(string)filemtime(get_theme_file_path('assets/css/axis.css')));
    wp_enqueue_script('axis-contact',get_theme_file_uri('assets/js/axis.js'),[],(string)filemtime(get_theme_file_path('assets/js/axis.js')),true);
},99);

require_once get_theme_file_path('inc/components.php');
require_once get_theme_file_path('inc/page-data.php');
require_once get_theme_file_path('inc/about.php');
require_once get_theme_file_path('inc/home.php');
require_once get_theme_file_path('inc/blog.php');
require_once get_theme_file_path('inc/template-loader.php');
require_once get_theme_file_path('inc/category-pages.php');
require_once get_theme_file_path('inc/footer.php');
require_once get_theme_file_path('inc/axis-navigation.php');
require_once get_theme_file_path('inc/axis-template-suite.php');
require_once get_theme_file_path('inc/axis-suite-extra.php');
require_once get_theme_file_path('inc/approved-design.php');
require_once get_theme_file_path('inc/quote-experience.php');
require_once get_theme_file_path('inc/header-navigation.php');

add_action('after_setup_theme', static function (): void {
    add_theme_support('title-tag');
    add_theme_support('custom-logo', ['height' => 60, 'width' => 240, 'flex-height' => true, 'flex-width' => true]);
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_editor_style('style.css');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    register_nav_menus(['primary' => __('Primary navigation', 'b2b-starter')]);
});

add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style('starter', get_stylesheet_uri(), [], (string) filemtime(get_stylesheet_directory() . '/style.css'));
    if (is_front_page() && home_template_value() === 'corporate') {
        wp_enqueue_style('starter-home', get_theme_file_uri('assets/css/home.css'), ['starter'], (string) filemtime(get_theme_file_path('assets/css/home.css')));
    }
    if (is_page_template('page-templates/about.php')) {
        wp_enqueue_style('starter-about', get_theme_file_uri('assets/css/about.css'), ['starter'], (string) filemtime(get_theme_file_path('assets/css/about.css')));
        wp_enqueue_script('starter-about-gallery', get_theme_file_uri('assets/js/about-gallery.js'), [], (string) filemtime(get_theme_file_path('assets/js/about-gallery.js')), ['in_footer' => true]);
    }
    if (is_page_template('page-templates/contact.php')) {
        wp_enqueue_style('starter-contact', get_theme_file_uri('assets/css/contact.css'), ['starter'], (string) filemtime(get_theme_file_path('assets/css/contact.css')));
    }
    if (is_singular('starter_industry')) {
        wp_enqueue_style('starter-industry', get_theme_file_uri('assets/css/industry.css'), ['starter'], (string) filemtime(get_theme_file_path('assets/css/industry.css')));
    }
    if (is_singular('post')) {
        wp_enqueue_style('starter-article', get_theme_file_uri('assets/css/article.css'), ['starter'], (string) filemtime(get_theme_file_path('assets/css/article.css')));
        wp_enqueue_script('starter-article', get_theme_file_uri('assets/js/article.js'), [], (string) filemtime(get_theme_file_path('assets/js/article.js')), ['in_footer' => true]);
    }
    wp_enqueue_style('starter-navigation', get_theme_file_uri('assets/css/navigation.css'), ['starter'], (string) filemtime(get_theme_file_path('assets/css/navigation.css')));
    $colors = [];
    foreach (['primary' => '#202826', 'accent' => '#215D50', 'surface' => '#F7F7F7'] as $name => $default) {
        $colors[] = '--brand-' . $name . ':' . (sanitize_hex_color((string) get_theme_mod('brand_' . $name, $default)) ?: $default);
    }
    wp_add_inline_style('starter', ':root{' . implode(';', $colors) . '}');
    wp_enqueue_script('starter-nav', get_theme_file_uri('assets/js/nav.js'), [], (string) filemtime(get_theme_file_path('assets/js/nav.js')), ['in_footer' => true]);
    wp_enqueue_script('starter-navigation', get_theme_file_uri('assets/js/navigation.js'), [], (string) filemtime(get_theme_file_path('assets/js/navigation.js')), ['in_footer' => true]);
});

add_action('wp_head', static function (): void {
    $font = get_theme_file_uri('assets/fonts/InterVariable.woff2');
    echo '<link rel="preload" href="' . esc_url($font) . '" as="font" type="font/woff2" crossorigin>' . "\n";
    echo '<meta name="theme-color" content="#ffffff">' . "\n";
    $icon = get_theme_file_uri('assets/favicon.svg');
    if (!has_site_icon()) echo '<link rel="icon" href="' . esc_url($icon) . '" type="image/svg+xml">' . "\n";
}, 1);

/* Highlight the matching section (Products / Industries / Knowledge) on
   single posts, terms and archives even though the menu only lists the
   archive pages. */
add_filter('nav_menu_css_class', static function (array $classes, $item): array {
    $map = [
        'starter_product' => 'products',
        'starter_category' => 'products',
        'starter_industry' => 'industries',
        'starter_guide' => 'guides',
    ];
    $type = '';
    if (is_singular(array_keys($map))) {
        $type = get_post_type();
    } elseif (is_post_type_archive(array_keys($map))) {
        $type = get_query_var('post_type');
    } elseif (is_tax('product_collection')) {
        $type = 'starter_product';
    }
    if ($type === '' || !isset($map[$type])) return $classes;
    $candidates = [trailingslashit((string) get_post_type_archive_link($type === 'starter_category' ? 'starter_product' : $type))];
    if (is_singular($type)) {
        $terms = get_the_terms(get_the_ID(), 'product_collection');
        if (is_array($terms) && $terms) {
            foreach ($terms as $term) {
                $link = get_term_link($term);
                if (!is_wp_error($link)) $candidates[] = trailingslashit((string) $link);
            }
        }
    }
    if (in_array(trailingslashit((string) ($item->url ?? '')), $candidates, true)) {
        $classes[] = 'current-menu-item';
        $classes[] = 'current-menu-ancestor';
    }
    return $classes;
}, 10, 2);

function field_text(string $name, $object = false): string {
    if ($object === 'option') return field_option($name);
    $value = function_exists('get_field') ? get_field($name, $object) : '';
    return is_string($value) ? $value : '';
}

function field_option(string $name): string {
    if (!function_exists('get_field')) return '';
    $profile_id = function_exists('Starter\\Model\\profile_post_id') ? \Starter\Model\profile_post_id() : 0;
    $value = get_field($name, $profile_id ?: 'option');
    return is_string($value) ? $value : '';
}

/** Parse plain lines from an ACF option field (with built-in fallback). */
function field_lines_option(string $name, string $fallback = ''): array {
    $raw = field_option($name);
    if (trim($raw) === '') $raw = $fallback;
    return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $raw) ?: []), static fn ($line) => $line !== ''));
}

/** Parse "Label | Value" lines from a field (with built-in fallback). */
function field_rows(string $name, $source = null, string $fallback = ''): array {
    $raw = field_text($name, $source);
    if (trim($raw) === '') $raw = $fallback;
    return lines_rows($raw);
}

/** Parse plain lines from a field (with built-in fallback). */
function field_lines(string $name, $source = null, string $fallback = ''): array {
    $raw = field_text($name, $source);
    if (trim($raw) === '') $raw = $fallback;
    return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $raw) ?: []), static fn ($line) => $line !== ''));
}

function lines_rows(string $raw): array {
    if (trim($raw) === '') return [];
    $rows = [];
    foreach (preg_split('/\r?\n/', $raw) as $line) {
        if (trim($line) === '') continue;
        $parts = array_map('trim', explode('|', $line, 2));
        if (count($parts) === 2 && $parts[0] !== '') {
            $rows[] = ['label' => $parts[0], 'value' => $parts[1]];
        }
    }
    return $rows;
}

/** Parse `Value | Label` rows for metrics where the measured value leads. */
function value_rows(string $raw): array {
    if (trim($raw) === '') return [];
    $rows = [];
    foreach (preg_split('/\r?\n/', $raw) as $line) {
        if (trim($line) === '') continue;
        $parts = array_map('trim', explode('|', $line, 2));
        if (count($parts) === 2 && $parts[0] !== '') {
            $rows[] = ['value' => $parts[0], 'label' => $parts[1]];
        }
    }
    return $rows;
}

/** Generic starter fallbacks; business copy belongs in editable fields. */
function category_defaults(string $slug): array {
    return [
        'intro' => '',
        'features' => '',
        'applications' => '',
        'faq' => '',
    ];
}

/** Starter ships no business claims; ACF seed and admin content own those. */
function factory_defaults(): array {
    return ['intro' => '', 'stats' => '', 'capabilities' => ''];
}

/** First sentences of raw content, heading-safe (tags become spaces). */
function trimmed_intro(int $words = 26): string {
    $text = preg_replace('/<[^>]+>/', ' ', (string) get_the_content());
    $text = preg_replace('/\s+/', ' ', trim((string) $text));
    return wp_trim_words($text, $words, '…');
}

/** Breadcrumb trail for inner pages. */
function breadcrumbs(): void {
    $trail = [['label' => 'Home', 'url' => home_url('/')]];
    $current = '';

    if (is_singular('starter_product')) {
        $trail[] = ['label' => 'Products', 'url' => get_post_type_archive_link('starter_product')];
        $terms = get_the_terms(get_the_ID(), 'product_collection');
        if (is_array($terms) && $terms && !is_wp_error($terms[0])) {
            $trail[] = ['label' => $terms[0]->name, 'url' => get_term_link($terms[0])];
        }
        $current = get_the_title();
    } elseif (is_singular('starter_industry')) {
        $trail[] = ['label' => 'Industries', 'url' => get_post_type_archive_link('starter_industry')];
        $current = get_the_title();
    } elseif (is_singular('starter_guide')) {
        $trail[] = ['label' => 'Knowledge', 'url' => get_post_type_archive_link('starter_guide')];
        $current = get_the_title();
    } elseif (is_singular()) {
        $current = get_the_title();
    } elseif (is_post_type_archive('starter_product')) {
        $current = 'Product catalogue';
    } elseif (is_post_type_archive('starter_industry')) {
        $current = 'Industry solutions';
    } elseif (is_post_type_archive('starter_guide')) {
        $current = 'Knowledge guides';
    } elseif (is_tax()) {
        $trail[] = ['label' => 'Products', 'url' => get_post_type_archive_link('starter_product')];
        $current = single_term_title('', false);
    } elseif (is_search()) {
        $current = 'Search results';
    } elseif (is_404()) {
        $current = 'Page not found';
    } else {
        $current = wp_strip_all_tags(get_the_archive_title());
    }

    $html = '<nav class="breadcrumbs" aria-label="Breadcrumb"><ol>';
    foreach ($trail as $crumb) {
        $html .= '<li><a href="' . esc_url($crumb['url']) . '">' . esc_html($crumb['label']) . '</a></li>';
    }
    $html .= '<li aria-current="page">' . esc_html((string) $current) . '</li></ol></nav>';
    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above
}

/** Neutral aspect-ratio placeholder for the starter template. */
function media_placeholder(string $kind = 'product', string $label = '', string $class = '', string $dimension = ''): string {
    $labels = ['product' => 'Product image', 'category' => 'Range image', 'hero' => 'Company image', 'industry' => 'Application image', 'guide' => 'Article image', 'post' => 'Article image'];
    $text = $labels[$kind] ?? 'Image';
    $aria = ($label !== '' ? $label . ': ' : '') . 'image not available';
    return '<span class="' . esc_attr(trim('media-fallback ' . $class)) . '" role="img" aria-label="' . esc_attr($aria) . '"><svg viewBox="0 0 48 48" fill="none" aria-hidden="true"><rect x="6" y="8" width="36" height="32" rx="3"/><circle cx="17" cy="18" r="3"/><path d="m7 33 10-9 7 6 7-11 10 14"/></svg><span class="media-label">' . esc_html($text) . '</span><span class="media-note">Image not available</span></span>';
}

/** Featured image or styled placeholder for a post. */
function card_media(string $kind = 'product'): void {
    if (has_post_thumbnail()) {
        the_post_thumbnail('medium_large', ['class' => 'card-img']);
    } else {
        echo media_placeholder($kind);
    }
}

function spec_rows($source = null): array {
    return field_rows('spec_table', $source);
}

function contact_url(string $product = ''): string {
    $url = home_url('/contact/');
    return $product === '' ? $url : add_query_arg('product', $product, $url);
}

/**
 * Public contacts use Business Profile; display settings remain in the Customizer.
 */
function get_setting(string $key, string $fallback = ''): string {
    if (function_exists('Starter\\Model\\profile_contact_field_keys')
        && isset(\Starter\Model\profile_contact_field_keys()[$key])) {
        return \Starter\Model\profile_contact_value($key, $fallback);
    }
    $val = get_theme_mod($key, '');
    return $val !== '' ? $val : $fallback;
}

function term_cards(string $taxonomy, string $title_tag = 'h3'): void {
    $title_tag = $title_tag === 'h2' ? 'h2' : 'h3';
    $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
    if (is_wp_error($terms) || !$terms) return;
    // Show selectable ranges; their parent overview remains reachable through the menu and breadcrumbs.
    $parent_ids = array_values(array_unique(array_filter(array_map(static fn ($term): int => (int) $term->parent, $terms))));
    $terms = array_values(array_filter($terms, static fn ($term): bool => !in_array((int) $term->term_id, $parent_ids, true)));
    echo '<div class="grid term-grid">';
    foreach ($terms as $index => $term) {
        $url = get_term_link($term);
        if (is_wp_error($url)) continue;
        $parent = $term->parent ? get_term($term->parent, $taxonomy) : null;
        $parent_label = $parent instanceof \WP_Term ? $parent->name : 'Product family';
        $image = function_exists('get_field') ? get_field('category_card_image', $taxonomy === 'product_collection' ? category_content_object((int) $term->term_id) : $taxonomy . '_' . $term->term_id) : null;
        if (!$image && function_exists('get_field')) $image = get_field('category_hero_image', $taxonomy === 'product_collection' ? category_content_object((int) $term->term_id) : $taxonomy . '_' . $term->term_id);
        $image_id = is_array($image) ? (int) ($image['ID'] ?? $image['id'] ?? 0) : (int) $image;
        $media = $image_id > 0
            ? wp_get_attachment_image($image_id, 'medium_large', false, ['loading' => 'lazy', 'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true) ?: $term->name])
            : media_placeholder('category', $term->name);
        printf(
            '<a class="term-card" href="%1$s"><span class="term-media">%5$s</span><span class="term-card-copy"><%7$s>%2$s</%7$s><p>%4$s</p><span class="term-more">Explore range <span aria-hidden="true">↗</span></span></span></a>',
            esc_url($url),
            esc_html($term->name),
            esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)),
            esc_html(wp_trim_words($term->description, 18)),
            $media,
            esc_html($parent_label),
            $title_tag
        );
    }
    echo '</div>';
}

function term_icon_svg(string $name): string {
    $paths = [
        'factory' => '<path d="M3 21V9l6 4V9l6 4V9l6 4v8z"/><path d="M17 9V3h3v8"/><path d="M7 17h2M12 17h2M17 17h2"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.5 4.5l2 2M17.5 17.5l2 2M19.5 4.5l-2 2M6.5 17.5l-2 2"/>',
        'building' => '<rect x="4" y="3" width="12" height="18" rx="1"/><path d="M16 9h4v12h-4"/><path d="M8 7h2M12 7h2M8 11h2M12 11h2M8 15h2M12 15h2"/>',
        'shield' => '<path d="M12 2l8 3v6c0 5-3.5 9-8 11-4.5-2-8-6-8-11V5z"/><path d="M9 12l2 2 4-4"/>',
    ];
    $path = $paths[$name] ?? $paths['factory'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}
add_action('widgets_init', function () {
    register_sidebar(['name' => __('Sidebar', 'b2b-starter'), 'id' => 'sidebar-1', 'before_widget' => '<section class="widget">', 'after_widget' => '</section>', 'before_title' => '<h2 class="widget-title">', 'after_title' => '</h2>']);
});

/**
 * Customizer: site-wide editable settings.
 * All values accessible via Starter\Theme\get_setting( $key ).
 */
add_action('customize_register', function ($wp_customize) {
    $wp_customize->add_section('starter_brand', ['title' => __('Brand colors', 'b2b-starter'), 'priority' => 29]);
    foreach (['primary' => ['label' => 'Primary ink', 'default' => '#202826'], 'accent' => ['label' => 'Accent', 'default' => '#215D50'], 'surface' => ['label' => 'Surface', 'default' => '#F7F7F7']] as $name => $color) {
        $key = 'brand_' . $name;
        $wp_customize->add_setting($key, ['default' => $color['default'], 'sanitize_callback' => 'sanitize_hex_color']);
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, $key, [
            'label' => __($color['label'], 'b2b-starter'), 'section' => 'starter_brand',
        ]));
    }
    $profile_id = function_exists('Starter\\Model\\profile_post_id') ? \Starter\Model\profile_post_id() : 0;
    $profile_contacts = function_exists('acf_get_field_group')
        && $profile_id > 0
        && acf_get_field_group('group_profile_contacts');
    $wp_customize->add_section('contact_info', [
        'title'    => $profile_contacts ? __('Brand & display', 'b2b-starter') : __('Contact & Brand Info', 'b2b-starter'),
        'priority' => 30,
    ]);
    $fields = [
        'header_cta_label' => ['label' => __('Header inquiry button', 'b2b-starter'), 'default' => 'Request a quote', 'type' => 'text'],
        'contact_email'    => ['label' => __('Contact Email', 'b2b-starter'), 'default' => '', 'type' => 'text'],
        'contact_phone'    => ['label' => __('Phone', 'b2b-starter'), 'default' => '', 'type' => 'text'],
        'contact_hours'    => ['label' => __('Working Hours', 'b2b-starter'), 'default' => '', 'type' => 'text'],
        'topbar_text'      => ['label' => __('Topbar Text', 'b2b-starter'), 'default' => '', 'type' => 'text'],
        'footer_about'     => ['label' => __('Footer About Text', 'b2b-starter'), 'default' => '', 'type' => 'textarea'],
        'social_linkedin'  => ['label' => __('LinkedIn URL', 'b2b-starter'), 'default' => '', 'type' => 'url'],
        'social_youtube'   => ['label' => __('YouTube URL', 'b2b-starter'), 'default' => '', 'type' => 'url'],
        'stat_1_value'     => ['label' => __('Stat 1 Value', 'b2b-starter'), 'default' => '', 'type' => 'text'],
        'stat_1_label'     => ['label' => __('Stat 1 Label', 'b2b-starter'), 'default' => '', 'type' => 'text'],
        'stat_2_value'     => ['label' => __('Stat 2 Value', 'b2b-starter'), 'default' => '', 'type' => 'text'],
        'stat_2_label'     => ['label' => __('Stat 2 Label', 'b2b-starter'), 'default' => '', 'type' => 'text'],
        'stat_3_value'     => ['label' => __('Stat 3 Value', 'b2b-starter'), 'default' => '', 'type' => 'text'],
        'stat_3_label'     => ['label' => __('Stat 3 Label', 'b2b-starter'), 'default' => '', 'type' => 'text'],
        'cta_title'        => ['label' => __('CTA Title', 'b2b-starter'), 'default' => '', 'type' => 'text'],
        'cta_text'         => ['label' => __('CTA Text', 'b2b-starter'), 'default' => '', 'type' => 'textarea'],
    ];
    // Never expose a legacy editor for values already owned by the Profile.
    foreach (['contact_email', 'contact_phone', 'contact_hours'] as $name) {
        if ($profile_contacts || ($profile_id && metadata_exists('post', $profile_id, $name))) unset($fields[$name]);
    }
    foreach ($fields as $key => $args) {
        $wp_customize->add_setting($key, [
            'default'           => $args['default'],
            'sanitize_callback' => $args['type'] === 'textarea' ? 'wp_kses_post' : 'sanitize_text_field',
        ]);
        $wp_customize->add_control($key, [
            'label'   => $args['label'],
            'section' => 'contact_info',
            'type'    => $args['type'] === 'textarea' ? 'textarea' : 'text',
        ]);
    }
});
// Preview sites must revalidate HTML after CMS edits, even when the host caches pages.
add_filter('wp_headers', static function (array $headers): array {
    if ((int) get_option('blog_public', 1) === 0) {
        $headers['Cache-Control'] = 'private, no-store, max-age=0';
        $headers['Pragma'] = 'no-cache';
    }
    return $headers;
});


// Render one managed RFQ instance, shared by inline and floating entry points.
add_filter('do_shortcode_tag', static function ($output, $tag) {
    if ($tag === 'starter_rfq_form') $GLOBALS['axis_rfq_rendered'] = true;
    return $output;
}, 10, 2);

add_action('wp_enqueue_scripts',static function():void{
    if(!is_front_page() || home_template_value() !== 'corporate') return;
    wp_enqueue_style('axis-capabilities',get_theme_file_uri('assets/css/axis-capabilities.css'),['axis-brand'],(string)filemtime(get_theme_file_path('assets/css/axis-capabilities.css')));
    wp_enqueue_style('axis-support',get_theme_file_uri('assets/css/axis-support.css'),['axis-brand'],(string)filemtime(get_theme_file_path('assets/css/axis-support.css')));
    wp_enqueue_script('axis-support',get_theme_file_uri('assets/js/axis-support.js'),[],(string)filemtime(get_theme_file_path('assets/js/axis-support.js')),true);
    wp_enqueue_script('axis-capabilities',get_theme_file_uri('assets/js/axis-capabilities.js'),[],(string)filemtime(get_theme_file_path('assets/js/axis-capabilities.js')),true);
},100);
