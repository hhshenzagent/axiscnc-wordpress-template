<?php
/**
 * Generic archive fallback with an editorial blog layout.
 *
 * @package b2b-starter
 */

get_header(); ?>
<main id="main" class="shell">
	<header class="blog-hero">
		<p class="eyebrow"><?php
			if (is_category()) echo 'Category';
			elseif (is_tag()) echo 'Topic';
			elseif (is_author()) echo 'Author';
			elseif (is_date()) echo 'Archive';
			else echo 'Archive';
		?></p>
		<h1><?php echo esc_html(wp_strip_all_tags((string) get_the_archive_title())); ?></h1>
		<?php $archive_description = get_the_archive_description(); ?>
		<?php if ($archive_description) : ?><div class="archive-description"><?php echo wp_kses_post($archive_description); ?></div><?php endif; ?>
	</header>

	<?php Starter\Theme\component_blog_archive(); ?>
	<?php the_posts_pagination(['prev_text' => '←', 'next_text' => '→']); ?>
</main>
<?php get_footer(); ?>

