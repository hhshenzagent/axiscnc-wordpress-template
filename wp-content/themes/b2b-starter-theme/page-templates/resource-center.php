<?php
/**
 * Template Name: Resource center
 * Template Post Type: page
 * Template Content Source: ACF
 *
 * @package b2b-starter
 */

get_header(); ?>
<main id="main" class="shell"><?php while (have_posts()) : the_post(); ?>
	<?php $resource = Starter\Theme\resource_data((int) get_the_ID()); ?>
	<?php Starter\Theme\component_page_head([
		'eyebrow' => 'Resources', 'title' => get_the_title(), 'description' => $resource['summary'],
	]); ?>
	<?php if ($resource['intro'] !== '') : ?>
		<section class="section prose resource-intro"><?php echo $resource['intro']; // ACF HTML escaping and formatting are applied in the getter. ?></section>
	<?php endif; ?>
	<section class="section">
		<?php Starter\Theme\component_section_heading(['eyebrow' => 'Knowledge', 'title' => 'Technical guides']); ?>
		<?php Starter\Theme\component_card_grid(['query_args' => ['post_type' => 'starter_guide', 'post_status' => 'publish', 'posts_per_page' => 6, 'no_found_rows' => true]]); ?>
	</section>
	<section class="section">
		<?php Starter\Theme\component_section_heading(['eyebrow' => 'News', 'title' => 'Company updates']); ?>
		<?php Starter\Theme\component_blog_archive(['query' => new WP_Query([
			'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 6,
			'ignore_sticky_posts' => true, 'no_found_rows' => true,
		])]); ?>
	</section>
	<?php Starter\Theme\component_cta_band([
		'title' => 'Need a document?', 'text' => 'Request datasheets, drawings, certification packs or project evidence.',
		'button' => ['url' => Starter\Theme\contact_url(), 'label' => 'Request resources'],
	]); ?>
<?php endwhile; ?></main>
<?php get_footer(); ?>

