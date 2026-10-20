<?php
/**
 * Knowledge guides archive.
 *
 * @package b2b-starter
 */

get_header(); ?>
<main id="main" class="shell">
	<?php Starter\Theme\component_page_head([
		'eyebrow' => 'Knowledge',
		'title' => 'Knowledge guides',
		'description' => Starter\Theme\field_option('guide_archive_description') ?: 'Practical specification, installation and certification notes for project buyers.',
	]); ?>
	<?php Starter\Theme\component_card_grid(['main_query' => true, 'pagination' => true]); ?>
</main>
<?php get_footer(); ?>

