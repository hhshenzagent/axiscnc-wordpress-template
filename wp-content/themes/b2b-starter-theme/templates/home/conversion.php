<?php
/**
 * Homepage partial: conversion / RFQ first layout.
 *
 * @package b2b-starter
 */

$context = get_query_var('home_template_context');
$home = $context['home'] ?? Starter\Theme\homepage_data();
$factory = $context['factory'] ?? Starter\Theme\factory_profile_data();
?>
<main id="main" class="shell">
	<?php get_template_part('parts/hero', null, [
		'eyebrow' => $home['overline'], 'description' => $home['description'] ?: get_the_excerpt(),
		'primary_label' => $home['cta']['button_label'], 'secondary_label' => 'See featured products',
		'term_index' => $home['term_index'],
	]); ?>
<?php Starter\Theme\component_stat_strip(['items' => $home['stats']]); ?>
<section class="section">
	<?php Starter\Theme\component_section_heading(['eyebrow' => 'RFQ', 'title' => 'How a quotation works']); ?>
	<?php Starter\Theme\component_process_steps(['rows' => $factory['process']]); ?>
</section>
<section class="section home-products">
	<?php Starter\Theme\component_section_heading(['eyebrow' => 'Catalogue', 'title' => $home['sections']['products']['title']]); ?>
	<?php Starter\Theme\component_card_grid(['query_args' => ['post_type' => 'starter_product', 'posts_per_page' => $home['sections']['products']['count']]]); ?>
</section>
<?php Starter\Theme\component_factory_capability([
	'intro' => $factory['intro'], 'capabilities' => $factory['capabilities'], 'process' => $factory['process'],
	'quality_tests' => $factory['quality_tests'], 'certifications' => $factory['certifications'],
]); ?>
<?php if ($home['sections']['applications']['enabled']) : ?>
<section class="section home-categories">
	<?php Starter\Theme\component_section_heading(['eyebrow' => 'Applications', 'title' => $home['sections']['applications']['title']]); ?>
	<?php Starter\Theme\term_cards('product_collection'); ?>
</section>
<?php endif; ?>
<?php Starter\Theme\component_cta_band(array_merge(['eyebrow' => 'Start here'], $home['cta'])); ?>
</main>

