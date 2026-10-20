<?php
/**
 * Page controllers. Templates ask for data here; views never query the database.
 *
 * @package b2b-starter
 */

namespace Starter\Theme;

defined('ABSPATH') || exit;

function text(string $name, $source = null, string $fallback = ''): string {
    $value = field_text($name, $source);
    return trim($value) !== '' ? $value : $fallback;
}

function rows(string $name, $source = null, array $fallback = []): array {
    $raw = field_text($name, $source);
    if (trim($raw) === '') {
        return $fallback;
    }
    return lines_rows($raw);
}

/** Prefer the structured PRO rows; retain older textarea content for existing sites. */
function product_repeater_rows(string $name, int $post_id, string $label_key, string $value_key, string $legacy_name): array {
    $raw = function_exists('get_field') ? get_field($name, $post_id) : [];
    $result = [];
    foreach (is_array($raw) ? $raw : [] as $row) {
        if (!is_array($row)) continue;
        $label = trim((string) ($row[$label_key] ?? ''));
        if ($label === '') continue;
        $result[] = ['label' => $label, 'value' => trim((string) ($row[$value_key] ?? ''))];
    }
    return metadata_exists('post', $post_id, $name) ? $result : ($result ?: rows($legacy_name, $post_id));
}

function lines(string $name, $source = null, array $fallback = []): array {
    $raw = field_text($name, $source);
    if (trim($raw) === '') {
        return $fallback;
    }
    return array_values(array_filter(array_map('trim', preg_split('/\\r?\\n/', $raw) ?: []), static fn ($line) => $line !== ''));
}

function global_text(string $name, string $fallback = ''): string {
    $value = field_option($name);
    return trim($value) !== '' ? $value : $fallback;
}

function enabled(string $name): bool {
    $value = function_exists('get_field') ? get_field($name) : null;
    return $value === null ? true : (bool) $value;
}

/** Resource introduction has one editor and never inherits an unused main body. */
function resource_data(int $post_id = 0): array {
    $post_id = $post_id ?: (int) get_the_ID();
    $intro = function_exists('get_field') ? (string) get_field('field_resource_intro', $post_id, true, true) : '';
    return [
        'intro' => has_rich_content($intro) ? $intro : '',
        'summary' => (string) get_post_field('post_excerpt', $post_id, 'raw'),
    ];
}

function factory_profile_data(): array {
    $proof = array_map(static fn (array $row): array => ['value' => $row['label'], 'label' => $row['value']], shared_rows('factory_stat_items', 'factory_stats'));
    $capabilities = shared_rows('factory_capability_items', 'factory_capabilities');
    $certifications = lines('factory_certifications', 'option');
    $process = shared_rows('factory_process_items', 'factory_process');
    $quality_tests = lines('factory_quality_tests', 'option');
    $markets = global_text('factory_markets');

    return [
        'intro' => global_text('factory_intro'),
        'proof' => $proof,
        'capabilities' => $capabilities,
        'certifications' => $certifications,
        'process' => $process,
        'quality_tests' => $quality_tests,
        'markets' => $markets,
        'has_core_proof' => (bool) ($proof || $capabilities || $certifications || $markets),
        'has_manufacturing' => (bool) ($capabilities || $process || $quality_tests),
    ];
}

function homepage_data(): array {
    $stats = [];
    foreach ([1, 2, 3] as $index) {
        $stats[] = [
            'value' => get_setting("stat_{$index}_value"),
            'label' => get_setting("stat_{$index}_label"),
        ];
    }
    if (!array_filter($stats, static fn (array $item): bool => trim((string) $item['value']) !== '')) {
            $stats = array_map(static fn (array $row): array => ['value' => $row['label'], 'label' => $row['value']], shared_rows('factory_stat_items', 'factory_stats'));
    }

    $term_index = [];
    $terms = get_terms(['taxonomy' => 'product_collection', 'hide_empty' => true, 'number' => 20]);
    if (!is_wp_error($terms)) {
        $parent_ids = array_map(static fn (\WP_Term $term): int => (int) $term->parent, $terms);
        foreach ($terms as $term) {
            if (in_array((int) $term->term_id, $parent_ids, true)) continue;
            $url = get_term_link($term);
            if (is_wp_error($url)) continue;
            $term_index[] = ['name' => $term->name, 'url' => $url];
            if (count($term_index) === 4) break;
        }
    }

    return [
        'overline' => text('home_overline', false, 'Products & manufacturing'),
        'description' => text('home_description', false),
        'stats' => array_slice($stats, 0, 4),
        'term_index' => $term_index,
        'company' => [
            'title' => text('home_company_title', false, 'About our company'),
            'body' => function_exists('get_field') ? (string) get_field('field_home_company_body', false, true, true) : '',
            'image' => function_exists('get_field') ? (int) get_field('home_company_image') : 0,
            'link' => function_exists('get_field') ? get_field('home_company_link') : [],
        ],
        'sections' => [
            'applications' => [
                'enabled' => enabled('show_applications'),
                'title' => text('applications_title', false, 'Explore our products'),
                'count' => max(1, min(12, (int) (get_field('applications_count') ?: 12))),
            ],
            'products' => [
                'enabled' => (bool) get_field('show_featured_products'),
                'title' => text('featured_products_title', false, 'Featured products'),
                'count' => max(1, min(12, (int) (get_field('featured_products_count') ?: 6))),
            ],
            'industries' => [
                'enabled' => enabled('show_industries'),
                'title' => text('industries_title', false, 'Industry solutions'),
                'count' => max(1, min(12, (int) (get_field('industries_count') ?: 3))),
            ],
            'guides' => [
                'enabled' => enabled('show_guides'),
                'title' => text('guides_title', false, 'Buyer guides'),
                'count' => max(1, min(12, (int) (get_field('guides_count') ?: 3))),
            ],
        ],
        'cta' => [
            'title' => text('home_cta_title', false, get_setting('cta_title', 'Tell us about your project')),
            'description' => text('home_cta_text', false, get_setting('cta_text', 'Send specifications or a short brief and receive an itemised response.')),
            'button_label' => text('home_cta_button', false, 'Request a quote'),
            'url' => contact_url(),
        ],
    ];
}

function module_body(string $name, int $post_id = 0): string {
    $post_id = $post_id ?: (int) get_the_ID();
    $keys = ['product_details_body' => 'field_p_details_body', 'about_story' => 'field_about_story', 'landing_body' => 'field_landing_body', 'factory_body' => 'field_factory_body', 'industry_body' => 'field_i_body'];
    $body = function_exists('get_field') ? (string) get_field($keys[$name] ?? $name, $post_id, true, true) : '';
    if ($body === '' && !metadata_exists('post', $post_id, $name)) {
        $legacy = (string) get_post_field('post_content', $post_id);
        return trim($legacy) !== '' ? (string) apply_filters('the_content', $legacy) : '';
    }
    return $body;
}

function shared_rows(string $name, string $legacy): array {
    $id = function_exists('Starter\\Model\\profile_post_id') ? \Starter\Model\profile_post_id() : 0;
    return $id ? product_repeater_rows($name, $id, 'label', 'value', $legacy) : rows($legacy, 'option');
}

function about_data(): array {
    $story = module_body('about_story');
    return [
        'story' => $story,
        'stats' => array_map(
            static fn (array $row): array => ['value' => $row['label'], 'label' => $row['value']],
            shared_rows('factory_stat_items', 'factory_stats')
        ),
        'capabilities' => shared_rows('factory_capability_items', 'factory_capabilities'),
        'cta' => [
            'title' => global_text('about_cta_title', 'Discuss your requirements'),
            'description' => global_text('about_cta_text', 'Share your target market, quantity and timeline.'),
            'button_label' => global_text('about_cta_button', 'Contact our team'),
            'url' => contact_url(),
        ],
    ];
}

function contact_data(): array {
    $post_id = (int) get_the_ID();
    $body = function_exists('get_field') ? (string) get_field('field_contact_body', $post_id, true, true) : '';
    $faq = [];
    $raw = function_exists('get_field') ? get_field('field_contact_faq_items', $post_id) : [];
    foreach (is_array($raw) ? $raw : [] as $row) {
        if (!is_array($row)) continue;
        $question = trim((string) ($row['question'] ?? ''));
        $answer = trim((string) ($row['answer'] ?? ''));
        if ($question !== '' && $answer !== '') $faq[] = ['label' => $question, 'value' => $answer];
    }
    return [
        'intro' => field_text('contact_intro', $post_id),
        'body' => has_rich_content($body) ? $body : '',
        'faq' => $faq,
        'form_title' => text('form_title', $post_id, 'Request a quotation'),
        'form_note' => field_text('form_note', $post_id),
    ];
}

function product_gallery_images(int $post_id): array {
    $images = [];
    $featured_id = (int) get_post_thumbnail_id($post_id);
    if ($featured_id > 0) {
        $images[] = normalize_product_image(['id' => $featured_id]);
    }
    $gallery = function_exists('get_field') ? get_field('product_gallery', $post_id) : [];
    $slots = metadata_exists('post', $post_id, 'product_gallery') || (is_array($gallery) && $gallery) ? (is_array($gallery) ? $gallery : []) : (function_exists('get_field') ? [
        get_field('product_gallery_1', $post_id),
        get_field('product_gallery_2', $post_id),
        get_field('product_gallery_3', $post_id),
        get_field('product_gallery_4', $post_id),
        get_field('product_gallery_5', $post_id),
    ] : []);

    foreach ($slots as $image) {
        if (is_numeric($image)) {
            $image = ['id' => (int) $image];
        }
        if (!is_array($image) || empty($image)) {
            continue;
        }
        $images[] = normalize_product_image($image);
    }

    $unique = [];
    foreach ($images as $image) {
        $key = (string) ($image['id'] > 0 ? 'id-' . $image['id'] : 'url-' . $image['url']);
        if (trim((string) $image['url']) === '' || isset($unique[$key])) {
            continue;
        }
        $unique[$key] = $image;
    }
    return array_values($unique);
}

function normalize_product_image(array $image): array {
    $id = (int) ($image['ID'] ?? $image['id'] ?? 0);
    $url = (string) ($image['url'] ?? '');
    $alt = (string) ($image['alt'] ?? '');
    $caption = (string) ($image['caption'] ?? '');

    if ($id > 0) {
        $url = $url !== '' ? $url : (string) wp_get_attachment_image_url($id, 'large');
        $alt = $alt !== '' ? $alt : (string) get_post_meta($id, '_wp_attachment_image_alt', true);
        $attachment = get_post($id);
        if ($attachment instanceof \WP_Post) {
            $caption = $caption !== '' ? $caption : (string) $attachment->post_excerpt;
        }
    }

    return [
        'id' => $id,
        'url' => $url,
        'alt' => $alt,
        'caption' => \AxisCNC\SuiteTheme\public_caption($caption),
        'full_url' => $id > 0 ? (string) (wp_get_attachment_image_url($id, 'full') ?: $url) : $url,
        'thumb_url' => $id > 0 ? (string) (wp_get_attachment_image_url($id, 'thumbnail') ?: $url) : $url,
    ];
}

/** Empty editor markup is empty; actual media and supported shortcodes are content. */
function has_rich_content(string $content): bool {
    $text = trim(str_replace(["\xc2\xa0", '&nbsp;'], '', wp_strip_all_tags($content)));
    return $text !== '' || (bool) preg_match('/<(?:img|video|audio|iframe)\b|\[(?:video|audio|embed|gallery)\b/i', $content);
}

/** Complete attribute rows only, with a separate optional unit. */
function normalize_attribute_rows(array $rows): array {
    $result = [];
    foreach ($rows as $row) {
        if (!is_array($row)) continue;
        $label = trim((string) ($row['label'] ?? ''));
        $value = trim((string) ($row['value'] ?? ''));
        if ($label === '' || $value === '') continue;
        $unit = trim((string) ($row['unit'] ?? ''));
        $result[] = ['label' => $label, 'value' => $value . ($unit !== '' ? ' ' . $unit : '')];
    }
    return $result;
}

function product_table_groups(string $name, int $post_id, string $default_title): array {
    $raw = function_exists('get_field') ? get_field($name, $post_id) : [];
    return normalize_table_groups(is_array($raw) ? $raw : [], $default_title);
}

/** Shared normalization for named product and category parameter tables. */
function normalize_table_groups(array $raw, string $default_title): array {
    $groups = [];
    foreach (is_array($raw) ? $raw : [] as $group) {
        if (!is_array($group)) continue;
        $rows = normalize_attribute_rows(is_array($group['rows'] ?? null) ? $group['rows'] : []);
        if (!$rows) continue;
        $groups[] = ['title' => trim((string) ($group['title'] ?? '')) ?: $default_title, 'rows' => $rows];
    }
    return $groups;
}

/** Shared source: the same Business Profile facts also power About and Factory pages. */
function product_factory_data(int $post_id): array {
    if (metadata_exists('post', $post_id, 'product_show_factory') && !get_field('product_show_factory', $post_id)) return [];
    return shared_factory_data();
}

/** Read shared supplier facts without a product-specific visibility flag. */
function shared_factory_data(): array {
    $profile_id = function_exists('Starter\\Model\\profile_post_id') ? \Starter\Model\profile_post_id() : 0;
    if (!$profile_id) return [];
    $image = (int) get_field('factory_card_image', $profile_id);
    $logo = (int) get_field('factory_logo', $profile_id);
    $name = global_text('factory_company_name');
    $location = global_text('factory_location');
    $intro = global_text('factory_intro');
    $facts = array_values(array_filter(shared_rows('factory_stat_items', 'factory_stats'), static fn (array $row): bool => $row['label'] !== '' && $row['value'] !== ''));
    if (!$image && !$logo && $name === '' && $location === '' && $intro === '' && !$facts) return [];
    return ['image' => $image, 'logo' => $logo, 'name' => $name, 'location' => $location, 'intro' => $intro, 'facts' => $facts, 'link' => get_field('factory_card_link', $profile_id) ?: []];
}

/** Product evidence uses attachment IDs; names/logos never imply independent verification. */
function product_certificates_data(int $post_id): array {
    $rows = function_exists('get_field') ? get_field('field_p_certificates', $post_id) : [];
    $certificates = [];
    $allowed_images = ['image/jpeg', 'image/png', 'image/webp'];
    $formats = ['application/pdf' => 'PDF', 'image/jpeg' => 'JPG', 'image/png' => 'PNG', 'image/webp' => 'WEBP'];
    foreach (is_array($rows) ? $rows : [] as $row) {
        if (!is_array($row)) continue;
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') continue;
        $badge = is_array($row['badge'] ?? null) ? $row['badge'] : [];
        $badge_id = (int) ($badge['ID'] ?? 0);
        $badge = $badge_id > 0 && in_array(get_post_mime_type($badge_id), $allowed_images, true)
            ? normalize_product_image(['ID' => $badge_id]) : [];
        $file = is_array($row['document'] ?? null) ? $row['document'] : [];
        $file_id = (int) ($file['ID'] ?? 0);
        $mime = $file_id > 0 ? get_post_mime_type($file_id) : false;
        $url = $file_id > 0 ? wp_get_attachment_url($file_id) : false;
        $document = $url && isset($formats[$mime])
            ? ['url' => $url, 'is_pdf' => $mime === 'application/pdf', 'format' => $formats[$mime], 'image' => $mime === 'application/pdf' ? [] : normalize_product_image(['ID' => $file_id])]
            : [];
        $certificates[] = [
            'name' => $name,
            'description' => trim((string) ($row['description'] ?? '')),
            'badge' => $badge,
            'document' => $document,
        ];
    }
    return $certificates;
}

function product_data(): array {
    $post_id = (int) get_the_ID();
    $quick_specs = array_values(array_filter(
        product_repeater_rows('product_quick_specs', $post_id, 'quick_label', 'quick_value', 'quick_specs'),
        static fn (array $row): bool => $row['value'] !== ''
    ));

    $terms = get_the_terms($post_id, 'product_collection');
    $primary_term = is_array($terms) && isset($terms[0]) && !is_wp_error($terms[0]) ? $terms[0] : null;
    $category = [];
    if ($primary_term) {
        $term_url = get_term_link($primary_term);
        if (!is_wp_error($term_url)) {
            $category = ['name' => (string) $primary_term->name, 'url' => (string) $term_url];
        }
    }

    $related = get_field('related_products', $post_id);

    $details_body = module_body('product_details_body', $post_id);
    $services = [];
    $raw_services = function_exists('get_field') ? get_field('field_p_services', $post_id, true, true) : [];
    foreach (is_array($raw_services) ? $raw_services : [] as $service) {
        if (!is_array($service)) continue;
        $title = trim((string) ($service['title'] ?? ''));
        if ($title === '') continue;
        $content = (string) ($service['content'] ?? '');
        $services[] = ['label' => $title, 'value' => has_rich_content($content) ? $content : '', 'rich' => true];
    }
    $lead_times = [];
    $raw_lead_times = function_exists('get_field') ? get_field('product_lead_times', $post_id) : [];
    foreach (is_array($raw_lead_times) ? $raw_lead_times : [] as $row) {
        if (!is_array($row)) continue;
        $quantity = trim((string) ($row['quantity'] ?? ''));
        $time = trim((string) ($row['time'] ?? ''));
        if ($quantity !== '' && $time !== '') $lead_times[] = ['quantity' => $quantity, 'time' => $time];
    }
    return [
        'gallery' => product_gallery_images($post_id),
        'title' => (string) get_the_title(),
        'category' => $category,
        'excerpt' => (string) get_the_excerpt(),
        'value_chips' => $quick_specs,
        'cta' => [
            'label' => text('product_cta_label', $post_id, 'Request a quotation'),
            'note' => global_text('response_promise'),
            'url' => contact_url((string) get_the_title()),
        ],
        'specifications' => product_repeater_rows('product_specifications', $post_id, 'spec_label', 'spec_value', 'spec_table'),
        'services' => $services,
        'attribute_tables' => product_table_groups('product_attribute_tables', $post_id, 'Product attributes'),
        'packaging_tables' => product_table_groups('product_packaging_tables', $post_id, 'Packaging'),
        'lead_times' => $lead_times,
        'certificates' => product_certificates_data($post_id),
        'factory_card' => product_factory_data($post_id),
        'faq' => product_repeater_rows('product_faq_items', $post_id, 'faq_question', 'faq_answer', 'product_faq'),
        'details' => [
            'title' => text('product_details_title', $post_id, 'Product details'),
            'content' => $details_body,
            'has_content' => has_rich_content($details_body),
        ],
        'related_ids' => is_array($related) ? array_values(array_filter(array_map('intval', $related))) : [],
    ];
}

/** Old text rows remain readable until the new term repeater is saved, including an intentional empty value. */
function category_repeater_rows(string $name, int $term_id, string $label_key, string $value_key, string $legacy_name): array {
    $object_id = category_content_object($term_id);
    if (!category_meta_exists($term_id, $name)) return rows($legacy_name, $object_id);
    $raw = function_exists('get_field') ? get_field($name, $object_id) : [];
    $result = [];
    foreach (is_array($raw) ? $raw : [] as $row) {
        if (!is_array($row) || trim((string) ($row[$label_key] ?? '')) === '') continue;
        $result[] = ['label' => (string) $row[$label_key], 'value' => (string) ($row[$value_key] ?? '')];
    }
    return $result;
}

/** Homogeneous modules: formatted editor HTML, valid attachments and one shared factory owner. */
function category_content_sections(int $term_id): array {
    $raw = function_exists('get_field') ? get_field('field_tc_content_sections', category_content_object($term_id), true, true) : [];
    $sections = [];
    foreach (is_array($raw) ? $raw : [] as $row) {
        if (!is_array($row)) continue;
        $title = trim((string) ($row['title'] ?? ''));
        if ($title === '') continue;
        $shared = ($row['content_source'] ?? '') === 'shared_factory';
        $factory = $shared ? shared_factory_data() : [];
        // Missing type is an existing image module. A selected empty video never falls back to its saved image.
        $media_type = ($row['media_type'] ?? '') === 'video' ? 'video' : 'image';
        $image = $media_type === 'image' ? (int) ($shared ? ($factory['image'] ?? 0) : ($row['image'] ?? 0)) : 0;
        if (!$image || !wp_attachment_is_image($image) || !wp_get_attachment_image_url($image, 'large')) $image = 0;
        $video = [];
        if ($media_type === 'video') {
            $video_id = (int) ($row['video'] ?? 0);
            $mime = $video_id > 0 ? get_post_mime_type($video_id) : false;
            $url = $video_id > 0 && get_post_type($video_id) === 'attachment' ? wp_get_attachment_url($video_id) : false;
            if ($url && in_array($mime, ['video/mp4', 'video/webm', 'video/ogg'], true)) {
                $poster_id = (int) ($row['video_poster'] ?? 0);
                $poster = $poster_id > 0 && wp_attachment_is_image($poster_id) ? wp_get_attachment_image_url($poster_id, 'large') : false;
                $video = ['id' => $video_id, 'url' => (string) $url, 'mime' => (string) $mime, 'poster' => $poster ? (string) $poster : ''];
            }
        }
        $body = $shared ? wpautop(esc_html((string) ($factory['intro'] ?? ''))) : (string) ($row['body'] ?? '');
        if (!has_rich_content($body)) $body = '';
        if (!$image && !$video && $body === '' && !($factory['name'] ?? '') && !($factory['location'] ?? '') && !($factory['facts'] ?? [])) continue;
        $position = (string) ($row['image_position'] ?? 'auto');
        if (!in_array($position, ['left', 'right'], true)) $position = count($sections) % 2 === 0 ? 'left' : 'right';
        $link = $shared ? ($factory['link'] ?? []) : ($row['link'] ?? []);
        $sections[] = ['title' => $title, 'image' => $image, 'video' => $video, 'body' => $body, 'position' => $position, 'factory' => $factory, 'link' => is_array($link) ? $link : []];
    }
    return $sections;
}

/** Contextual taxonomy links come from real children or siblings, including parent navigation. */
function category_navigation(\WP_Term $term): array {
    $children = get_terms(['taxonomy' => $term->taxonomy, 'parent' => $term->term_id, 'hide_empty' => true]);
    $terms = is_array($children) && $children ? $children : get_terms(['taxonomy' => $term->taxonomy, 'parent' => $term->parent, 'hide_empty' => true]);
    $terms = is_array($terms) ? $terms : [];
    $links = [];
    if ($term->parent) {
        $parent = get_term($term->parent, $term->taxonomy);
        if ($parent instanceof \WP_Term) array_unshift($terms, $parent);
    }
    foreach (is_array($terms) ? $terms : [] as $item) {
        $url = get_term_link($item);
        if (!is_wp_error($url)) $links[] = ['name' => $item->name, 'url' => $url, 'current' => $item->term_id === $term->term_id];
    }
    return array_filter($links, static fn (array $link): bool => !$link['current']) ? $links : [];
}

function product_category_data($term = null): array {
    $term = $term instanceof \WP_Term ? $term : category_current_term();
    if (!$term instanceof \WP_Term) return [];

    $term_id = (int) $term->term_id;
    $object_id = category_content_object($term_id);
    $parse_resources = static function (array $items): array {
        $rows = [];
        foreach ($items as $item) {
            $parts = array_map('trim', explode('|', (string) $item, 2));
            $label = $parts[0] ?? '';
            $url = $parts[1] ?? '';
            if ($label === '') continue;
            $rows[] = [
                'label' => $label,
                'url' => $url,
                'is_link' => $url !== '' && (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '/')),
            ];
        }
        return $rows;
    };

    $hero_image = function_exists('get_field') ? get_field('category_hero_image', $object_id) : null;
    if (is_numeric($hero_image)) $hero_image = ['id' => (int) $hero_image];
    $hero_image = is_array($hero_image) ? normalize_product_image($hero_image) : [];

    $related_terms = get_terms([
        'taxonomy' => 'product_collection',
        'hide_empty' => false,
        'exclude' => [$term_id],
        'number' => 6,
    ]);
    $related_categories = [];
    if (is_array($related_terms)) {
        foreach ($related_terms as $related_term) {
            $url = get_term_link($related_term);
            if (is_wp_error($url)) continue;
            $related_categories[] = [
                'name' => (string) $related_term->name,
                'url' => (string) $url,
                'description' => wp_trim_words(wp_strip_all_tags((string) $related_term->description), 18, '…'),
            ];
        }
    }

    $guide = !is_paged() && function_exists('get_field') ? (string) get_field('category_long_description', $object_id, true, true) : '';
    return [
        'name' => is_singular('starter_category') ? get_the_title(get_queried_object_id()) : (string) $term->name,
        'slug' => (string) $term->slug,
        'overline' => text('category_overline', $object_id, 'Product range'),
        'intro' => category_meta_exists($term_id, 'category_intro') ? text('category_intro', $object_id) : wp_strip_all_tags((string) term_description($term)),
        'hero_image' => $hero_image,
        'show_products' => !category_meta_exists($term_id, 'category_show_products') || (bool) get_field('category_show_products', $object_id),
        'navigation' => category_navigation($term),
        'content_sections' => !is_paged() ? category_content_sections($term_id) : [],
        'key_facts' => category_repeater_rows('category_fact_items', $term_id, 'label', 'value', 'category_key_facts'),
        'features' => category_repeater_rows('category_feature_items', $term_id, 'label', 'value', 'category_features'),
        'selection_guide' => category_repeater_rows('category_selection_items', $term_id, 'label', 'value', 'category_selection_guide'),
        'attribute_tables' => normalize_table_groups((array) (function_exists('get_field') ? get_field('category_attribute_tables', $object_id) : []), 'Category attributes'),
        'specifications' => normalize_attribute_rows(category_repeater_rows('category_spec_items', $term_id, 'label', 'value', 'category_specifications')),
        'applications' => lines('category_applications', $object_id),
        'use_cases' => category_repeater_rows('category_use_case_items', $term_id, 'label', 'value', 'category_use_cases'),
        'standards' => lines('category_standards', $object_id),
        'process' => category_repeater_rows('category_process_items', $term_id, 'label', 'value', 'category_process'),
        'checklist' => lines('category_checklist', $object_id),
        'resources' => $parse_resources(lines('category_resources', $object_id)),
        'faq' => normalize_attribute_rows(category_repeater_rows('category_faq_items', $term_id, 'question', 'answer', 'category_faq')),
        'guide_title' => text('category_guide_title', $object_id, 'Buying guide'),
        'long_description' => has_rich_content($guide) ? $guide : '',
        'related_categories' => $related_categories,
        'cta' => [
            'title' => text('category_cta_title', $object_id, 'Request a quotation'),
            'description' => text('category_cta_text', $object_id, 'Send your requirement and receive a project-specific response.'),
            'button_label' => text('category_cta_button', $object_id, 'Contact us'),
            'url' => contact_url($term->name),
        ],
    ];
}

/** Complete industry rows; blank rows and numeric zero have explicit semantics. */
function industry_rows(string $name, int $post_id): array {
    $raw = function_exists('get_field') ? get_field($name, $post_id) : [];
    return normalize_attribute_rows(is_array($raw) ? $raw : []);
}

/** Optional headings use defaults only before the field has ever been saved. */
function industry_text(string $name, int $post_id, string $default = ''): string {
    return metadata_exists('post', $post_id, $name) ? field_text($name, $post_id) : text($name, $post_id, $default);
}

/** The active media type owns output. A missing video does not revive its saved image. */
function industry_modules(int $post_id): array {
    $raw = function_exists('get_field') ? get_field('industry_modules', $post_id) : [];
    $result = [];
    foreach (is_array($raw) ? $raw : [] as $row) {
        if (!is_array($row)) continue;
        $title = trim((string) ($row['title'] ?? ''));
        if ($title === '') continue;
        $image = 0;
        $video = [];
        if (($row['media_type'] ?? 'image') === 'video') {
            $id = (int) ($row['video'] ?? 0);
            $mime = $id > 0 ? get_post_mime_type($id) : false;
            $url = $id > 0 && get_post_type($id) === 'attachment' ? wp_get_attachment_url($id) : false;
            if ($url && in_array($mime, ['video/mp4', 'video/webm', 'video/ogg'], true)) {
                $poster_id = (int) ($row['video_poster'] ?? 0);
                $poster = $poster_id > 0 && wp_attachment_is_image($poster_id) ? wp_get_attachment_image_url($poster_id, 'large') : false;
                $video = ['id' => $id, 'url' => (string) $url, 'mime' => (string) $mime, 'poster' => $poster ? (string) $poster : ''];
            }
        } else {
            $id = (int) ($row['image'] ?? 0);
            if ($id > 0 && wp_attachment_is_image($id) && wp_get_attachment_image_url($id, 'large')) $image = $id;
        }
        $body = (string) ($row['body'] ?? '');
        if (!has_rich_content($body)) $body = '';
        if (!$image && !$video && $body === '') continue;
        $position = (string) ($row['image_position'] ?? 'auto');
        if (!in_array($position, ['left', 'right'], true)) $position = count($result) % 2 === 0 ? 'left' : 'right';
        $link = $row['link'] ?? [];
        $result[] = ['title' => $title, 'image' => $image, 'video' => $video, 'body' => $body, 'position' => $position, 'factory' => [], 'link' => is_array($link) ? $link : []];
    }
    return $result;
}

function industry_data(): array {
    $post_id = (int) get_the_ID();
    $selected = function_exists('get_field') ? get_field('industry_products', $post_id) : [];
    $product_ids = array_values(array_unique(array_filter(array_map('intval', is_array($selected) ? $selected : []),
        static fn (int $id): bool => $id > 0 && get_post_type($id) === 'starter_product' && get_post_status($id) === 'publish'
    )));
    return [
        'challenge' => text('challenge', $post_id),
        'constraints_title' => industry_text('industry_constraints_title', $post_id, 'Start with the site constraints'),
        'constraints' => industry_rows('industry_constraints', $post_id),
        'applications_title' => industry_text('industry_applications_title', $post_id, 'A solution shaped around your work'),
        'modules' => industry_modules($post_id),
        'products_title' => industry_text('industry_products_title', $post_id, 'Compare equipment for this application'),
        'products_intro' => text('industry_products_intro', $post_id),
        'outcome' => text('outcome', $post_id),
        'planning_title' => industry_text('industry_planning_title', $post_id, 'Prepare your application brief'),
        'planning_intro' => text('industry_planning_intro', $post_id),
        'steps' => industry_rows('industry_steps', $post_id),
        'planning_rows' => industry_rows('industry_planning_rows', $post_id),
        'note' => text('industry_note', $post_id),
        'faq_title' => industry_text('industry_faq_title', $post_id, 'Questions before you choose'),
        'faq' => industry_rows('industry_faq', $post_id),
        'show_inquiry' => !metadata_exists('post', $post_id, 'industry_show_inquiry') || (bool) get_field('industry_show_inquiry', $post_id),
        'product_ids' => $product_ids,
        'cta' => [
            'title' => industry_text('industry_cta_title', $post_id, 'Tell us about your project'),
            'description' => text('industry_cta_text', $post_id),
            'button_label' => industry_text('industry_cta_button', $post_id, 'Discuss your project'),
            'url' => contact_url(get_the_title()),
        ],
    ];
}

function archive_data(string $key, string $fallback): array {
    return ['description' => global_text($key . '_archive_description', $fallback)];
}

