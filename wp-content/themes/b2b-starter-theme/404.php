<?php
/**
 * 404: search + primary destinations.
 *
 * @package b2b-starter
 */

get_header(); ?>
<main id="main" class="shell">
	<?php Starter\Theme\component_page_head([
		'eyebrow' => '404',
		'title' => 'Page not found',
		'description' => Starter\Theme\field_option('not_found_description') ?: 'The page may have moved. Search the site or start from one of these:',
	]); ?>
	<div class="error-search"><?php get_search_form(); ?></div>
	<?php Starter\Theme\component_link_cards(['links' => [
		['label' => 'Product catalogue', 'url' => home_url('/products/'), 'description' => 'Browse the full catalogue.'],
		['label' => 'Industry solutions', 'url' => home_url('/industries/'), 'description' => 'Find solutions by sector.'],
		['label' => 'Knowledge guides', 'url' => home_url('/guides/'), 'description' => 'Read specification guides.'],
		['label' => 'Contact us', 'url' => home_url('/contact/'), 'description' => 'Request a quotation.'],
	]]); ?>
</main>
<?php get_footer(); ?>

