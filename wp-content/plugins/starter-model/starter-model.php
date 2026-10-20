<?php
/**
 * Plugin Name: b2b-starter Content Model
 * Description: Manufacturer content model: products, categories, editable company pages and inquiries.
 * Version: 2.22.27
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Requires Plugins: advanced-custom-fields-pro
 */
namespace Starter\Model;

defined('ABSPATH') || exit;

require_once __DIR__ . '/includes/author-profile.php';
require_once __DIR__ . '/includes/contact-content.php';
require_once __DIR__ . '/includes/category-pages.php';
require_once __DIR__ . '/includes/category-fields.php';
require_once __DIR__ . '/includes/about-content.php';
require_once __DIR__ . '/includes/home-content.php';
require_once __DIR__ . '/includes/axis-template-suite.php';
require_once __DIR__ . '/includes/axis-suite-extra.php';
require_once __DIR__ . '/includes/approved-design.php';
require_once __DIR__ . '/includes/axis-content-types.php';
require_once __DIR__ . '/includes/industry-fields.php';
require_once __DIR__ . '/includes/acf-rest-revisions.php';
AcfRestRevisions::boot();
require_once __DIR__ . '/includes/inquiry-attribution.php';
require_once __DIR__ . '/includes/inquiry-thank-you.php';
InquiryThankYou\boot();
register_activation_hook(__FILE__, static function (): void { InquiryThankYou\initialize(); });
require_once __DIR__ . '/includes/inquiry-attachments.php';
\AxisTemplate\Attachments\Module::boot(plugins_url('assets', __FILE__));
require_once __DIR__ . '/modules/footer-fields.php';
\AxisTemplate\Attribution\Module::boot(plugins_url('assets/js/inquiry-attribution.js', __FILE__));

function content_labels(string $singular, string $plural, string $menu_name = ''): array {
    $menu_name = $menu_name ?: $plural;
    return [
        'name' => _x($plural, 'Post type general name', 'b2b-starter'),
        'singular_name' => _x($singular, 'Post type singular name', 'b2b-starter'),
        'menu_name' => _x($menu_name, 'Admin menu name', 'b2b-starter'),
        'all_items' => sprintf(__('All %s', 'b2b-starter'), $plural),
        'add_new' => __('Add new', 'b2b-starter'),
        'add_new_item' => sprintf(__('Add new %s', 'b2b-starter'), $singular),
        'edit_item' => sprintf(__('Edit %s', 'b2b-starter'), $singular),
        'new_item' => sprintf(__('New %s', 'b2b-starter'), $singular),
        'view_item' => sprintf(__('View %s', 'b2b-starter'), $singular),
        'view_items' => sprintf(__('View %s', 'b2b-starter'), $plural),
        'search_items' => sprintf(__('Search %s', 'b2b-starter'), $plural),
        'not_found' => sprintf(__('No %s found', 'b2b-starter'), strtolower($plural)),
        'not_found_in_trash' => sprintf(__('No %s found in trash', 'b2b-starter'), strtolower($plural)),
    ];
}

function register_model(): void {
    register_post_type('starter_product', [
        'labels' => content_labels('Product', 'Products'),
        'public' => true, 'publicly_queryable' => true, 'show_ui' => true, 'show_in_nav_menus' => true,
        'show_in_rest' => true, 'rest_base' => 'products', 'has_archive' => 'products',
        'rewrite' => ['slug' => 'products', 'with_front' => false], 'menu_icon' => 'dashicons-lightbulb',
        'menu_position' => 20, 'map_meta_cap' => true, 'capability_type' => 'post',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields'],
    ]);

    register_taxonomy('product_collection', ['starter_product'], [
        'labels' => [
            'name' => _x('Product Categories', 'Taxonomy general name', 'b2b-starter'),
            'singular_name' => _x('Product Category', 'Taxonomy singular name', 'b2b-starter'),
            'menu_name' => _x('Categories', 'Admin menu name', 'b2b-starter'),
            'all_items' => __('All product categories', 'b2b-starter'),
            'edit_item' => __('Edit product category', 'b2b-starter'),
            'view_item' => __('View product category', 'b2b-starter'),
            'add_new_item' => __('Add new product category', 'b2b-starter'),
            'search_items' => __('Search product categories', 'b2b-starter'),
        ],
        'public' => true, 'publicly_queryable' => true, 'show_ui' => true, 'show_in_nav_menus' => true,
        'show_in_rest' => true, 'rest_base' => 'product-categories', 'hierarchical' => true,
        'show_admin_column' => true, 'rewrite' => ['slug' => 'product-category', 'with_front' => false],
        'map_meta_cap' => true,
    ]);

    register_category_page_type();

    // Industry solutions are a default capability; projects may explicitly disable the UI.
    $enable_industry = (bool) apply_filters('starter_enable_industry', true);
    register_post_type('starter_industry', [
        'labels' => content_labels('Industry solution', 'Industry Solutions', 'Industry'),
        'public' => true, 'publicly_queryable' => true, 'show_ui' => $enable_industry, 'show_in_nav_menus' => $enable_industry,
        'show_in_rest' => true, 'rest_base' => 'industries', 'has_archive' => 'industries',
        'rewrite' => ['slug' => 'industries', 'with_front' => false], 'menu_icon' => 'dashicons-building',
        'menu_position' => 23, 'map_meta_cap' => true, 'capability_type' => 'post',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields'],
    ]);

    // Legacy records remain readable; editorial UI requires an explicit project opt-in.
    $enable_guide = (bool) apply_filters('starter_enable_guide', false);
    register_post_type('starter_guide', [
        'labels' => content_labels('Knowledge guide', 'Knowledge Guides', 'Guides'),
        'public' => true, 'publicly_queryable' => true, 'show_ui' => $enable_guide, 'show_in_nav_menus' => $enable_guide,
        'show_in_rest' => true, 'rest_base' => 'guides', 'has_archive' => 'guides',
        'rewrite' => ['slug' => 'guides', 'with_front' => false], 'menu_icon' => 'dashicons-book',
        'menu_position' => 25, 'map_meta_cap' => true, 'capability_type' => 'post',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields'],
    ]);

    register_post_type('starter_profile', [
        'labels' => content_labels('Business profile', 'Business Profile', 'Business Profile'),
        'public' => false, 'publicly_queryable' => false, 'exclude_from_search' => true,
        'show_ui' => true, 'show_in_menu' => true, 'show_in_rest' => false,
        'menu_icon' => 'dashicons-building', 'menu_position' => 26,
        'map_meta_cap' => true, 'capability_type' => 'post',
        'capabilities' => ['create_posts' => 'do_not_allow'],
        'supports' => ['title', 'revisions'],
    ]);
}
add_action('init', __NAMESPACE__ . '\\register_model');

/** Compatibility is read-only until the editor saves the new field. A saved empty value stays empty. */
function legacy_row_fields(): array {
    return [
        'product_quick_specs' => 'quick_specs', 'product_specifications' => 'spec_table', 'product_faq_items' => 'product_faq',
        'category_fact_items' => 'category_key_facts', 'category_spec_items' => 'category_specifications', 'category_faq_items' => 'category_faq',
        'category_feature_items' => 'category_features', 'category_selection_items' => 'category_selection_guide',
        'category_use_case_items' => 'category_use_cases', 'category_process_items' => 'category_process',
        'factory_stat_items' => 'factory_stats', 'factory_capability_items' => 'factory_capabilities', 'factory_process_items' => 'factory_process',
        'landing_feature_items' => 'landing_features', 'contact_process_items' => 'contact_process',
    ];
}

foreach (['product_details_body', 'about_story', 'landing_body', 'factory_body', 'industry_body'] as $name) {
    add_filter('acf/load_value/name=' . $name, static function ($value, $post_id) use ($name) {
        if (is_numeric($post_id) && !metadata_exists('post', (int) $post_id, $name)) {
            return (string) get_post_field('post_content', (int) $post_id);
        }
        return $value;
    }, 10, 2);
}

foreach (legacy_row_fields() as $name => $legacy) {
    add_filter('acf/load_value/name=' . $name, static function ($value, $post_id, $field) use ($name, $legacy) {
        $object = acf_decode_post_id($post_id);
        if (!in_array($object['type'], ['post', 'term'], true) || metadata_exists($object['type'], $object['id'], $name)) return $value;
        // ACF can invoke name filters before resolving a complete field definition.
        if (count($field['sub_fields'] ?? []) < 2) return $value;
        $raw = (string) get_metadata($object['type'], $object['id'], $legacy, true);
        $rows = [];
        foreach (preg_split('/\r?\n/', $raw) ?: [] as $line) {
            $parts = array_map('trim', explode('|', $line, 2));
            if ($parts[0] === '') continue;
            $rows[] = [$field['sub_fields'][0]['key'] => $parts[0], $field['sub_fields'][1]['key'] => $parts[1] ?? ''];
        }
        return $rows ?: $value;
    }, 10, 3);
    // Keep the old field registered for API reads, but present one editor per module.
    add_filter('acf/prepare_field/name=' . $legacy, '__return_false');
}
add_filter('acf/load_value/name=product_gallery', static function ($value, $post_id) {
    if (!is_numeric($post_id) || metadata_exists('post', (int) $post_id, 'product_gallery')) return $value;
    $ids = [];
    foreach (range(1, 5) as $slot) {
        $id = (int) get_post_meta((int) $post_id, 'product_gallery_' . $slot, true);
        if ($id > 0) $ids[] = $id;
    }
    return $ids ?: $value;
}, 10, 2);
foreach (range(1, 5) as $slot) add_filter('acf/prepare_field/name=product_gallery_' . $slot, '__return_false');

add_action('current_screen', static function ($screen): void {
    if ($screen->base !== 'post' || !function_exists('acf_get_field')) return;
    $body_fields = ['starter_product' => 'field_p_details_body', 'starter_industry' => 'field_i_body', 'starter_category' => 'field_tc_long_description'];
    if (isset($body_fields[$screen->post_type]) && acf_get_field($body_fields[$screen->post_type])) {
        remove_post_type_support($screen->post_type, 'editor');
    }
    if ($screen->post_type !== 'page') return;
    $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
    $template = get_page_template_slug($post_id);
    if ($template === 'page-templates/resource-center.php') add_post_type_support('page', 'excerpt');
    $is_home = $post_id > 0 && $post_id === (int) get_option('page_on_front');
    $acf_only = $is_home || in_array($template, ['page-templates/about.php', 'page-templates/landing.php', 'page-templates/factory-capability.php', 'page-templates/product-catalogue.php', 'page-templates/resource-center.php'], true)
        || ($template === 'page-templates/contact.php' && trim((string) get_post_field('post_content', $post_id)) === '');
    // Contact bodies may contain an existing form shortcode: retain that editing entry.
    if ($acf_only) remove_post_type_support('page', 'editor');
});

/** Resource automatic descriptions use only the saved native summary. */
function resource_description(string $description, string $channel = 'seo'): string {
    $post_id = (int) get_queried_object_id();
    if (!is_singular('page') || $post_id < 1 || get_page_template_slug($post_id) !== 'page-templates/resource-center.php') return $description;

    $manual_keys = ['rank_math_description'];
    if ($channel === 'facebook') array_unshift($manual_keys, 'rank_math_facebook_description');
    if ($channel === 'twitter') {
        // Rank Math inherits Facebook by default; its saved off/false values disable that inheritance.
        $inherit_facebook = !in_array(get_post_meta($post_id, 'rank_math_twitter_use_facebook', true), ['off', 'false'], true);
        array_unshift($manual_keys, $inherit_facebook ? 'rank_math_facebook_description' : 'rank_math_twitter_description');
    }
    foreach ($manual_keys as $key) {
        if (trim((string) get_post_meta($post_id, $key, true)) !== '') return $description;
    }
    return wp_strip_all_tags((string) get_post_field('post_excerpt', $post_id, 'raw'), true);
}
add_filter('rank_math/frontend/description', __NAMESPACE__ . '\\resource_description');
add_filter('rank_math/opengraph/facebook/og_description', static fn(string $description): string => resource_description($description, 'facebook'));
add_filter('rank_math/opengraph/twitter/twitter_description', static fn(string $description): string => resource_description($description, 'twitter'));

/** Shared fields have one private Business Profile record. */
function profile_post_id(): int {
    $id = (int) get_option('starter_profile_post_id');
    if ($id > 0 && get_post_type($id) === 'starter_profile' && get_post_status($id) !== 'trash') return $id;
    $posts = get_posts(['post_type' => 'starter_profile', 'post_status' => 'any', 'numberposts' => 1, 'orderby' => 'ID', 'order' => 'ASC']);
    return $posts ? (int) $posts[0]->ID : 0;
}

function ensure_profile_post(): int {
    $id = profile_post_id();
    if (!$id) {
        $id = wp_insert_post(['post_type' => 'starter_profile', 'post_status' => 'publish', 'post_title' => 'Business profile'], true);
        if (is_wp_error($id)) return 0;
    }
    update_option('starter_profile_post_id', $id);
    if (!get_option('starter_profile_migrated') && function_exists('get_field') && function_exists('update_field')) {
        $migrated = true;
        foreach (['factory_intro', 'factory_stats', 'factory_capabilities', 'factory_certifications', 'factory_process', 'factory_quality_tests', 'factory_markets', 'product_archive_description', 'industry_archive_description', 'guide_archive_description', 'news_archive_description', 'not_found_description', 'response_promise', 'about_cta_title', 'about_cta_text', 'about_cta_button'] as $name) {
            $legacy = get_field($name, 'option');
            // A saved empty value is intentional; only absent fields inherit legacy options.
            if (metadata_exists('post', $id, $name)) continue;
            if (is_string($legacy) && trim($legacy) !== '') {
                update_field($name, $legacy, $id);
                if (get_field($name, $id) !== $legacy) $migrated = false;
            }
        }
        if ($migrated) update_option('starter_profile_migrated', 1);
    }
    return (int) $id;
}
add_action('admin_init', __NAMESPACE__ . '\\ensure_profile_post');

/** Give structured content types a locked native-editor skeleton. */
function register_editor_templates(): void {
    $guide_template = [
        ['core/heading', ['level' => 2, 'content' => 'Who this guide is for']],
        ['core/paragraph', ['content' => 'Define the buyer role, application and intended outcome.']],
        ['core/heading', ['level' => 2, 'content' => 'Selection criteria']],
        ['core/list', ['values' => '<li>Criterion one</li><li>Criterion two</li><li>Criterion three</li>']],
        ['core/heading', ['level' => 2, 'content' => 'Next step']],
        ['core/paragraph', ['content' => 'Explain what the buyer should send for an accurate response.']],
    ];

    $guide = get_post_type_object('starter_guide');
    if (!$guide) return;
    $guide->template = $guide_template;
    $guide->template_lock = 'all';
}
add_action('init', __NAMESPACE__ . '\\register_editor_templates', 20);

register_activation_hook(__FILE__, static function (): void { register_model(); ensure_profile_post(); flush_rewrite_rules(); });
register_deactivation_hook(__FILE__, static function (): void {
    wp_clear_scheduled_hook('wb_inquiry_attachment_cleanup');
    foreach (['starter_product', 'starter_category', 'starter_industry', 'starter_guide', 'starter_profile'] as $type) unregister_post_type($type);
    unregister_taxonomy('product_collection');
    flush_rewrite_rules();
});

/** Create the sample RFQ form once when Fluent Forms is available. */
function ensure_rfq_form_settings(int $form_id): void {
    global $wpdb;
    $meta_table = $wpdb->prefix . 'fluentform_form_meta';
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$meta_table} WHERE form_id = %d AND meta_key = 'formSettings'",
        $form_id
    ));
    if ($exists) {
        return;
    }
    $settings = [
        'formExtraClasses' => [],
        'confirmation' => InquiryThankYou\confirmation(),
    ];
    $wpdb->insert($meta_table, [
        'form_id' => $form_id,
        'meta_key' => 'formSettings',
        'value' => wp_json_encode($settings),
    ]);
}

/** Canonical preset; the legacy variant is used only to recognize an unchanged older form. */
function default_rfq_form_fields(bool $legacy_product = false, bool $attachments = true): array {
    $field = static function (string $element, string $name, string $label, bool $required = false, string $type = 'text'): array {
        return [
            'element' => $element,
            'attributes' => ['name' => $name, 'type' => $type, 'class' => '', 'placeholder' => ''],
            'settings' => [
                'label' => $label,
                'admin_field_label' => $label,
                'validation_rules' => $required ? ['required' => ['value' => true, 'message' => 'This field is required.']] : [],
                'conditional_logic' => [],
            ],
            'editor_options' => ['title' => $label, 'icon_class' => 'ff-edit-text'],
        ];
    };
    $fields = [
        $field('input_text', 'full_name', 'Full name', true),
        $field('input_email', 'email', 'Work email', true, 'email'),
        $field('input_text', 'company', 'Company'),
        $field('input_text', 'country', 'Country / region'),
        $field('textarea', 'message', 'Requirements', true, 'textarea'),
        [
            'element' => 'custom_html',
            'attributes' => ['name' => 'gdpr_note', 'type' => '', 'class' => '', 'placeholder' => ''],
            'settings' => [
                'label' => '',
                'admin_field_label' => '',
                'validation_rules' => [],
                'conditional_logic' => [],
                'html' => '<p>By submitting this form you agree to be contacted about your enquiry.</p>',
            ],
            'editor_options' => ['title' => '', 'icon_class' => 'ff-edit-text'],
        ],
    ];

    if ($attachments) array_splice($fields, 5, 0, [\AxisTemplate\Attachments\Module::component()]);
    if ($legacy_product) array_splice($fields, 4, 0, [$field('input_text', 'product', 'Product or project type')]);
    return $fields;
}

/** Upgrade only the unchanged Starter preset; custom forms and existing entries stay intact. */
function upgrade_default_rfq_form(int $form_id): void {
    global $wpdb;
    $table = $wpdb->prefix . 'fluentform_forms';
    $definition = json_decode((string) $wpdb->get_var($wpdb->prepare("SELECT form_fields FROM {$table} WHERE id = %d", $form_id)), true);
    $fields = $definition['fields'] ?? [];
    // Attribution owns its hidden fields; preserve them while recognizing the unchanged RFQ preset.
    $attribution_fields = array_values(array_filter($fields, static fn($field): bool => is_array($field) && \AxisTemplate\Attribution\Module::is_managed_field($field)));
    $fields = array_values(array_filter($fields, static fn($field): bool => !is_array($field) || !\AxisTemplate\Attribution\Module::is_managed_field($field)));
    $current = default_rfq_form_fields();
    if ($fields !== $current) {
        if ($fields !== default_rfq_form_fields(true) && $fields !== default_rfq_form_fields(false, false) && $fields !== default_rfq_form_fields(true, false)) return;
        $definition['fields'] = array_merge($current, $attribution_fields);
        if ($wpdb->update($table, ['form_fields' => wp_json_encode($definition)], ['id' => $form_id]) === false) return;
    }
    $meta_table = $wpdb->prefix . 'fluentform_form_meta';
    foreach ($wpdb->get_results($wpdb->prepare("SELECT id, value FROM {$meta_table} WHERE form_id = %d AND meta_key = 'notifications'", $form_id)) as $row) {
        $feed = json_decode((string) $row->value, true);
        if (($feed['name'] ?? '') === 'Admin notification') {
            $feed['wb_inquiry_attachments'] = true;
            if (($feed['message'] ?? '') === '<p>{all_data}</p>') $feed['message'] .= '<p>Inquiry page: {submission.source_url}</p>';
            $wpdb->update($meta_table, ['value' => wp_json_encode($feed)], ['id' => $row->id]);
        }
    }
}

function ensure_rfq_form(): int {
    global $wpdb;
    $forms_table = $wpdb->prefix . 'fluentform_forms';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $forms_table)) !== $forms_table) {
        return 0;
    }

    $form_id = (int) get_option('starter_rfq_form_id');
    if ($form_id && (int) $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$forms_table} WHERE ID = %d", $form_id))) {
        ensure_rfq_form_settings($form_id);
        upgrade_default_rfq_form($form_id);
        return $form_id;
    }

    // Recover safely if the option was removed but the form still exists.
    $form_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$forms_table} WHERE title = %s AND status = 'published' ORDER BY ID LIMIT 1",
        'Request for quotation'
    ));
    if ($form_id) {
        update_option('starter_rfq_form_id', $form_id);
        ensure_rfq_form_settings($form_id);
        upgrade_default_rfq_form($form_id);
        return $form_id;
    }

    $fields = default_rfq_form_fields();

    // Fluent Forms' installer-created forms have a known renderable row/meta
    // shape. Copy that shape rather than inventing a bare form row.
    $source = $wpdb->get_row(
        "SELECT * FROM {$forms_table} WHERE status = 'published' ORDER BY CASE WHEN title = 'Contact Form Demo' THEN 0 ELSE 1 END, id ASC LIMIT 1"
    );
    $form_fields = ['fields' => $fields];
    if ($source && !empty($source->form_fields)) {
        $source_fields = json_decode((string) $source->form_fields, true);
        if (!empty($source_fields['submitButton'])) {
            $form_fields['submitButton'] = $source_fields['submitButton'];
            $form_fields['submitButton']['settings']['button_ui'] = ['type' => 'default', 'text' => 'Send inquiry'];
        }
    }

    $inserted = $wpdb->insert($forms_table, [
        'title' => 'Request for quotation',
        'status' => 'published',
        'form_fields' => wp_json_encode($form_fields),
        'appearance_settings' => $source->appearance_settings ?? wp_json_encode(['css' => '']),
        'has_payment' => 0,
        'type' => $source->type ?? 'form',
        'conditions' => $source->conditions ?? wp_json_encode([]),
        'created_by' => get_current_user_id() ?: 1,
    ]);
    if (!$inserted) {
        return 0;
    }

    $form_id = (int) $wpdb->insert_id;
    if ($source) {
        $source_settings = $wpdb->get_var($wpdb->prepare(
            "SELECT value FROM {$wpdb->prefix}fluentform_form_meta WHERE form_id = %d AND meta_key = 'formSettings'",
            $source->id
        ));
        if ($source_settings) {
            $wpdb->insert($wpdb->prefix . 'fluentform_form_meta', [
                'form_id' => $form_id,
                'meta_key' => 'formSettings',
                'value' => wp_json_encode(array_replace(json_decode((string) $source_settings, true) ?: [], ['confirmation' => InquiryThankYou\confirmation()])),
            ]);
        }
    }
    $wpdb->insert($wpdb->prefix . 'fluentform_form_meta', [
        'form_id' => $form_id,
        'meta_key' => 'notifications',
        'value' => wp_json_encode([
            'name' => 'Admin notification',
        'wb_inquiry_attachments' => true,
            'sendTo' => ['type' => 'email', 'email' => get_option('admin_email')],
            'fromName' => get_bloginfo('name'),
            'fromEmail' => '',
            'replyTo' => '{inputs.email}',
            'bcc' => '',
            'cc' => '',
            'subject' => 'New quotation request — {inputs.full_name}',
            'message' => '<p>{all_data}</p><p>Inquiry page: {submission.source_url}</p>',
            'conditionals' => ['status' => false, 'type' => 'all', 'conditions' => []],
            'enabled' => true,
            'email_template' => '',
        ]),
    ]);
    update_option('starter_rfq_form_id', $form_id);
    ensure_rfq_form_settings($form_id);
    return $form_id;
}

add_action('init', static function (): void {
    // RFQ form embed. Maps the stable placeholder shortcode to the Fluent Form
    // instance so editors never need to know internal form IDs.
    add_shortcode('starter_rfq_form', static function (array $atts = []): string {
        if (!shortcode_exists('fluentform')) {
            return '<p>' . esc_html__('Install Fluent Forms to enable the quotation form.', 'b2b-starter') . '</p>';
        }
        $atts = shortcode_atts(['id' => ''], $atts, 'starter_rfq_form');
        $form_id = (string) ($atts['id'] !== '' ? $atts['id'] : ensure_rfq_form());
        if ($form_id === '0') {
            return '<p>' . esc_html__('The quotation form is not ready yet. Please try again shortly.', 'b2b-starter') . '</p>';
        }
        return do_shortcode('[fluentform id="' . esc_attr($form_id) . '"]');
    });
});

add_action('acf/init', static function (): void {
    if (!function_exists('acf_add_local_field_group')) return;

    $field = static function (string $key, string $name, string $label, string $type, array $args = []): array {
        $defaults = [
            'key' => $key, 'name' => $name, 'label' => $label, 'type' => $type, 'show_in_rest' => true,
        ];
        if ($type !== 'tab' && empty($args['instructions'])) {
            $args['instructions'] = 'Shown on the website when this field is completed.';
        }
        return array_merge($defaults, $args);
    };
    $tab = static function (string $key, string $name, string $label) use ($field): array {
        return $field($key, $name, $label, 'tab', ['placement' => 'top', 'endpoint' => 0, 'show_in_rest' => false, 'instructions' => '']);
    };
    $rows_help = static function (string $format): array {
        return ['instructions' => $format . ' One item per line. Empty lines are ignored.'];
    };

    $pair = static function (string $key, string $name, string $label, string $first = 'Title', string $second = 'Description') use ($field): array {
        return $field($key, $name, $label, 'repeater', [
            'layout' => 'row', 'max' => 40, 'button_label' => 'Add item', 'collapsed' => $key . '_label',
            'instructions' => 'Add, reorder or remove items in this website section. Leave empty to hide it.',
            'sub_fields' => [
                $field($key . '_label', 'label', $first, 'text'),
                $field($key . '_value', 'value', $second, 'textarea', ['rows' => 2]),
            ],
        ]);
    };

    // Product editors can add named tables without entering HTML or layout code.
    $tables = static function (string $key, string $name, string $label) use ($field): array {
        return $field($key, $name, $label, 'repeater', [
            'layout' => 'block', 'max' => 12, 'collapsed' => $key . '_title', 'button_label' => 'Add table',
            'instructions' => 'Add a table, then add its attributes. Drag to reorder. Tables without complete rows are hidden.',
            'sub_fields' => [
                $field($key . '_title', 'title', 'Table title', 'text'),
                $field($key . '_rows', 'rows', 'Attributes', 'repeater', [
                    'layout' => 'table', 'max' => 60, 'button_label' => 'Add attribute',
                    'sub_fields' => [
                        $field($key . '_name', 'label', 'Attribute', 'text'),
                        $field($key . '_value', 'value', 'Value', 'text'),
                        $field($key . '_unit', 'unit', 'Unit (optional)', 'text'),
                    ],
                ]),
            ],
        ]);
    };

    $product_group = [
        'key' => 'group_starter_product', 'title' => 'Product content', 'show_in_rest' => true,
        'label_placement' => 'top', 'instruction_placement' => 'label', 'position' => 'acf_after_title',
        'fields' => [
            $tab('field_p_tab_gallery', 'tab_product_gallery', 'Gallery'),
            $field('field_p_gallery', 'product_gallery', 'Product gallery', 'gallery', [
                'return_format' => 'array', 'preview_size' => 'medium', 'library' => 'all',
                'instructions' => 'Add relevant product images. Clearly label generated concept illustrations; do not imply they are verified production photographs. The featured image appears first when set.',
            ]),
            $tab('field_p_tab_overview', 'tab_product_overview', 'Overview'),
            $field('field_p_quick_specs_rows', 'product_quick_specs', 'Key product specifications', 'repeater', [
                'layout' => 'table', 'button_label' => 'Add specification', 'max' => 12,
                'instructions' => 'Shown next to the product title. Choose 3–6 important comparison attributes; up to 12 rows are supported. You can rename, add, remove and drag rows to reorder. Every complete row is displayed in that order. Put units in the value, e.g. 18.5 kW. Full technical tables belong in Attributes.',
                'sub_fields' => [
                    $field('field_p_quick_label', 'quick_label', 'Attribute', 'text'),
                    $field('field_p_quick_value', 'quick_value', 'Value / unit', 'text'),
                ],
            ]),
            $field('field_p_cta_label', 'product_cta_label', 'Product CTA button label', 'text', ['instructions' => 'Shown on the product inquiry button. Leave empty to use “Request a quotation”.']),
            $tab('field_p_tab_services', 'tab_product_services', 'Services'),
            $field('field_p_services', 'product_services', 'Product services', 'repeater', [
                'layout' => 'block', 'max' => 30, 'collapsed' => 'field_p_service_title', 'button_label' => 'Add service',
                'instructions' => 'Each service expands in place. A title without details is shown as a plain row. Leave empty to hide Services.',
                'sub_fields' => [
                    $field('field_p_service_title', 'title', 'Service title', 'text'),
                    $field('field_p_service_content', 'content', 'Service details', 'wysiwyg', ['tabs' => 'all', 'toolbar' => 'full', 'media_upload' => 1, 'delay' => 1]),
                ],
            ]),
            $tab('field_p_tab_specs', 'tab_product_specs', 'Attributes'),
            $field('field_p_specifications', 'product_specifications', 'Full specifications', 'repeater', [
                'layout' => 'table', 'button_label' => 'Add specification', 'max' => 60,
                'sub_fields' => [
                    $field('field_p_spec_label', 'spec_label', 'Label', 'text'),
                    $field('field_p_spec_value', 'spec_value', 'Value', 'text'),
                ],
            ]),
            $tables('field_p_attribute_tables', 'product_attribute_tables', 'Additional attribute tables'),
            $tables('field_p_packaging_tables', 'product_packaging_tables', 'Packaging tables'),
            $field('field_p_lead_times', 'product_lead_times', 'Lead times', 'repeater', [
                'layout' => 'table', 'max' => 20, 'button_label' => 'Add quantity range',
                'instructions' => 'Enter quantity ranges and their delivery times. Only complete rows are displayed.',
                'sub_fields' => [
                    $field('field_p_lead_quantity', 'quantity', 'Quantity range', 'text'),
                    $field('field_p_lead_time', 'time', 'Lead time', 'text'),
                ],
            ]),
            $tab('field_p_tab_certificates', 'tab_product_certificates', 'Certificates & documents'),
            $field('field_p_certificates', 'product_certificates', 'Certificates & product documents', 'repeater', [
                'layout' => 'block', 'max' => 20, 'collapsed' => 'field_p_certificate_name', 'button_label' => 'Add document',
                'instructions' => 'Shown after the product attributes. Add applicable conformity declarations, engine-emission documents, test reports, datasheets or manuals. Choose the documents for this model and destination market, not a fixed set of logos. Drag to reorder; leave empty to hide this section. A logo alone is not certification evidence.',
                'sub_fields' => [
                    $field('field_p_certificate_name', 'name', 'Document or mark name', 'text', ['required' => 1]),
                    $field('field_p_certificate_description', 'description', 'Scope / explanation', 'textarea', ['rows' => 2, 'instructions' => 'Briefly identify the model, destination market, standard and document version/date where useful. Company-management certificates must identify the company scope; they are not product certification.']),
                    $field('field_p_certificate_badge', 'badge', 'Mark / logo image (optional)', 'image', ['return_format' => 'array', 'preview_size' => 'thumbnail', 'mime_types' => 'jpg,jpeg,png,webp', 'instructions' => 'Upload an approved mark or logo. No logo is required; a neutral document icon is used instead.']),
                    $field('field_p_certificate_document', 'document', 'Document file (optional)', 'file', ['return_format' => 'array', 'mime_types' => 'pdf,jpg,jpeg,png,webp', 'instructions' => 'Upload a genuine product certificate, declaration, report, datasheet or manual: PDF or a legible image. Only upload material suitable for public viewing. Without a logo, an image file becomes the thumbnail; PDF uses a document icon.']),
                ],
            ]),
            $tab('field_p_tab_factory', 'tab_product_factory', 'Factory information'),
            $field('field_p_show_factory', 'product_show_factory', 'Show factory information', 'true_false', [
                'ui' => 1, 'default_value' => 1,
                'instructions' => 'Display the shared factory photo and information before the description. Edit the shared information in Business Profile.',
            ]),
            $tab('field_p_tab_details', 'tab_product_details', 'Description'),
            $field('field_p_details_title', 'product_details_title', 'Product details title', 'text', ['instructions' => 'Optional heading for the bottom product description. Leave empty to use “Product details”.']),
            $field('field_p_details_body', 'product_details_body', 'Product details', 'wysiwyg', [
                'tabs' => 'all', 'toolbar' => 'full', 'media_upload' => 1, 'delay' => 0,
                'instructions' => 'Write the complete product description shown below the specifications. FAQ is optional and follows the details. Add headings, images and videos here. Existing main-editor text is preloaded until you first save this field; the original body remains stored.',
            ]),
            $tab('field_p_tab_faq', 'tab_product_faq', 'FAQ'),
            $field('field_p_faq_items', 'product_faq_items', 'Product FAQ', 'repeater', [
                'layout' => 'row', 'button_label' => 'Add question', 'max' => 30, 'collapsed' => 'field_p_faq_question',
                'sub_fields' => [
                    $field('field_p_faq_question', 'faq_question', 'Question', 'text'),
                    $field('field_p_faq_answer', 'faq_answer', 'Answer', 'textarea', ['rows' => 3]),
                ],
            ]),
            $tab('field_p_tab_related', 'tab_product_related', 'Related products'),
            $field('field_p_related', 'related_products', 'Related products', 'post_object', [
                'post_type' => ['starter_product'], 'multiple' => 1, 'return_format' => 'id', 'instructions' => 'Leave empty to show automatic related products.',
            ]),
            $field('field_p_gallery_1', 'product_gallery_1', 'Gallery image 1', 'image', [
                'return_format' => 'array', 'preview_size' => 'medium', 'library' => 'all',
                'instructions' => 'Legacy storage; preloaded into Product gallery until its first save.',
            ]),
            $field('field_p_gallery_2', 'product_gallery_2', 'Gallery image 2', 'image', ['return_format' => 'array', 'preview_size' => 'medium']),
            $field('field_p_gallery_3', 'product_gallery_3', 'Gallery image 3', 'image', ['return_format' => 'array', 'preview_size' => 'medium']),
            $field('field_p_gallery_4', 'product_gallery_4', 'Gallery image 4', 'image', ['return_format' => 'array', 'preview_size' => 'medium']),
            $field('field_p_gallery_5', 'product_gallery_5', 'Gallery image 5', 'image', ['return_format' => 'array', 'preview_size' => 'medium']),
            $field('field_p_quick_specs', 'quick_specs', 'Legacy quick specifications', 'textarea', array_merge(['rows' => 6], $rows_help('Use Label | Value. Preloaded into structured rows until their first save.'))),
            $field('field_p_spec_table', 'spec_table', 'Legacy full specifications', 'textarea', array_merge(['rows' => 10], $rows_help('Use Label | Value. Preloaded into structured rows until their first save.'))),
            $field('field_p_faq', 'product_faq', 'Legacy product FAQ', 'textarea', array_merge(['rows' => 8], $rows_help('Use Question | Answer. Preloaded into structured rows until their first save.'))),
        ],
        'location' => [[
            ['param' => 'post_type', 'operator' => '==', 'value' => 'starter_product'],
            ['param' => 'post_template', 'operator' => '==', 'value' => 'default'],
        ]],
    ];
    acf_add_local_field_group($product_group);
    // Simple owns a complete editor. Seamless Clone reuses canonical fields without new storage names.
    acf_add_local_field_group(array_merge($product_group, [
        'key' => 'group_starter_product_simple',
        'fields' => [
            $field('field_p_simple_content', 'product_simple_content', '', 'clone', [
                'clone' => [
                    'field_p_tab_gallery', 'field_p_gallery', 'field_p_tab_overview',
                    'field_p_quick_specs_rows', 'field_p_cta_label', 'field_p_tab_details',
                    'field_p_details_title', 'field_p_details_body',
                ],
                'display' => 'seamless', 'prefix_name' => 0, 'prefix_label' => 0,
            ]),
        ],
        'location' => [[
            ['param' => 'post_type', 'operator' => '==', 'value' => 'starter_product'],
            ['param' => 'post_template', 'operator' => '==', 'value' => 'page-templates/product-simple.php'],
        ]],
    ]));

    register_industry_fields($field, $tab);

    register_category_fields($field, $tab, $tables, $pair, $rows_help);

    register_home_fields($field, $tab);

    acf_add_local_field_group([
        'key' => 'group_landing_content', 'title' => 'Landing page content', 'show_in_rest' => true,
        'fields' => [
            $tab('field_landing_tab_hero', 'tab_landing_hero', 'Hero'),
            $field('field_landing_overline', 'landing_overline', 'Eyebrow', 'text'),
            $field('field_landing_description', 'landing_description', 'Intro description', 'textarea', ['rows' => 4]),
            $tab('field_landing_tab_features', 'tab_landing_features', 'Features'),
            $pair('field_landing_feature_items', 'landing_feature_items', 'Features'),
            $field('field_landing_features', 'landing_features', 'Feature rows', 'textarea', array_merge(['rows' => 8], $rows_help('Use Title | Description.'))),
            $tab('field_landing_tab_body', 'tab_landing_body', 'Page details'),
            $field('field_landing_body', 'landing_body', 'Page details', 'wysiwyg', ['tabs' => 'all', 'toolbar' => 'full', 'media_upload' => 1]),
            $tab('field_landing_tab_cta', 'tab_landing_cta', 'CTA'),
            $field('field_landing_cta_title', 'landing_cta_title', 'CTA title', 'text'),
            $field('field_landing_cta_text', 'landing_cta_text', 'CTA text', 'textarea', ['rows' => 3]),
            $field('field_landing_cta_button', 'landing_cta_button', 'CTA button label', 'text'),
        ],
        'location' => [[['param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/landing.php']]],
    ]);


    acf_add_local_field_group([
        'key' => 'group_about_content', 'title' => 'About page content', 'show_in_rest' => true,
        'fields' => array_merge([
            $tab('field_about_tab_story', 'tab_about_story', 'Company introduction'),
            $field('field_about_story', 'about_story', 'Company story', 'wysiwyg', [
                'tabs' => 'all', 'toolbar' => 'full', 'media_upload' => 1, 'delay' => 0,
                'instructions' => 'Edit the company introduction here. Factory facts and capabilities are managed once in Business Profile. Existing main-editor copy is preloaded until the first save. Clearing this field hides the story.',
            ]),
        ], about_media_fields($field, $tab), [
            $tab('field_about_tab_gallery', 'tab_about_gallery', 'Company gallery'),
        ], about_gallery_heading_fields($field), [
            $field('field_about_gallery', 'about_gallery', 'Company photographs', 'gallery', ['return_format' => 'array', 'preview_size' => 'medium', 'instructions' => 'Select multiple approved factory, team or quality-control photographs and drag to reorder. Images appear in a photo wall with an original-image viewer. Leave empty to omit the entire gallery.']),
        ], about_faq_fields($field, $tab)),
        'location' => [[['param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/about.php']]],
    ]);

    acf_add_local_field_group([
        'key' => 'group_factory_profile', 'title' => 'Factory profile', 'show_in_rest' => true,
        'fields' => [
            $tab('field_f_tab_overview', 'tab_factory_overview', 'Overview'),
            $field('field_f_card_image', 'factory_card_image', 'Factory photograph', 'image', [
                'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all',
                'instructions' => 'Shared product-page factory image. Upload a real factory, workshop or production-line photograph; a landscape image works best. Set alternative text in the media library.',
            ]),
            $field('field_f_company_name', 'factory_company_name', 'Company name', 'text'),
            $field('field_f_logo', 'factory_logo', 'Company logo', 'image', ['return_format' => 'id', 'preview_size' => 'thumbnail']),
            $field('field_f_location', 'factory_location', 'Factory location', 'text'),
            $field('field_f_card_link', 'factory_card_link', 'Factory details link', 'link', ['return_format' => 'array', 'instructions' => 'Optional link to your About or Factory page.']),
            $field('field_f_intro', 'factory_intro', 'Factory introduction', 'textarea', ['rows' => 4, 'instructions' => 'Shown where the design requests a company proof block.']),
            $pair('field_f_stat_items', 'factory_stat_items', 'Factory facts', 'Value', 'Label'),
            $field('field_f_stats', 'factory_stats', 'Factory facts', 'textarea', array_merge(['rows' => 6], $rows_help('One fact per line. Use Value | Label (for example: 3 lines | Automated SMT, assembly and burn-in).'))),
            $tab('field_f_tab_capabilities', 'tab_factory_capabilities', 'Production'),
            $pair('field_f_capability_items', 'factory_capability_items', 'Capabilities'),
            $field('field_f_caps', 'factory_capabilities', 'Capabilities', 'textarea', array_merge(['rows' => 8], $rows_help('Use Title | Description.'))),
            $pair('field_f_process_items', 'factory_process_items', 'Manufacturing process'),
            $field('field_f_process', 'factory_process', 'Manufacturing process', 'textarea', array_merge(['rows' => 8], $rows_help('Use Step | Description.'))),
            $field('field_f_quality_tests', 'factory_quality_tests', 'Quality tests', 'textarea', array_merge(['rows' => 6], $rows_help('Enter a quality-control step.'))),
            $tab('field_f_tab_evidence', 'tab_factory_evidence', 'Evidence & markets'),
            $field('field_f_certifications', 'factory_certifications', 'Certifications & standards', 'textarea', array_merge(['rows' => 6], $rows_help('Use Name | Note.'))),
            $field('field_f_markets', 'factory_markets', 'Export markets', 'text'),
        ],
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'starter_profile']]],
    ]);

    acf_add_local_field_group([
        'key' => 'group_factory_page', 'title' => 'Factory page details', 'show_in_rest' => true,
        'fields' => [$field('field_factory_body', 'factory_body', 'Factory details', 'wysiwyg', [
            'tabs' => 'all', 'toolbar' => 'full', 'media_upload' => 1,
            'instructions' => 'Additional photos and details for this page. Shared capabilities and process are in Business Profile.',
        ])],
        'location' => [[['param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/factory-capability.php']]],
    ]);

    acf_add_local_field_group([
        'key' => 'group_resource_content', 'title' => 'Resource center content', 'show_in_rest' => true,
        'fields' => [$field('field_resource_intro', 'resource_intro', 'Introduction', 'wysiwyg', [
            'tabs' => 'all', 'toolbar' => 'full', 'media_upload' => 1,
            'instructions' => 'Shown below this page title and summary. Add headings, paragraphs, lists, links or approved media. Clear this field to hide the introduction.',
        ])],
        'location' => [[['param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/resource-center.php']]],
    ]);

    // Copy for archives, system routes and global conversion promises.
    acf_add_local_field_group([
        'key' => 'group_site_copy', 'title' => 'Site copy & conversion', 'show_in_rest' => true,
        'fields' => [
            $tab('field_site_tab_archives', 'tab_site_archives', 'Archives'),
            $field('field_product_archive_description', 'product_archive_description', 'Product archive introduction', 'textarea', ['rows' => 3]),
            $field('field_industry_archive_description', 'industry_archive_description', 'Industry archive introduction', 'textarea', ['rows' => 3]),
            $field('field_guide_archive_description', 'guide_archive_description', 'Guide archive introduction', 'textarea', ['rows' => 3]),
            $field('field_news_archive_description', 'news_archive_description', 'News archive introduction', 'textarea', ['rows' => 3]),
            $field('field_not_found_description', 'not_found_description', '404 introduction', 'textarea', ['rows' => 3]),
            $tab('field_site_tab_response', 'tab_site_response', 'Response'),
            $field('field_response_promise', 'response_promise', 'Response promise', 'text'),
            $tab('field_site_tab_about', 'tab_site_about', 'About CTA'),
            $field('field_about_cta_title', 'about_cta_title', 'About CTA title', 'text'),
            $field('field_about_cta_text', 'about_cta_text', 'About CTA text', 'textarea', ['rows' => 3]),
            $field('field_about_cta_button', 'about_cta_button', 'About CTA button label', 'text'),
        ],
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'starter_profile']]],
    ]);

    acf_add_local_field_group([
        'key' => 'group_contact_content', 'title' => 'Contact page content', 'show_in_rest' => true,
        'fields' => [
            $tab('field_contact_tab_paths', 'tab_contact_paths', 'Introduction'),
            $field('field_contact_intro', 'contact_intro', 'Contact introduction', 'textarea', ['rows' => 3]),
            $tab('field_contact_tab_body', 'tab_contact_body', 'Content'),
            $field('field_contact_body', 'contact_body', 'Contact information', 'wysiwyg', [
                'tabs' => 'all', 'toolbar' => 'full', 'media_upload' => 1,
                'instructions' => 'Write headings, paragraphs, lists and links for the left column. Previous checklist and follow-up copy is preloaded until the first save. Clear this field to hide it.',
            ]),
            $tab('field_contact_tab_faq', 'tab_contact_faq', 'FAQ'),
            $field('field_contact_faq_items', 'contact_faq_items', 'Frequently asked questions', 'repeater', [
                'layout' => 'row', 'button_label' => 'Add question', 'collapsed' => 'field_contact_faq_question',
                'instructions' => 'Add, remove or drag questions to reorder. Only complete questions and answers appear; leave empty to hide FAQ.',
                'sub_fields' => [
                    $field('field_contact_faq_question', 'question', 'Question', 'text'),
                    $field('field_contact_faq_answer', 'answer', 'Answer', 'textarea', ['rows' => 4]),
                ],
            ]),
            $tab('field_contact_tab_form', 'tab_contact_form', 'Form'),
            $field('field_form_title', 'form_title', 'Form panel title', 'text'),
            $field('field_form_note', 'form_note', 'Form panel note', 'textarea', ['rows' => 3]),
        ],
        'location' => [[['param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/contact.php']]],
    ]);
    // Read-only definitions preserve old stored references without a second visible editor.
    acf_add_local_field_group([
        'key' => 'group_contact_legacy', 'title' => 'Legacy Contact fields', 'active' => false,
        'fields' => [
            $field('field_contact_path_quote_title', 'contact_path_quote_title', 'Quotation path title', 'text'),
            $field('field_contact_path_quote_description', 'contact_path_quote_description', 'Quotation path description', 'textarea', ['rows' => 2]),
            $field('field_contact_path_quote_action', 'contact_path_quote_action', 'Quotation path button', 'text'),
            $field('field_contact_path_project_title', 'contact_path_project_title', 'Project path title', 'text'),
            $field('field_contact_path_project_description', 'contact_path_project_description', 'Project path description', 'textarea', ['rows' => 2]),
            $field('field_contact_path_project_action', 'contact_path_project_action', 'Project path button', 'text'),
            $field('field_contact_path_visit_title', 'contact_path_visit_title', 'Visit path title', 'text'),
            $field('field_contact_path_visit_description', 'contact_path_visit_description', 'Visit path description', 'textarea', ['rows' => 2]),
            $field('field_contact_path_visit_action', 'contact_path_visit_action', 'Visit path button', 'text'),
            $field('field_contact_checklist', 'contact_checklist', 'What helps us respond faster', 'textarea', array_merge(['rows' => 6], $rows_help('Enter a checklist item.'))),
            $pair('field_contact_process_items', 'contact_process_items', 'After-submission steps'),
            $field('field_contact_process', 'contact_process', 'After-submission steps', 'textarea', array_merge(['rows' => 6], $rows_help('Use Step | Explanation.'))),
        ],
        'location' => [],
    ]);

});

/** Keep the baseline editing screen aligned with the actual default layout. */
add_filter('acf/prepare_field', static function ($field) {
    if (!$field) return $field;
    $name = $field['name'] ?? '';
    $unused_contact = str_starts_with($name, 'contact_path_');
    if ($unused_contact) return false;
    return $field;
});
add_filter('acf/load_field', static function ($field) {
    $name = $field['name'] ?? '';
    $home_extended = ['applications_count', 'show_industries', 'industries_title', 'industries_count', 'show_guides', 'guides_title', 'guides_count'];

    if (in_array($name, $home_extended, true)) $field['conditional_logic'] = [[['field' => 'field_home_template', 'operator' => '!=', 'value' => 'corporate']]];
    return $field;
});

/** An editorial category with its product list disabled has no secondary listing pages. */
add_action('template_redirect', static function (): void {
    if (!is_tax('product_collection') || !is_paged()) return;
    $term = get_queried_object();
    if (!$term instanceof \WP_Term) return;
    $template = (string) get_term_meta($term->term_id, 'category_template', true);
    if ($template !== '' && $template !== 'standard') return;
    if (!metadata_exists('term', $term->term_id, 'category_show_products') || get_term_meta($term->term_id, 'category_show_products', true) !== '0') return;
    $url = get_term_link($term);
    if (!is_wp_error($url)) { wp_safe_redirect($url, 302); exit; }
});

