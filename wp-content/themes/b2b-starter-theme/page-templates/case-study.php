<?php
/**
 * Template Name: Case study
 * Template Post Type: page
 * Template Editor Pattern: case-study-body
 *
 * @package b2b-starter
 */

get_header(); ?>
<main id="main" class="shell"><?php while (have_posts()) : the_post(); ?>
	<?php Starter\Theme\component_page_head([
		'eyebrow' => 'Case study', 'title' => get_the_title(), 'description' => get_the_excerpt(),
	]); ?>
	<?php if (has_post_thumbnail()) the_post_thumbnail('wide', ['class' => 'article-photo']); ?>
	<article class="prose article"><?php the_content(); ?></article>
	<?php $factory = Starter\Theme\factory_profile_data(); Starter\Theme\component_factory_strip([
		'proof' => $factory['proof'], 'certifications' => $factory['certifications'], 'markets' => $factory['markets'],
	]); ?>
	<?php Starter\Theme\component_cta_band([
		'title' => 'Planning a similar project?', 'text' => 'Send the application, target specification and delivery window.',
		'button' => ['url' => Starter\Theme\contact_url(), 'label' => 'Start an enquiry'],
	]); ?>
<?php endwhile; ?></main>
<?php get_footer(); ?>

