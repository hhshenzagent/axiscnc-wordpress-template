<?php
/**
 * Hero accepts normalized props from the page controller.
 *
 * @package b2b-starter
 */

$props = $args ?? [];
$eyebrow = (string) ($props['eyebrow'] ?? '');
$title = (string) ($props['title'] ?? get_the_title());
$description = (string) ($props['description'] ?? get_the_excerpt());
$primary = (string) ($props['primary_label'] ?? 'Request a quotation');
$secondary = (string) ($props['secondary_label'] ?? 'Browse the catalogue');
$term_index = is_array($props['term_index'] ?? null) ? $props['term_index'] : [];
?>
<section class="hero<?php echo has_post_thumbnail() ? ' has-media' : ' no-media'; ?><?php echo !has_post_thumbnail() && $term_index ? ' with-index' : ''; ?>">
<div>
<?php if ($eyebrow !== '') : ?><p class="eyebrow"><?php echo esc_html($eyebrow); ?></p><?php endif; ?>
<h1><?php echo esc_html($title); ?></h1>
<?php if ($description !== '') : ?><div class="prose"><?php echo wp_kses_post(wpautop($description)); ?></div><?php endif; ?>
<div class="hero-actions">
	<a class="button" href="<?php echo esc_url(Starter\Theme\contact_url()); ?>"><?php echo esc_html($primary); ?></a>
	<a class="ghost-button" href="<?php echo esc_url(get_post_type_archive_link('starter_product') ?: home_url('/products/')); ?>"><?php echo esc_html($secondary); ?></a>
</div>
</div>
<?php if (has_post_thumbnail()) : ?><div class="hero-photo"><?php the_post_thumbnail('large', ['loading' => 'eager']); ?></div><?php endif; ?>
<?php if (!has_post_thumbnail() && $term_index) : ?>
<nav class="hero-index" aria-label="Product families">
	<p class="hero-index-label">Explore the range</p>
	<ol>
		<?php foreach ($term_index as $index => $item) : ?>
		<li><a href="<?php echo esc_url((string) ($item['url'] ?? '')); ?>"><span><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span><strong><?php echo esc_html((string) ($item['name'] ?? '')); ?></strong><span aria-hidden="true">↗</span></a></li>
		<?php endforeach; ?>
	</ol>
	<a class="hero-index-all" href="<?php echo esc_url(get_post_type_archive_link('starter_product') ?: home_url('/products/')); ?>">View full catalogue <span aria-hidden="true">→</span></a>
</nav>
<?php endif; ?>
</section>

