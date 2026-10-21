<?php
/**
 * Reusable presentation components.
 *
 * Templates compose pages from these functions. A component owns markup;
 * CSS owns appearance; content comes in through typed props.
 *
 * @package b2b-starter
 */

namespace Starter\Theme;

defined('ABSPATH') || exit;

/** Inner page heading with optional eyebrow, description and breadcrumbs. */
function component_page_head(array $props = []): void {
	$eyebrow = (string) ($props['eyebrow'] ?? '');
	$title = (string) ($props['title'] ?? '');
	$description = (string) ($props['description'] ?? '');
	$show_breadcrumbs = (bool) ($props['breadcrumbs'] ?? true);

	echo '<header class="page-head">';
	if ($show_breadcrumbs) breadcrumbs();
	if ($eyebrow !== '') echo '<p class="eyebrow">' . esc_html($eyebrow) . '</p>';
	if ($title !== '') echo '<h1>' . esc_html($title) . '</h1>';
	if ($description !== '') echo '<p>' . esc_html($description) . '</p>';
	echo '</header>';
}

/** Standard section heading, optionally with a right-aligned archive link. */
function component_section_heading(array $props = []): void {
	$eyebrow = (string) ($props['eyebrow'] ?? '');
	$title = (string) ($props['title'] ?? '');
	$description = (string) ($props['description'] ?? '');
	$link = $props['link'] ?? [];
	$title_tag = ($props['title_tag'] ?? 'h2') === 'h1' ? 'h1' : 'h2';

	echo '<div class="section-heading"><div>';
	if ($eyebrow !== '') echo '<p class="eyebrow">' . esc_html($eyebrow) . '</p>';
	if ($title !== '') {
		if ($title_tag === 'h1') echo '<h1>' . esc_html($title) . '</h1>';
		else echo '<h2>' . esc_html($title) . '</h2>';
	}
	if ($description !== '') echo '<p>' . esc_html($description) . '</p>';
	echo '</div>';
	if (!empty($link['url']) && !empty($link['label'])) {
		printf('<a class="more" href="%s">%s</a>', esc_url((string) $link['url']), esc_html((string) $link['label']));
	}
	echo '</div>';
}

/** Card grid from an explicit WP_Query args array. */
function component_card_grid(array $props = []): void {
	$query_args = (array) ($props['query_args'] ?? []);
	$query_args['post_type'] = $query_args['post_type'] ?? 'post';
	$query_args['posts_per_page'] = $query_args['posts_per_page'] ?? 3;
	$title_tag = ($props['title_tag'] ?? 'h2') === 'h3' ? 'h3' : 'h2';
	$use_main_query = (bool) ($props['main_query'] ?? false);
	$pagination = (bool) ($props['pagination'] ?? false);

	$query = ($props['query'] ?? null) instanceof \WP_Query ? $props['query'] : ($use_main_query ? $GLOBALS['wp_query'] : new \WP_Query($query_args));
	echo '<div class="grid archive-grid">';
	if ($query->have_posts()) {
		while ($query->have_posts()) : $query->the_post();
            get_template_part('parts/card', null, ['title_tag' => $title_tag, 'show_specs' => !empty($props['show_specs'])]);
		endwhile;
	} else {
		echo '<p class="empty-state">' . esc_html(($props['empty_text'] ?? 'No items yet.')) . '</p>';
	}
	echo '</div>';
	if (!$use_main_query || isset($props['query'])) wp_reset_postdata();
	if ($pagination && isset($props['query']) && $query->max_num_pages > 1) {
        $links = paginate_links(['total' => $query->max_num_pages, 'current' => max(1, (int) get_query_var('paged')), 'prev_text' => '←', 'next_text' => '→']);
        if ($links) echo '<nav class="navigation pagination" aria-label="Products pagination"><div class="nav-links">' . $links . '</div></nav>';
    } elseif ($pagination && $use_main_query) {
		the_posts_pagination(['prev_text' => '←', 'next_text' => '→']);
	}
}

/** Grid of label/value feature cards. */
function component_feature_grid(array $props = []): void {
	$rows = (array) ($props['rows'] ?? []);
	if (!$rows) return;
	echo '<div class="' . esc_attr($props['class'] ?? 'term-grid') . '">';
	foreach ($rows as $row) {
		$label = (string) ($row['label'] ?? '');
		$value = (string) ($row['value'] ?? '');
		if ($label === '' && $value === '') continue;
		printf(
			'<div class="term-card"><h3>%s</h3><p>%s</p></div>',
			esc_html($label),
			esc_html($value)
		);
	}
	echo '</div>';
}

/** Linked taxonomy chips or application pills. */
function component_pill_list(array $props = []): void {
	$items = array_values(array_filter(array_map('trim', (array) ($props['items'] ?? []))));
	if (!$items) return;
	echo '<ul class="app-list">';
	foreach ($items as $item) echo '<li>' . esc_html($item) . '</li>';
	echo '</ul>';
}

/** Collapsible FAQ rows. */
function component_faq(array $props = []): void {
	$rows = (array) ($props['rows'] ?? []);
	if (!$rows) return;
	echo '<div class="faq">';
	foreach ($rows as $row) {
		$label = trim((string) ($row['label'] ?? ''));
		if ($label === '') continue;
		$value = trim((string) ($row['value'] ?? ''));
		if ($value === '') {
			printf('<div class="faq-plain">%s</div>', esc_html($label));
			continue;
		}
		printf('<details class="faq-item"><summary>%s</summary><div class="faq-content prose">%s</div></details>', esc_html($label), !empty($row['rich']) ? $value : wpautop(esc_html($value)));
	}
	echo '</div>';
}

/** Highlight callout; variant may be info or ok. */
function component_callout(array $props = []): void {
	$title = (string) ($props['title'] ?? '');
	$text = (string) ($props['text'] ?? '');
	$variant = ($props['variant'] ?? 'info') === 'ok' ? ' callout--ok' : '';
	if ($title === '' && $text === '') return;
	printf('<section class="callout%s"><h2>%s</h2><p>%s</p></section>', esc_attr($variant), esc_html($title), esc_html($text));
}

/** Label/value specification table. */
function component_spec_table(array $props = []): void {
	$rows = array_values(array_filter((array) ($props['rows'] ?? []), static fn ($row): bool => trim((string) ($row['label'] ?? '')) !== ''));
	if (!$rows) return;
	echo '<div class="spec-table-wrap"><table class="spec-table"><tbody>';
	foreach ($rows as $row) {
		printf('<tr><th scope="row">%s</th><td>%s</td></tr>', esc_html((string) $row['label']), esc_html((string) ($row['value'] ?? '')));
	}
	echo '</tbody></table></div>';
}

/** Compact definition list of editable label/value specifications. */
function component_quick_specs(array $props = []): void {
	$rows = array_values(array_filter((array) ($props['rows'] ?? []), static fn ($row): bool => trim((string) ($row['label'] ?? '')) !== ''));
	if (!$rows) return;
	echo '<dl class="' . esc_attr(trim('quick-specs ' . (string) ($props['class'] ?? ''))) . '">';
	foreach ($rows as $row) {
		printf('<div><dt>%s</dt><dd>%s</dd></div>', esc_html($row['label']), esc_html($row['value']));
	}
	echo '</dl>';
}

/** Small trust/reassurance strip of short claims. */
function component_trust_strip(array $props = []): void {
	$items = array_values(array_filter(array_map('trim', (array) ($props['items'] ?? []))));
	if (!$items) return;
	echo '<p class="trust-strip">';
	foreach ($items as $item) echo '<span>' . esc_html($item) . '</span>';
	echo '</p>';
}

/** Linked taxonomy chips for the current post. */
function component_term_chips(array $props = []): void {
	$taxonomy = (string) ($props['taxonomy'] ?? 'product_collection');
	$post_id = (int) ($props['post_id'] ?? get_the_ID());
	$terms = get_the_terms($post_id, $taxonomy);
	if (!is_array($terms) || !$terms) return;
	echo '<div class="product-meta">';
	foreach ($terms as $term) {
		$url = get_term_link($term);
		if (!is_wp_error($url)) {
			printf('<a class="card-chip" href="%s">%s</a>', esc_url((string) $url), esc_html($term->name));
		}
	}
	echo '</div>';
}

/** Direct-link cards used by 404 and other recovery layouts. */
function component_link_cards(array $props = []): void {
	$links = (array) ($props['links'] ?? []);
	if (!$links) return;
	echo '<div class="term-grid error-grid">';
	foreach ($links as $link) {
		printf(
			'<a class="term-card" href="%s"><h2>%s</h2><p>%s</p></a>',
			esc_url((string) ($link['url'] ?? '')),
			esc_html((string) ($link['label'] ?? '')),
			esc_html((string) ($link['description'] ?? ''))
		);
	}
	echo '</div>';
}

/** Related products from the current product's terms, with catalogue fallback. */
function component_related_products(array $props = []): void {
	$post_id = (int) ($props['post_id'] ?? get_the_ID());
	$manual_ids = array_values(array_unique(array_filter(array_map('intval', (array) ($props['related_ids'] ?? [])), static fn (int $id): bool => $id > 0 && $id !== $post_id)));
	$terms = get_the_terms($post_id, 'product_collection');
	$ids = get_posts([
		'post_type' => 'starter_product',
		'posts_per_page' => 3,
		'post__not_in' => [$post_id],
		'fields' => 'ids',
		'post_status' => 'publish',
		'orderby' => $manual_ids ? 'post__in' : 'date',
		'post__in' => $manual_ids,
		'tax_query' => !$manual_ids && is_array($terms) && $terms && !is_wp_error($terms) ? [[
			'taxonomy' => 'product_collection',
			'field' => 'term_id',
			'terms' => wp_list_pluck($terms, 'term_id'),
		]] : [],
	]);
	if (!$manual_ids && count($ids) < 3) {
		$ids = array_merge($ids, get_posts([
			'post_type' => 'starter_product',
			'posts_per_page' => 3 - count($ids),
			'post__not_in' => array_merge([$post_id], $ids),
			'fields' => 'ids',
			'post_status' => 'publish',
			'orderby' => 'date',
		]));
	}
	if (!$ids) return;
	component_section_heading([
		'title' => 'Related products',
		'link' => ['url' => get_post_type_archive_link('starter_product'), 'label' => 'View all'],
	]);
	echo '<div class="grid">';
	$query = new \WP_Query(['post_type' => 'starter_product', 'post__in' => $ids, 'orderby' => 'post__in', 'posts_per_page' => 3]);
	while ($query->have_posts()) : $query->the_post();
		get_template_part('parts/card', null, ['title_tag' => 'h3', 'show_more' => false]);
	endwhile;
	wp_reset_postdata();
	echo '</div>';
}

/** Full-width conversion band used at the end of catalogue and article pages. */
function component_cta_band(array $props = []): void {
	$button = $props['button'] ?? [
		'url' => $props['url'] ?? '',
		'label' => $props['button_label'] ?? '',
	];
	$text = $props['text'] ?? $props['description'] ?? '';
	echo '<section class="cta-band"><div class="cta-layout">';
	component_section_heading([
		'eyebrow' => (string) ($props['eyebrow'] ?? ''),
		'title' => (string) ($props['title'] ?? ''),
		'description' => (string) $text,
	]);
	if (!empty($button['url']) && !empty($button['label'])) {
		printf('<a class="button" href="%s">%s</a>', esc_url((string) $button['url']), esc_html((string) $button['label']));
	}
	echo '</div></section>';
}

/** Three-column social-proof strip; empty items are omitted. */
function component_stat_strip(array $props = []): void {
	$items = array_values(array_filter((array) ($props['items'] ?? []), static fn ($item): bool => trim((string) ($item['value'] ?? '')) !== ''));
	if (!$items) return;
	echo '<section class="stats" aria-label="Key facts">';
	foreach ($items as $item) {
		echo '<div><b>' . esc_html((string) $item['value']) . '</b><span>' . esc_html((string) ($item['label'] ?? '')) . '</span></div>';
	}
	echo '</section>';
}

/** Product gallery with accessible thumbnail switching and a neutral empty state. */
function component_product_gallery(array $props = []): void {
    $images = array_values(array_filter((array) ($props['images'] ?? []), static fn ($image): bool => trim((string) ($image['url'] ?? '')) !== ''));
    $title = (string) ($props['title'] ?? get_the_title());
    if (!$images) {
        echo '<figure class="product-gallery is-placeholder">' . media_placeholder('product', $title, 'gallery-empty') . '</figure>';
        return;
    }
    $first = $images[0];
    $alt = (string) ($first['alt'] ?: $title);
    echo '<figure class="product-gallery' . (count($images) > 1 ? ' has-many' : '') . '">';
    printf('<a class="gallery-main" href="%s" aria-label="%s"><img src="%s" alt="%s" loading="eager" decoding="async"></a>', esc_url($first['full_url'] ?? $first['url']), esc_attr('View larger: ' . $alt), esc_url($first['url']), esc_attr($alt));
    if (count($images) > 1) {
        echo '<div class="gallery-thumbs" role="group" aria-label="Product image selector">';
        foreach ($images as $index => $image) {
            printf('<button type="button" class="gallery-thumb%s" data-src="%s" data-full="%s" data-alt="%s" data-caption="%s" aria-current="%s" aria-label="%s"><img src="%s" alt="" loading="lazy" decoding="async"></button>', $index === 0 ? ' is-active' : '', esc_url($image['url']), esc_url($image['full_url'] ?? $image['url']), esc_attr($image['alt'] ?: $title), esc_attr($image['caption'] ?? ''), $index === 0 ? 'true' : 'false', esc_attr(sprintf('View image %d of %d: %s', $index + 1, count($images), $image['alt'] ?: $title)), esc_url($image['thumb_url'] ?? $image['url']));
        }
        echo '</div>';
    }
    echo '<dialog class="gallery-dialog" aria-label="Product image viewer">';
    echo '<button type="button" class="gallery-close" aria-label="Close image viewer"><span aria-hidden="true">×</span></button><p class="gallery-viewer-count" aria-live="polite"></p>';
    echo '<div class="gallery-viewer' . (count($images) > 1 ? ' has-many' : '') . '"><div class="gallery-viewer-stage">';
    echo '<button type="button" class="gallery-prev" aria-label="Previous image"><span aria-hidden="true">‹</span></button><img class="gallery-viewer-image" src="' . esc_url($first['full_url'] ?? $first['url']) . '" alt="' . esc_attr($alt) . '"><button type="button" class="gallery-next" aria-label="Next image"><span aria-hidden="true">›</span></button></div>';
    if (count($images) > 1) {
        echo '<div class="gallery-viewer-thumbs" role="group" aria-label="Enlarged product image selector">';
        foreach ($images as $index => $image) {
            printf('<button type="button" class="gallery-viewer-thumb%s" data-index="%d" aria-current="%s" aria-label="%s"><img src="%s" alt="" loading="lazy" decoding="async"></button>', $index === 0 ? ' is-active' : '', $index, $index === 0 ? 'true' : 'false', esc_attr(sprintf('View image %d of %d: %s', $index + 1, count($images), $image['alt'] ?: $title)), esc_url($image['thumb_url'] ?? $image['url']));
        }
        echo '</div>';
    }
    echo '<p class="gallery-viewer-caption" hidden></p></div></dialog>';
    echo '<figcaption class="gallery-meta">';
    printf('<span class="gallery-caption"%s>%s</span>', empty($first['caption']) ? ' hidden' : '', esc_html($first['caption'] ?? ''));
    printf('<span class="gallery-count screen-reader-text" aria-live="polite">1 / %d</span></figcaption>', count($images));
    echo '</figure>';
}

/** Small shared supplier card; photograph and Business Profile facts have one owner. */
function component_product_factory(array $props = []): void {
    if (!$props) return;
    echo '<section class="product-block factory-card" aria-labelledby="product-factory-heading">';
    echo '<h2 id="product-factory-heading">Factory information</h2>';
    $image = !empty($props['image']) ? wp_get_attachment_image((int) $props['image'], 'large', false, ['loading' => 'lazy', 'decoding' => 'async']) : '';
    echo '<div class="factory-card-layout' . ($image ? ' has-photo' : '') . '">';
    if ($image) echo '<div class="factory-card-photo">' . $image . '</div>';
    echo '<div class="factory-card-copy"><div class="factory-card-identity">';
    if (!empty($props['logo'])) echo wp_get_attachment_image((int) $props['logo'], 'thumbnail', false, ['class' => 'factory-card-logo', 'loading' => 'lazy']);
    echo '<div>';
    if (!empty($props['name'])) printf('<h3>%s</h3>', esc_html($props['name']));
    if (!empty($props['location'])) printf('<p class="factory-card-location">%s</p>', esc_html($props['location']));
    echo '</div></div>';
    if (!empty($props['intro'])) printf('<p class="factory-card-intro">%s</p>', esc_html($props['intro']));
    if (!empty($props['facts'])) {
        echo '<dl class="factory-card-facts">';
        foreach ($props['facts'] as $fact) printf('<div><dt>%s</dt><dd>%s</dd></div>', esc_html($fact['value']), esc_html($fact['label']));
        echo '</dl>';
    }
    $link = $props['link'] ?? [];
    if (!empty($link['url'])) printf('<a class="text-link" href="%s"%s>%s <span aria-hidden="true">→</span></a>', esc_url($link['url']), ($link['target'] ?? '') === '_blank' ? ' target="_blank" rel="noopener"' : '', esc_html($link['title'] ?: 'Explore our factory'));
    echo '</div></div></section>';
}

/** A real Contact form instance, with a working contact fallback when unavailable. */
function component_product_inquiry(array $props = []): void {
    $form = shortcode_exists('starter_rfq_form') && shortcode_exists('fluentform') ? do_shortcode('[starter_rfq_form]') : '';
    $title = trim((string) ($props['title'] ?? 'Request a quotation'));
    echo '<aside id="product-inquiry" class="product-inquiry"' . ($title !== '' ? ' aria-labelledby="product-inquiry-heading"' : ' aria-label="Inquiry"') . '>';
    echo '<div class="product-inquiry-card form-panel">';
    if ($title !== '') echo '<h2 id="product-inquiry-heading">' . esc_html($title) . '</h2>';
    if (!empty($props['description'])) echo '<p class="inquiry-introduction">' . esc_html($props['description']) . '</p>';
    if (str_contains($form, '<form')) {
        echo $form; // Trusted Fluent Forms renderer; field and notification configuration is shared with Contact.
    } else {
        echo '<p>Tell us your quantity and requirements.</p>';
        printf('<a class="button" href="%s">Contact us</a>', esc_url((string) ($props['url'] ?? contact_url())));
    }
    if (!empty($props['note'])) printf('<p class="conversion-note">%s</p>', esc_html($props['note']));
    echo '</div></aside>';
}

/** Transposed quantity/lead-time matrix; scroll only this table on narrow screens. */
function component_product_lead_times(array $props = []): void {
    $rows = $props['rows'] ?? [];
    if (!$rows) return;
    echo '<div class="product-table-group"><h3>Lead time</h3><div class="lead-time-scroll" role="region" aria-label="Lead time by quantity" tabindex="0"><table class="lead-time-table"><tbody><tr><th scope="row">Quantity</th>';
    foreach ($rows as $row) printf('<td>%s</td>', esc_html($row['quantity']));
    echo '</tr><tr><th scope="row">Lead time</th>';
    foreach ($rows as $row) printf('<td>%s</td>', esc_html($row['time']));
    echo '</tr></tbody></table></div><p class="table-scroll-note" hidden>Scroll to see all quantities <span aria-hidden="true">↔</span></p></div>';
}

/** One editable company introduction; a coherent image/text module, not a grid of proof cards. */
/** Public product documents; optional marks never introduce a verification claim. */
function component_product_certificates(array $props = []): void {
    $rows = (array) ($props['rows'] ?? []);
    if (!$rows) return;
    echo '<div id="product-certificates" class="product-table-group product-certificates"><h3>Certificates &amp; documents</h3><ul class="certificate-grid">';
    foreach ($rows as $row) {
        $document = $row['document'];
        $image = $row['badge'] ?: ($document['image'] ?? []);
        echo '<li class="certificate-card"><span class="certificate-image" aria-hidden="true">';
        if ($image && $image['url'] !== '') {
            echo '<img src="' . esc_url($image['thumb_url']) . '" alt="" width="64" height="64" loading="lazy">';
        } else {
            echo '<svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.5" focusable="false"><path d="M8 3h11l6 6v20H8zM19 3v7h6M12 16h9M12 21h9"/></svg>';
        }
        echo '</span><div class="certificate-copy"><div class="certificate-title"><h4>' . esc_html($row['name']) . '</h4>';
        if ($document) echo '<span class="certificate-format" aria-hidden="true">' . esc_html($document['format']) . '</span>';
        echo '</div>';
        if ($row['description'] !== '') echo '<p>' . esc_html($row['description']) . '</p>';
        echo '</div>';
        if ($document) {
            $label = $document['is_pdf'] ? 'View PDF' : 'View image';
            echo '<a class="certificate-link" href="' . esc_url($document['url']) . '" target="_blank" rel="noopener" aria-label="' . esc_attr($label . ': ' . $row['name'] . ' (opens in a new tab)') . '">' . esc_html($label) . ' <span aria-hidden="true">↗</span></a>';
        }
        echo '</li>';
    }
    echo '</ul></div>';
}

function component_company_intro(array $props = []): void {
    $body = (string) ($props['body'] ?? '');
    if (trim($body) === '') return;
    $image = (int) ($props['image'] ?? 0);
    echo '<section class="section company-intro' . ($image ? ' has-media' : '') . '">';
    if ($image) echo '<div class="company-photo">' . wp_get_attachment_image($image, 'large', false, ['loading' => 'lazy']) . '</div>';
    echo '<div class="company-copy">';
    if (!empty($props['title'])) echo '<h2>' . esc_html($props['title']) . '</h2>';
    echo '<div class="prose">' . $body . '</div>'; // Escaped and formatted ACF WYSIWYG.
    $link = $props['link'] ?? [];
    if (!empty($link['url']) && !empty($link['title'])) printf('<a class="text-link" href="%s">%s <span aria-hidden="true">→</span></a>', esc_url($link['url']), esc_html($link['title']));
    echo '</div></section>';
}

/** Compact product hero summary: identity and conversion only. */
function component_product_hero_summary(array $props = []): void {
	$category = (array) ($props['category'] ?? []);
	$title = (string) ($props['title'] ?? get_the_title());
	$description = (string) ($props['description'] ?? '');
	$chips = (array) ($props['value_chips'] ?? []);
	$cta = (array) ($props['cta'] ?? []);
	echo '<div class="product-summary">';
	if (!empty($category['url'])) {
		printf('<a class="card-chip product-category" href="%s">%s</a>', esc_url((string) $category['url']), esc_html((string) $category['name']));
	} elseif (!empty($category['name'])) {
		printf('<span class="card-chip product-category">%s</span>', esc_html((string) $category['name']));
	}
	printf('<h1>%s</h1>', esc_html($title));
	if ($description !== '') echo '<div class="prose product-intro"><p>' . esc_html($description) . '</p></div>';
	component_quick_specs(['rows' => $chips, 'class' => 'product-value-chips']);
	if (!empty($cta['url'])) {
		echo '<div class="hero-conversion">';
		printf('<a class="button button-large" href="%s">%s</a>', esc_url((string) $cta['url']), esc_html((string) ($cta['label'] ?? 'Request pricing')));
		if (!empty($cta['note'])) printf('<p class="conversion-note">%s</p>', esc_html((string) $cta['note']));
		echo '</div>';
	}
	echo '</div>';
}

/** Constrained wrapper for ACF WYSIWYG or legacy WordPress editor content. */
function component_rich_description(array $props = []): void {
	$content = trim(\AxisCNC\SuiteTheme\without_ai_notices((string) ($props['content'] ?? '')));
	if ($content === '') return;
	echo '<section class="product-details">';
	component_section_heading([
		'eyebrow' => (string) ($props['eyebrow'] ?? 'Details'),
		'title' => (string) ($props['title'] ?? 'Product details'),
	]);
	echo '<article class="prose rich-description">' . $content . '</article>'; // phpcs:ignore WordPress.Security.EscapeOutput -- ACF WYSIWYG and the_content() provide formatted editor HTML.
	echo '</section>';
}

/** Rich product-category hero with optional image, key facts and dual actions. */
function component_category_hero(array $props = []): void {
	$name = (string) ($props['name'] ?? '');
	if ($name === '') return;
	$overline = (string) ($props['overline'] ?? 'Product range');
	$intro = (string) ($props['intro'] ?? '');
	$image = (array) ($props['image'] ?? []);
	$facts = array_slice((array) ($props['facts'] ?? []), 0, 4);
	$cta = (array) ($props['cta'] ?? []);
	$all_url = (string) ($props['all_url'] ?? '');

	echo '<section class="category-hero' . (empty($image['url']) ? ' no-media' : ' has-media') . '"><div class="category-hero-grid">';
	echo '<div class="category-copy">';
	if ($overline !== '') echo '<p class="eyebrow">' . esc_html($overline) . '</p>';
	echo '<h1>' . esc_html($name) . '</h1>';
	if ($intro !== '') echo '<div class="prose category-intro"><p>' . esc_html($intro) . '</p></div>';
	if ($facts) {
		echo '<dl class="category-facts">';
		foreach ($facts as $fact) printf('<div><dt>%s</dt><dd>%s</dd></div>', esc_html((string) ($fact['label'] ?? '')), esc_html((string) ($fact['value'] ?? '')));
		echo '</dl>';
	}
	echo '<div class="category-actions">';
	if (!empty($cta['url'])) printf('<a class="button button-large" href="%s">%s</a>', esc_url((string) $cta['url']), esc_html((string) ($cta['button_label'] ?? 'Request a quotation')));
	if ($all_url !== '') printf('<a class="ghost-button" href="%s">%s</a>', esc_url($all_url), esc_html($all_url === '#products' ? 'Browse products' : 'Selection guide'));
	echo '</div>';
	echo '</div>';
    if (!empty($image['url'])) {
        echo '<div class="category-media">';
        $alt = (string) (($image['alt'] ?? '') ?: $name);
        if (!empty($image['id'])) echo wp_get_attachment_image((int) $image['id'], 'large', false, ['alt' => $alt, 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async']);
        else printf('<img src="%s" alt="%s" loading="eager" decoding="async">', esc_url((string) $image['url']), esc_attr($alt));
        echo '</div>';
    }
    echo '</div></section>';
}

/** Reordered category modules have no wrapper when no valid rows exist. */
function component_category_sections(array $sections): void {
    if (!$sections) return;
    echo '<div class="category-content-sections">';
    foreach ($sections as $index => $section) {
        $media = $section['image'] ? wp_get_attachment_image($section['image'], 'large', false, ['loading' => 'lazy', 'decoding' => 'async']) : '';
        $video = $section['video'] ?? [];
        $heading_id = 'category-section-heading-' . ($index + 1);
        if ($video) {
            $poster = $video['poster'] !== '' ? ' poster="' . esc_url($video['poster']) . '"' : '';
            $media = sprintf('<video controls playsinline preload="metadata" aria-labelledby="%s"%s><source src="%s" type="%s"><a href="%s">%s</a></video>', esc_attr($heading_id), $poster, esc_url($video['url']), esc_attr($video['mime']), esc_url($video['url']), esc_html__('Open video', 'b2b-starter'));
        }
        printf('<section class="category-content-section image-%s%s" aria-labelledby="%s"><div class="category-section-copy"><h2 id="%s">%s</h2>', esc_attr($section['position']), $media ? ' has-media' : ' no-media', esc_attr($heading_id), esc_attr($heading_id), esc_html($section['title']));
        $factory = $section['factory'];
        if (!empty($factory['name'])) printf('<h3>%s</h3>', esc_html($factory['name']));
        if (!empty($factory['location'])) printf('<p>%s</p>', esc_html($factory['location']));
        if ($section['body'] !== '') echo '<div class="prose">' . $section['body'] . '</div>'; // Escaped/formatted by the data adapter.
        if (!empty($factory['facts'])) {
            echo '<dl class="category-section-facts">';
            foreach ($factory['facts'] as $fact) printf('<div><dt>%s</dt><dd>%s</dd></div>', esc_html($fact['value']), esc_html($fact['label']));
            echo '</dl>';
        }
        $link = $section['link'];
        if (!empty($link['url']) && esc_url($link['url']) !== '' && !empty($link['title'])) printf('<a class="text-link" href="%s"%s>%s <span aria-hidden="true">→</span></a>', esc_url($link['url']), ($link['target'] ?? '') === '_blank' ? ' target="_blank" rel="noopener"' : '', esc_html($link['title']));
        echo '</div>';
        if ($media) echo '<div class="category-section-media">' . $media . '</div>';
        echo '</section>';
    }
    echo '</div>';
}

/** Section anchor navigation for long commercial landing pages. */
function component_anchor_nav(array $props = []): void {
	// Commercial pages no longer render section shortcut navigation.
}

/** Compact label/value fact grid used by category ranges. */
function component_fact_grid(array $props = []): void {
	$rows = array_values(array_filter((array) ($props['rows'] ?? []), static fn ($row): bool => trim((string) ($row['label'] ?? '')) !== ''));
	if (!$rows) return;
	echo '<dl class="fact-grid">';
	foreach ($rows as $row) printf('<div><dt>%s</dt><dd>%s</dd></div>', esc_html((string) $row['label']), esc_html((string) ($row['value'] ?? '')));
	echo '</dl>';
}

/** Numbered category process steps. */
function component_process_steps(array $props = []): void {
	$rows = array_values(array_filter((array) ($props['rows'] ?? []), static fn ($row): bool => trim((string) ($row['label'] ?? '')) !== ''));
	if (!$rows) return;
	echo '<ol class="process-grid">';
	foreach ($rows as $index => $row) printf('<li><span>%02d</span><strong class="process-title">%s</strong><p>%s</p></li>', $index + 1, esc_html((string) $row['label']), esc_html((string) ($row['value'] ?? '')));
	echo '</ol>';
}

/** Checklist with an accessible check icon. */
function component_check_list(array $props = []): void {
	$items = array_values(array_filter(array_map('trim', (array) ($props['items'] ?? []))));
	if (!$items) return;
	echo '<ul class="check-list">';
	foreach ($items as $item) echo '<li>' . esc_html($item) . '</li>';
	echo '</ul>';
}

/** Resource cards; an optional second line segment becomes a link. */
function component_resource_list(array $props = []): void {
	$rows = array_values(array_filter((array) ($props['resources'] ?? []), static fn ($row): bool => trim((string) ($row['label'] ?? '')) !== ''));
	if (!$rows) return;
	echo '<div class="resource-grid">';
	foreach ($rows as $row) {
		echo '<div class="resource-card">';
		if (!empty($row['is_link'])) {
			printf('<a href="%s">%s<span aria-hidden="true">→</span></a>', esc_url((string) $row['url']), esc_html((string) $row['label']));
		} else {
			printf('<p>%s</p>', esc_html((string) $row['label']));
		}
		echo '</div>';
	}
	echo '</div>';
}

/** Related category tiles derived from sibling product categories. */
function component_related_category_tiles(array $props = []): void {
	$terms = array_values(array_filter((array) ($props['terms'] ?? [])));
	if (!$terms) return;
	echo '<div class="related-category-grid">';
	foreach ($terms as $term) {
		printf(
			'<a class="related-category" href="%s"><strong class="related-category-title">%s</strong><p>%s</p><span>Browse range →</span></a>',
			esc_url((string) $term['url']),
			esc_html((string) $term['name']),
			esc_html((string) $term['description'])
		);
	}
	echo '</div>';
}

/** Category editorial wrapper for ACF WYSIWYG content. */
function component_category_editorial(array $props = []): void {
	$content = trim((string) ($props['content'] ?? ''));
	if ($content === '') return;
	component_section_heading([
		'eyebrow' => (string) ($props['eyebrow'] ?? 'Buying guide'),
		'title' => (string) ($props['title'] ?? 'Category guide'),
	]);
	printf('<article class="prose category-editorial">%s</article>', $content); // Already escaped before ACF formatting; retain generated media embeds.
}

/** Editorial blog archive with a featured lead article and compact post cards. */
function component_blog_archive(array $props = []): void {
	$query = ($props['query'] ?? null) instanceof \WP_Query ? $props['query'] : ($GLOBALS['wp_query'] ?? null);
	if (!$query || !$query->have_posts()) {
		echo '<p class="empty-state">' . esc_html((string) ($props['empty_text'] ?? 'No articles yet.')) . '</p>';
		return;
	}

	$featured = !is_paged();
	$index = 0;
	echo '<div class="blog-grid">';
	while ($query->have_posts()) : $query->the_post();
		get_template_part('parts/blog-card', null, [
			'featured' => $featured && 0 === $index,
		]);
		$index++;
	endwhile;
	echo '</div>';
	wp_reset_postdata();
}

/** Related article cards. */
function component_related_blog_cards(array $props = []): void {
	$posts = array_values(array_filter((array) ($props['posts'] ?? [])));
	if (!$posts) return;
	echo '<div class="blog-grid blog-grid-compact">';
	foreach ($posts as $related_post) {
		$GLOBALS['post'] = $related_post;
		setup_postdata($related_post);
		get_template_part('parts/blog-card', null, ['show_meta' => false, 'show_more' => false]);
	}
	wp_reset_postdata();
	echo '</div>';
}

/** Compact factory proof strip for core conversion pages. */
function component_factory_strip(array $props = []): void {
	$proof = array_values(array_filter((array) ($props['proof'] ?? []), static fn ($row): bool => trim((string) ($row['value'] ?? '')) !== ''));
	$certifications = array_values(array_filter(array_map('trim', (array) ($props['certifications'] ?? []))));
	$markets = (string) ($props['markets'] ?? '');
	if (!$proof && !$certifications && $markets === '') return;

	echo '<section class="factory-strip" aria-label="Factory and export proof"><div class="factory-strip-grid">';
	foreach ($proof as $row) {
		printf('<div><strong>%s</strong><span>%s</span></div>', esc_html((string) $row['value']), esc_html((string) ($row['label'] ?? '')));
	}
	if ($markets !== '') {
		echo '<div><strong>Export markets</strong><span>' . esc_html($markets) . '</span></div>';
	}
	echo '</div>';
	if ($certifications) {
		echo '<div class="factory-certifications">';
		foreach ($certifications as $item) echo '<span>' . esc_html((string) $item) . '</span>';
		echo '</div>';
	}
	echo '</section>';
}

/** Manufacturing and quality-control module for core templates. */
function component_factory_capability(array $props = []): void {
	$intro = (string) ($props['intro'] ?? '');
	$capabilities = (array) ($props['capabilities'] ?? []);
	$process = (array) ($props['process'] ?? []);
	$quality_tests = (array) ($props['quality_tests'] ?? []);
	$certifications = (array) ($props['certifications'] ?? []);
	if (!$intro && !$capabilities && !$process && !$quality_tests && !$certifications) return;

	echo '<section class="section factory-capability" aria-label="Manufacturing and quality control">';
	component_section_heading([
		'eyebrow' => (string) ($props['eyebrow'] ?? 'Manufacturing'),
		'title' => (string) ($props['title'] ?? 'Factory and quality control'),
		'description' => $intro,
	]);
	if ($capabilities) component_feature_grid(['rows' => $capabilities, 'class' => 'capability-grid']);
	if ($process) {
		echo '<p class="sub-heading">Production process</p>';
		component_process_steps(['rows' => $process]);
	}
	if ($quality_tests || $certifications) {
		echo '<div class="quality-grid">';
		if ($quality_tests) {
			echo '<div class="quality-panel"><p class="panel-title">Quality checks</p>';
			component_check_list(['items' => $quality_tests]);
			echo '</div>';
		}
		if ($certifications) {
			echo '<div class="quality-panel"><p class="panel-title">Standards &amp; certifications</p><ul class="app-list">';
			foreach ($certifications as $item) echo '<li>' . esc_html((string) $item) . '</li>';
			echo '</ul></div>';
		}
		echo '</div>';
	}
	echo '</section>';
}

