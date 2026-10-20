<?php
/**
 * Editorial post card for blog archives and related article sections.
 *
 * @package b2b-starter
 */

$props = $args ?? [];
$featured = (bool) ($props['featured'] ?? false);
$show_meta = (bool) ($props['show_meta'] ?? true);
$show_more = (bool) ($props['show_more'] ?? true);
$category = Starter\Theme\blog_primary_category();
$reading_time = Starter\Theme\blog_reading_time();
$author_name = get_the_author() ?: get_bloginfo('name');
$author_id = (int) get_the_author_meta('ID');
$modified = Starter\Theme\blog_modified_is_visible();
$excerpt = trim((string) wp_trim_words(get_the_excerpt(), $featured ? 34 : 22));
?>
<article class="blog-card<?= $featured ? ' is-featured' : ''; ?>">
	<?php if (has_post_thumbnail()) : ?>
	<a class="blog-media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php Starter\Theme\card_media('post'); ?>
	</a>
	<?php endif; ?>
	<div class="blog-body">
		<?php if ($show_meta) : ?>
		<div class="blog-meta">
			<?php if ($category instanceof WP_Term) : ?>
				<a class="blog-chip" href="<?php echo esc_url((string) get_category_link($category)); ?>"><?php echo esc_html($category->name); ?></a>
			<?php endif; ?>
			<time datetime="<?php echo esc_attr((string) get_the_date('c')); ?>"><?php echo esc_html((string) get_the_date()); ?></time>
			<span><?php echo esc_html($reading_time); ?> min read</span>
		</div>
		<?php endif; ?>
		<<?php echo $featured ? 'h2' : 'h3'; ?> class="blog-title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</<?php echo $featured ? 'h2' : 'h3'; ?>>
		<?php if ($excerpt !== '') : ?><p class="blog-excerpt"><?php echo esc_html($excerpt); ?></p><?php endif; ?>
		<?php if ($show_more) : ?><span class="more">Read article</span><?php endif; ?>
	</div>
</article>

