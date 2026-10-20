<?php
/**
 * Single guide: article column, prev/next navigation and CTA.
 *
 * @package b2b-starter
 */

get_header(); ?>
<main id="main" class="shell"><?php while (have_posts()) : the_post();
	Starter\Theme\component_page_head([
		'eyebrow' => 'Knowledge',
		'title' => get_the_title(),
	]);
	?>
	<article class="prose article"><?php the_content(); ?></article>
	<?php $prev = get_previous_post(); $next = get_next_post(); ?>
	<?php if ($prev || $next) : ?>
	<nav class="guide-nav" aria-label="More guides">
		<?php if ($prev) : ?>
		<a class="prev" href="<?php echo esc_url(get_permalink($prev)); ?>"><span class="guide-nav-label">Previous guide</span><span class="guide-nav-title"><?php echo esc_html(get_the_title($prev)); ?></span></a>
		<?php endif; ?>
		<?php if ($next) : ?>
		<a class="next" href="<?php echo esc_url(get_permalink($next)); ?>"><span class="guide-nav-label">Next guide</span><span class="guide-nav-title"><?php echo esc_html(get_the_title($next)); ?></span></a>
		<?php endif; ?>
	</nav>
	<?php endif; ?>
	<?php Starter\Theme\component_cta_band([
		'eyebrow' => 'Next step',
		'title' => 'Specifying a project?',
		'text' => 'Send drawings or a schedule — itemised pricing in three working days.',
		'button' => ['url' => Starter\Theme\contact_url(), 'label' => 'Contact our team'],
	]); ?>
<?php endwhile; ?></main>
<?php get_footer(); ?>

