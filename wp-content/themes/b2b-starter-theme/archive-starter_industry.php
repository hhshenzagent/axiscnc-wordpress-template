<?php
/**
 * Industry solutions archive.
 *
 * @package b2b-starter
 */

get_header(); ?>
<main id="main" class="shell industry-archive">
	<?php Starter\Theme\component_page_head([
		'eyebrow' => 'Sectors',
		'title' => 'Industry solutions',
		'description' => Starter\Theme\field_option('industry_archive_description') ?: 'Explore applications and the product requirements for each use case.',
	]); ?>
	<?php Starter\Theme\component_card_grid(['main_query' => true, 'pagination' => true]); ?>
</main>
<?php get_footer(); ?>

