<?php
/** Native WordPress article: editorial hero, contents, reading column and RFQ. */
namespace AxisCNC\SuiteTheme;
defined('ABSPATH') || exit;

function article_reading_content(string $html, int $post_id): array {
    $items = [];
    $used = [];
    preg_match_all('/\bid\s*=\s*(["\'])(.*?)\1/is', $html, $existing_ids);
    $reserved = array_fill_keys($existing_ids[2], true);
    $index = 0;
    $html = preg_replace_callback('/<h2\b([^>]*)>(.*?)<\/h2>/is', static function ($match) use (&$items, &$used, &$reserved, &$index, $post_id) {
        $label = trim(html_entity_decode(wp_strip_all_tags($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($label === '') return $match[0];
        $index++;
        $attrs = $match[1];
        $has_id = preg_match('/\bid\s*=\s*(["\'])(.*?)\1/is', $attrs, $id_match);
        $id = $has_id ? $id_match[2] : '';
        if ($id === '' || isset($used[$id])) {
            $id = 'axis-article-' . $post_id . '-section-' . $index;
            while (isset($used[$id]) || isset($reserved[$id])) $id .= '-section';
            $attrs = preg_replace('/\s*\bid\s*=\s*(["\']).*?\1/is', '', $attrs);
            $attrs .= ' id="' . esc_attr($id) . '"';
        }
        $used[$id] = true;
        $reserved[$id] = true;
        $items[] = ['id' => $id, 'title' => $label];
        return '<h2' . $attrs . '>' . $match[2] . '</h2>';
    }, $html);
    return ['html' => $html, 'items' => $items];
}

function article_reading_share(): void {
    $url = rawurlencode(get_permalink());
    $title = rawurlencode(get_the_title());
    echo '<div class="axis-reading-share"><span>Share</span><div class="axis-reading-share-links">';
    foreach ([
        ['LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url, 'in'],
        ['X', 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title, 'X'],
        ['Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $url, 'f'],
    ] as [$label, $href, $glyph]) {
        echo '<a href="' . esc_url($href) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr('Share on ' . $label) . '">' . esc_html($glyph) . '</a>';
    }
    echo '</div><div class="axis-reading-utilities"><button type="button" data-article-copy>Copy link</button><button type="button" data-article-print>Print</button></div><span class="visually-hidden" role="status" data-article-status></span></div>';
}

function render_article_reading(): void {
    the_post();
    $id = get_the_ID();
    $content = article_reading_content(apply_filters('the_content', get_the_content()), $id);
    $toc = count($content['items']) >= 2;
    $plain = wp_strip_all_tags($content['html']);
    $length = preg_match_all('/[\p{L}\p{N}]+(?:[\x{2019}\x{0027}-][\p{L}\p{N}]+)*/u', $plain);
    $minutes = max(1, (int) ceil($length / 220));
    $form = (int) value('article_form');
    $cta = value('article_cta', []);
    $has_cta = is_array($cta) && !empty($cta['url']) && !empty($cta['title']);
    $rfq = $form > 0 || $has_cta;
    $rfq_title = $has_cta ? $cta['title'] : 'Ready to start your project?';
    $side_image = (int) value('article_sidebar_image');
    $author_id = (int) get_the_author_meta('ID');
    $author_name = get_the_author();
    $author_url = $author_id > 0 ? get_author_posts_url($author_id) : '';
    start();
    echo '<div class="axis-reading-page"><section class="axis-reading-hero"><div class="axis-reading-container">';
    echo '<nav class="axis-reading-breadcrumb" aria-label="Breadcrumb"><a href="' . esc_url(home_url('/')) . '">Home</a><span aria-hidden="true">›</span><a href="' . esc_url(home_url('/blog/')) . '">Blog</a><span aria-hidden="true">›</span><span>' . esc_html(get_the_title()) . '</span></nav>';
    echo '<div class="axis-reading-heading' . (!has_post_thumbnail() ? ' is-text-only' : '') . '"><div><h1>' . esc_html(get_the_title()) . '</h1><div class="axis-reading-meta">';
    if ($author_name !== '') {
        preg_match('/^./us', $author_name, $initial);
        echo '<div class="axis-reading-byline"><span class="axis-reading-author-initial" aria-hidden="true">' . esc_html(strtoupper($initial[0] ?? '')) . '</span><div>';
        if ($author_url) echo '<a rel="author" href="' . esc_url($author_url) . '">' . esc_html($author_name) . '</a>';
        else echo '<span>' . esc_html($author_name) . '</span>';
        if ($length) echo '<span class="axis-reading-duration">About ' . $minutes . ' min read</span>';
        echo '</div></div>';
    }
    echo '<div class="axis-reading-dates"><span><strong>Published:</strong> <time datetime="' . esc_attr(get_the_date('c')) . '">' . esc_html(get_the_date('F j, Y')) . '</time></span>';
    if ((int) get_post_modified_time('U') - (int) get_post_time('U') >= DAY_IN_SECONDS) echo '<span><strong>Updated:</strong> <time datetime="' . esc_attr(get_the_modified_date('c')) . '">' . esc_html(get_the_modified_date('F j, Y')) . '</time></span>';
    echo '</div></div></div>';
    if (has_post_thumbnail()) {
        echo '<figure class="axis-reading-cover">'; image(get_post_thumbnail_id(), 'large');
        if (has_text(public_caption(value('article_caption')))) echo '<figcaption>' . esc_html(value('article_caption')) . '</figcaption>';
        echo '</figure>';
    }
    echo '</div></div></section><div class="axis-reading-container axis-reading-grid' . (!$toc ? ' without-contents' : '') . (!$rfq ? ' without-rfq' : '') . '">';
    if ($toc) {
        echo '<aside class="axis-reading-left" aria-label="Article navigation">'; article_reading_share();
        echo '<nav class="axis-reading-toc" aria-label="Table of contents"><button type="button" class="axis-reading-toc-toggle" aria-expanded="true" aria-controls="axis-reading-contents"><span>Table of Contents</span><span aria-hidden="true">⌃</span></button><ul id="axis-reading-contents">';
        foreach ($content['items'] as $item) echo '<li><a href="#' . esc_attr($item['id']) . '">' . esc_html($item['title']) . '</a></li>';
        echo '</ul></nav></aside>';
    }
    echo '<article class="axis-reading-body" data-reading-body>';
    if (!$toc) article_reading_share();
    if (has_text(value('article_deck'))) echo '<p class="axis-reading-lead">' . esc_html(value('article_deck')) . '</p>';
    echo $content['html']; // Native WordPress content filters already ran above.
    $bio = trim((string) get_the_author_meta('description'));
    if ($bio !== '') echo '<section class="axis-reading-author"><p>Written by</p><h2>' . esc_html($author_name) . '</h2><p>' . esc_html($bio) . '</p></section>';
    echo '</article>';
    if ($rfq) {
        echo '<aside class="axis-reading-right" aria-label="Project inquiry"><div class="axis-reading-quote"><p class="axis-reading-quote-brand">' . esc_html(get_bloginfo('name')) . '</p>';
        if (get_bloginfo('description')) echo '<p class="axis-reading-quote-tagline">' . esc_html(get_bloginfo('description')) . '</p>';
        if (has_image($side_image)) {
            echo '<figure class="axis-reading-quote-image">'; image($side_image, 'medium_large');
            if (has_text(public_caption(value('article_sidebar_caption')))) echo '<figcaption>' . esc_html(value('article_sidebar_caption')) . '</figcaption>';
            echo '</figure>';
        }
        echo '<h2>' . esc_html($rfq_title) . '</h2><p class="axis-reading-quote-copy">Bring your drawing and the requirements you want to review.</p><ul class="axis-reading-inputs"><li>Drawing</li><li>Material</li><li>Quantity</li></ul>';
        if ($form > 0) echo '<button type="button" class="button axis-reading-quote-button" data-reading-inquiry aria-haspopup="dialog" aria-controls="axis-reading-inquiry">Request a quote <span aria-hidden="true">→</span></button>';
        else link($cta);
        echo '</div></aside>';
    }
    echo '</div>';
    if ($form > 0) {
        echo '<dialog id="axis-reading-inquiry" class="axis-reading-dialog" aria-labelledby="axis-reading-inquiry-title"><div class="axis-reading-dialog-heading"><div><p class="eyebrow">PROJECT INQUIRY</p><h2 id="axis-reading-inquiry-title">' . esc_html($rfq_title) . '</h2></div><button type="button" data-reading-close aria-label="Close inquiry">×</button></div><p>Send your drawing, material, quantity and the question you need to resolve.</p><div class="axis-reading-form suite-native-form" data-axis-context="' . esc_attr(get_the_title()) . '">' . do_shortcode('[fluentform id="' . $form . '"]') . '</div></dialog>';
    }
    $ids = (array) value('article_related', []);
    if (!$ids) $ids = get_posts(['post_type' => 'post', 'post_status' => 'publish', 'fields' => 'ids', 'numberposts' => 3, 'post__not_in' => [$id], 'category__in' => wp_get_post_categories($id)]);
    $ids = array_values(array_filter(array_unique(array_map('intval', $ids)), static fn($rid) => $rid !== $id && get_post_type($rid) === 'post' && get_post_status($rid) === 'publish'));
    if ($ids) {
        echo '<section class="suite-section axis-reading-container axis-reading-related"><h2>Recent articles</h2><div class="suite-blog-grid">';
        foreach ($ids as $rid) article_card(get_post($rid));
        echo '</div></section>';
    }
    echo '</div>'; end();
}
