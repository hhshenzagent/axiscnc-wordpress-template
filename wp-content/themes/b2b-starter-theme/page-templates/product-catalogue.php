<?php
/**
 * Template Name: Product catalogue
 * Template Post Type: page
 * Template Content Source: ACF
 *
 * @package b2b-starter
 */

get_header(); ?>
<main id="main" class="shell"><?php while (have_posts()) : the_post(); $factory = Starter\Theme\factory_profile_data(); ?>
	<?php Starter\Theme\component_page_head([
		'eyebrow' => 'Catalogue', 'title' => get_the_title(),
		'description' => Starter\Theme\field_option('product_archive_description') ?: get_the_excerpt(),
	]); ?>
	<section class="section">
		<?php Starter\Theme\component_section_heading(['eyebrow' => 'Product families', 'title' => 'Choose a product range']); ?>
		<?php Starter\Theme\term_cards('product_collection'); ?>
	</section>
	<section class="section">
		<?php Starter\Theme\component_section_heading(['eyebrow' => 'Products', 'title' => 'All products']); ?>
		<?php Starter\Theme\component_card_grid(['query_args' => ['post_type' => 'starter_product', 'posts_per_page' => 12]]); ?>
	</section>
	<?php Starter\Theme\component_cta_band([
		'title' => 'Cannot find the exact product?', 'text' => 'Send your specification and target market for a custom proposal.',
		'button' => ['url' => Starter\Theme\contact_url(), 'label' => 'Request a quote'],
	]); ?>
<?php endwhile; ?></main>
<?php get_footer(); ?>

