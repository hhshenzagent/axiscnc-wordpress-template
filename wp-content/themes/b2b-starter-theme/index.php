<?php
/**
 * Fallback index: editorial archive layout for uncached post collections.
 *
 * @package b2b-starter
 */

get_header(); ?>
<main id="main" class="shell">
	<header class="blog-hero">
		<p class="eyebrow">News &amp; insights</p>
		<h1><?php echo esc_html(wp_strip_all_tags((string) get_the_archive_title() ?: 'News & updates')); ?></h1>
		<p><?php echo esc_html(Starter\Theme\field_option('news_archive_description') ?: 'Updates from the team.'); ?></p>
	</header>

	<?php Starter\Theme\component_blog_archive(); ?>
	<?php the_posts_pagination(['prev_text' => '←', 'next_text' => '→']); ?>
</main>
<?php get_footer(); ?>

