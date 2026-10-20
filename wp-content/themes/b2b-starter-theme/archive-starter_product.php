<?php
/**
 * Product catalogue archive.
 *
 * @package b2b-starter
 */

$families = get_terms(['taxonomy' => 'product_collection', 'hide_empty' => false, 'fields' => 'ids']);
$has_families = !is_wp_error($families) && !empty($families);
$product_count = (int) ($GLOBALS['wp_query']->found_posts ?? 0);
$product_columns = min(4, max(1, $product_count));
$intro = Starter\Theme\field_option('product_archive_description');

get_header(); ?>
<main id="main" class="shell product-catalogue">
	<?php if ($has_families) : ?>
	<section class="section catalogue-families">
		<?php Starter\Theme\component_section_heading(['eyebrow' => 'Product families', 'title' => 'Choose a product range', 'title_tag' => 'h1', 'description' => $intro]); ?>
		<?php Starter\Theme\term_cards('product_collection', 'h2'); ?>
	</section>
	<?php endif; ?>
	<section class="section catalogue-products product-columns-<?php echo esc_attr((string) $product_columns); ?><?php echo $product_count === 1 ? ' has-single-product' : ''; ?>" aria-label="Product listings">
		<?php Starter\Theme\component_section_heading(['title' => 'All products', 'title_tag' => $has_families ? 'h2' : 'h1', 'description' => $has_families ? '' : $intro]); ?>
		<?php Starter\Theme\component_card_grid(['main_query' => true, 'pagination' => true, 'title_tag' => $has_families ? 'h3' : 'h2', 'show_specs' => true]); ?>
	</section>
</main>
<?php get_footer(); ?>

