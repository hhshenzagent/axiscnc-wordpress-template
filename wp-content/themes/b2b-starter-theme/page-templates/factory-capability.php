<?php
/**
 * Template Name: Factory capability
 * Template Post Type: page
 * Template Content Source: ACF
 *
 * @package b2b-starter
 */

get_header(); ?>
<main id="main" class="shell"><?php while (have_posts()) : the_post(); $factory = Starter\Theme\factory_profile_data(); ?>
	<?php Starter\Theme\component_page_head([
		'eyebrow' => 'Factory', 'title' => get_the_title(),
		'description' => get_the_excerpt() ?: $factory['intro'],
	]); ?>
	<?php Starter\Theme\component_stat_strip(['items' => array_map(
		static fn (array $row): array => ['value' => $row['value'], 'label' => $row['label']],
		$factory['proof']
	)]); ?>
	<?php Starter\Theme\component_factory_capability([
		'eyebrow' => 'Capability', 'title' => 'Production, process and quality',
		'intro' => $factory['intro'], 'capabilities' => $factory['capabilities'],
		'process' => $factory['process'], 'quality_tests' => $factory['quality_tests'],
		'certifications' => $factory['certifications'],
	]); ?>
	<?php $body = Starter\Theme\module_body('factory_body'); if (trim($body) !== '') : ?><article class="prose article"><?php echo $body; // Formatted ACF or preserved legacy body. ?></article><?php endif; ?>
	<?php Starter\Theme\component_cta_band([
		'title' => 'Audit our capability', 'text' => 'Request factory documents, samples or a production review.',
		'button' => ['url' => Starter\Theme\contact_url(), 'label' => 'Contact factory team'],
	]); ?>
<?php endwhile; ?></main>
<?php get_footer(); ?>

