<?php
namespace Starter\Theme;
defined('ABSPATH') || exit;

function category_current_term(): ?\WP_Term {
    $object = get_queried_object();
    if ($object instanceof \WP_Term) return $object;
    if (!$object instanceof \WP_Post || $object->post_type !== 'starter_category') return null;
    $id = (int) get_field('category_collection', $object->ID);
    $term = $id ? get_term($id, 'product_collection') : null;
    return $term instanceof \WP_Term ? $term : null;
}

/** The page is the sole owner after migration. Saved empties never fall back to term metadata. */
function category_content_object(int $term_id) {
    if (is_singular('starter_category')) {
        $current = category_current_term();
        if ($current && $current->term_id === $term_id) return get_queried_object_id();
    }
    $id = \Starter\Model\category_page_for_term($term_id);
    return $id ?: 'product_collection_' . $term_id;
}

function category_meta_exists(int $term_id, string $name): bool {
    $object = category_content_object($term_id);
    return metadata_exists(is_int($object) ? 'post' : 'term', is_int($object) ? $object : $term_id, $name);
}

function category_product_query(): \WP_Query {
    if (!is_singular('starter_category')) return $GLOBALS['wp_query'];
    $term = category_current_term();
    return new \WP_Query([
        'post_type' => 'starter_product', 'post_status' => 'publish',
        'posts_per_page' => max(1, (int) get_option('posts_per_page')), 'paged' => max(1, (int) get_query_var('paged')),
        'tax_query' => [['taxonomy' => 'product_collection', 'field' => 'term_id', 'terms' => $term ? [$term->term_id] : [0], 'include_children' => true]],
    ]);
}

function render_category_page(string $layout = 'standard'): void {
    if (!category_current_term()) {
        get_header();
        echo '<main id="main" class="shell section"><h1>' . esc_html(get_the_title(get_queried_object_id())) . '</h1><p>' . esc_html__('Select a product classification in this page’s editor.', 'b2b-starter') . '</p></main>';
        get_footer();
        return;
    }
    render_category_archive($layout);
}

