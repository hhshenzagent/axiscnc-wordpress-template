<?php
/**
 * Homepage partial: product-led catalogue layout.
 *
 * @package b2b-starter
 */

$context = get_query_var('home_template_context');
$home = $context['home'] ?? Starter\Theme\homepage_data();
$factory = $context['factory'] ?? Starter\Theme\factory_profile_data();
$factory_proof = array_values(array_filter($factory['proof'], static function (array $proof) use ($home): bool {
	foreach ($home['stats'] as $stat) {
		if (trim((string) ($stat['value'] ?? '')) !== ''
			&& trim((string) ($stat['value'] ?? '')) === trim((string) ($proof['value'] ?? ''))
			&& trim((string) ($stat['label'] ?? '')) === trim((string) ($proof['label'] ?? ''))) return false;
	}
	return true;
}));
?>
<main id="main" class="shell">
	<?php get_template_part('parts/hero', null, [
		'eyebrow' => $home['overline'],
		'description' => $home['description'] ?: get_the_excerpt(),
		'primary_label' => $home['cta']['button_label'],
		'secondary_label' => 'Explore applications',
		'term_index' => $home['term_index'],
	]); ?>
<?php Starter\Theme\component_stat_strip(['items' => $home['stats']]); ?>
<?php Starter\Theme\component_factory_strip(['proof' => $factory_proof, 'certifications' => $factory['certifications'], 'markets' => $factory['markets']]); ?>
<section class="section home-products">
	<?php Starter\Theme\component_section_heading([
		'eyebrow' => 'Catalogue', 'title' => $home['sections']['products']['title'],
		'link' => ['url' => get_post_type_archive_link('starter_product') ?: home_url('/products/'), 'label' => 'All products →'],
	]); ?>
	<?php Starter\Theme\component_card_grid(['query_args' => ['post_type' => 'starter_product', 'posts_per_page' => $home['sections']['products']['count']]]); ?>
</section>
<section class="section home-categories">
	<?php Starter\Theme\component_section_heading(['eyebrow' => 'Applications', 'title' => $home['sections']['applications']['title']]); ?>
	<?php Starter\Theme\term_cards('product_collection'); ?>
</section>
<?php Starter\Theme\component_factory_capability([
	'intro' => $factory['intro'], 'capabilities' => $factory['capabilities'], 'process' => $factory['process'],
	'quality_tests' => $factory['quality_tests'], 'certifications' => $factory['certifications'],
]); ?>
<?php if ($home['sections']['guides']['enabled']) : ?>
<section class="section home-guides">
	<?php Starter\Theme\component_section_heading(['eyebrow' => 'Knowledge', 'title' => $home['sections']['guides']['title']]); ?>
	<?php Starter\Theme\component_card_grid(['query_args' => ['post_type' => 'starter_guide', 'posts_per_page' => $home['sections']['guides']['count']]]); ?>
</section>
<?php endif; ?>
<?php Starter\Theme\component_cta_band(array_merge(['eyebrow' => 'Start here'], $home['cta'])); ?>
</main>

