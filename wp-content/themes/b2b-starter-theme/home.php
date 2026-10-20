<?php
/**
 * Blog posts index.
 *
 * @package b2b-starter
 */

get_header(); ?>
<main id="main" class="shell">
<?php
$posts_page_id = (int) (int) get_option('page_for_posts');
$title = $posts_page_id ? get_the_title($posts_page_id) : 'News & insights';
$description = $posts_page_id ? get_post_field('post_excerpt', $posts_page_id) : '';
if ($description === '') {
    $description = 'Updates and practical information for buyers.';
}
$categories = get_categories(['hide_empty' => true, 'number' => 8]);
?>
	<header class="blog-hero">
		<p class="eyebrow">News &amp; insights</p>
		<h1><?php echo esc_html((string) $title); ?></h1>
		<p><?php echo esc_html(wp_strip_all_tags((string) $description)); ?></p>
	</header>

	<?php if ($categories) : ?>
	<nav class="blog-filter" aria-label="Article categories">
		<?php foreach ($categories as $category) : ?>
			<a href="<?php echo esc_url((string) get_category_link($category)); ?>"><?php echo esc_html($category->name); ?></a>
		<?php endforeach; ?>
	</nav>
	<?php endif; ?>

	<?php Starter\Theme\component_blog_archive(); ?>
	<?php the_posts_pagination(['prev_text' => '←', 'next_text' => '→']); ?>
</main>
<?php get_footer(); ?>

