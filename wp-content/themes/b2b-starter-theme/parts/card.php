<?php
/**
 * Universal content card. $args: title_tag (h2|h3), kind (product|industry|guide|post), show_more.
 *
 * @package b2b-starter
 */

$kind = $args['kind'] ?? (string) get_post_type();
$kind = str_contains($kind, 'product') ? 'product' : (str_contains($kind, 'industry') ? 'industry' : (str_contains($kind, 'guide') ? 'guide' : 'post'));
$title_tag = ($args['title_tag'] ?? 'h2') === 'h3' ? 'h3' : 'h2';

$excerpt = trim(wp_trim_words(get_the_excerpt(), 22));
$chip = '';
if ($kind === 'product') {
    $terms = get_the_terms(get_the_ID(), 'product_collection');
    if (is_array($terms) && $terms && !is_wp_error($terms[0])) {
        $chip = $terms[0]->name;
    }
} elseif ($kind === 'industry') {
    $chip = 'Solution';
} elseif ($kind === 'guide') {
    $chip = esc_html(get_the_date());
}
?>
<article class="card card--<?php echo esc_attr($kind); ?><?php echo has_post_thumbnail() ? ' has-media' : ' no-media'; ?>">
	<a class="card-media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php Starter\Theme\card_media($kind); ?></a>
	<div class="card-body">
		<?php if ($chip !== '' && empty($args['show_specs'])) : ?><span class="card-chip"><?php echo esc_html($chip); ?></span><?php endif; ?>
		<<?php echo esc_html($title_tag); ?> class="card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></<?php echo esc_html($title_tag); ?>>
		<?php if ($excerpt !== '') : ?><p class="excerpt"><?php echo esc_html($excerpt); ?></p><?php endif; ?>
		<?php if ($kind === 'product' && !empty($args['show_specs'])) : ?>
		<?php $specs = array_slice(Starter\Theme\normalize_attribute_rows(Starter\Theme\product_repeater_rows('product_quick_specs', (int) get_the_ID(), 'quick_label', 'quick_value', 'quick_specs')), 0, 2); ?>
		<?php if ($specs) : ?><dl class="category-card-specs"><?php foreach ($specs as $spec) : ?><div><dt><?php echo esc_html($spec['label']); ?></dt><dd><?php echo esc_html($spec['value']); ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
		<?php endif; ?>
		<?php if (($args['show_more'] ?? true) && $kind !== 'product') : ?><span class="more"><?php echo esc_html($kind === 'industry' ? 'View solution' : 'Read guide'); ?></span><?php endif; ?>
	</div>
</article>

