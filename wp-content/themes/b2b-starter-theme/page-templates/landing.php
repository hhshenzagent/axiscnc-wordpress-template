<?php
/**
 * Template Name: Landing page
 * Template Post Type: page
 * Template Content Source: ACF
 *
 * @package b2b-starter
 */

get_header(); ?>
<main id="main" class="shell"><?php while (have_posts()) : the_post();
	Starter\Theme\component_page_head([
		'eyebrow' => Starter\Theme\text('landing_overline'),
		'title' => get_the_title(),
		'description' => Starter\Theme\text('landing_description'),
	]);
	?>
	<?php Starter\Theme\component_feature_grid(['rows' => Starter\Theme\product_repeater_rows('landing_feature_items', (int) get_the_ID(), 'label', 'value', 'landing_features')]); ?>
	<?php $body = Starter\Theme\module_body('landing_body'); if (trim($body) !== '') : ?><article class="prose article"><?php echo $body; // Formatted ACF or preserved legacy body. ?></article><?php endif; ?>
	<?php Starter\Theme\component_cta_band([
		'title' => Starter\Theme\text('landing_cta_title', null, 'Request a quote'),
		'text' => Starter\Theme\text('landing_cta_text'),
		'button' => ['url' => Starter\Theme\contact_url(), 'label' => Starter\Theme\text('landing_cta_button', null, 'Contact us')],
	]);
endwhile; ?></main>
<?php get_footer(); ?>

