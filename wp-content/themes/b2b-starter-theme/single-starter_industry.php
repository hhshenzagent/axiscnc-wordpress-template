<?php
/** Industry application detail: editable modules and the existing shared inquiry. */
get_header(); ?>
<main id="main" class="industry-single"><?php while (have_posts()) : the_post();
$industry = Starter\Theme\industry_data();
$body = Starter\Theme\module_body('industry_body');
$has_body = Starter\Theme\has_rich_content($body);
$has_applications = (bool) ($industry['modules'] || $has_body);
$has_planning = (bool) ($industry['steps'] || $industry['planning_rows'] || $industry['outcome'] !== '');
?>
<div class="shell"><?php Starter\Theme\breadcrumbs(); ?></div>
<section id="industry-overview" class="shell industry-hero<?php echo has_post_thumbnail() ? '' : ' without-media'; ?>">
<div class="industry-hero-copy">
<?php Starter\Theme\component_page_head(['eyebrow' => 'Industry solution', 'title' => get_the_title(), 'description' => get_the_excerpt(), 'breadcrumbs' => false]); ?>
<div class="industry-hero-actions">
<?php if ($industry['cta']['button_label'] !== '') : ?><a class="button" href="<?php echo esc_url($industry['show_inquiry'] ? '#product-inquiry' : $industry['cta']['url']); ?>"><?php echo esc_html($industry['cta']['button_label']); ?><span aria-hidden="true">→</span></a><?php endif; ?>
<?php if ($industry['product_ids']) : ?><a class="ghost-button" href="#application-products">Explore equipment</a><?php endif; ?>
</div></div>
<?php if (has_post_thumbnail()) : ?><div class="industry-hero-media"><?php the_post_thumbnail('full', ['class' => 'industry-photo', 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 900px) calc(100vw - 40px), 640px']); ?></div><?php endif; ?>
</section>
<?php if ($industry['constraints'] || $industry['challenge'] !== '') : ?>
<section class="industry-constraints industry-surface"><div class="shell">
<?php if ($industry['constraints_title'] !== '') : ?><h2><?php echo esc_html($industry['constraints_title']); ?></h2><?php endif; ?>
<?php if ($industry['challenge'] !== '') : ?><p class="industry-section-intro"><?php echo esc_html($industry['challenge']); ?></p><?php endif; ?>
<?php if ($industry['constraints']) : ?><div class="industry-constraint-grid">
<?php foreach ($industry['constraints'] as $index => $row) : ?><div class="industry-constraint">
<svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.3" aria-hidden="true" focusable="false"><?php if ($index % 3 === 0) : ?><path d="M5 27V7l5-3v23M22 27V4l5 3v20M10 10h12M10 20h12M3 27h26"/><?php elseif ($index % 3 === 1) : ?><path d="M4 25h24M6 25v-7h20v7M9 18v-6h14v6M12 12V6h8v6M3 28h26"/><?php else : ?><path d="M6 26h22L18 5l-4 3 4 13H8zM14 8l-5 7M9 15l-3 11"/><?php endif; ?></svg>
<h3><?php echo esc_html($row['label']); ?></h3><p><?php echo esc_html($row['value']); ?></p>
</div><?php endforeach; ?></div><?php endif; ?>
</div></section><?php endif; ?>
<?php if ($has_applications) : ?><section id="industry-applications" class="shell industry-applications industry-section">
<?php if ($industry['applications_title'] !== '') : ?><h2><?php echo esc_html($industry['applications_title']); ?></h2><?php endif; ?>
<?php Starter\Theme\component_category_sections($industry['modules']); ?>
<?php if ($has_body) : ?><article class="prose industry-additional-content"><?php echo $body; // Formatted by the canonical rich-text adapter. ?></article><?php endif; ?>
</section><?php endif; ?>
<?php if ($industry['product_ids']) : ?><section class="shell industry-section industry-products product-columns-<?php echo min(4, count($industry['product_ids'])); ?><?php echo count($industry['product_ids']) === 1 ? ' has-single-product' : ''; ?>" id="application-products">
<?php Starter\Theme\component_section_heading(['title' => $industry['products_title'], 'description' => $industry['products_intro'], 'link' => ['url' => get_post_type_archive_link('starter_product'), 'label' => 'All products']]); ?>
<?php Starter\Theme\component_card_grid(['query_args' => ['post_type' => 'starter_product', 'post_status' => 'publish', 'post__in' => $industry['product_ids'], 'orderby' => 'post__in', 'posts_per_page' => count($industry['product_ids'])], 'title_tag' => 'h3', 'show_specs' => true]); ?>
</section><?php endif; ?>
<?php if ($has_planning) : ?><section id="industry-planning" class="industry-section industry-surface"><div class="shell industry-planning<?php echo $industry['planning_rows'] && ($industry['steps'] || $industry['outcome'] !== '') ? ' has-two-columns' : ''; ?>">
<div class="industry-planning-copy">
<?php if ($industry['planning_title'] !== '') : ?><h2><?php echo esc_html($industry['planning_title']); ?></h2><?php endif; ?>
<?php if ($industry['planning_intro'] !== '') : ?><p class="industry-section-intro"><?php echo esc_html($industry['planning_intro']); ?></p><?php endif; ?>
<?php if ($industry['steps']) : ?><ol class="industry-steps"><?php foreach ($industry['steps'] as $row) : ?><li><h3><?php echo esc_html($row['label']); ?></h3><p><?php echo esc_html($row['value']); ?></p></li><?php endforeach; ?></ol><?php endif; ?>
<?php if ($industry['outcome'] !== '') : ?><p class="industry-outcome"><?php echo esc_html($industry['outcome']); ?></p><?php endif; ?>
</div>
<?php if ($industry['planning_rows']) : ?><div class="industry-planning-table"><div class="industry-table-scroll" role="region" aria-label="Application planning information" tabindex="0"><table><thead><tr><th scope="col">Planning item</th><th scope="col">Information to share</th></tr></thead><tbody>
<?php foreach ($industry['planning_rows'] as $row) : ?><tr><th scope="row"><?php echo esc_html($row['label']); ?></th><td><?php echo esc_html($row['value']); ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php if ($industry['note'] !== '') : ?><p class="industry-note"><?php echo esc_html($industry['note']); ?></p><?php endif; ?></div><?php endif; ?>
</div></section><?php endif; ?>
<?php if ($industry['faq'] || $industry['show_inquiry']) : ?><section class="shell industry-section industry-conversion<?php echo $industry['faq'] && $industry['show_inquiry'] ? ' has-two-columns' : ''; ?>">
<?php if ($industry['faq']) : ?><div id="industry-questions" class="industry-questions"><?php if ($industry['faq_title'] !== '') : ?><h2><?php echo esc_html($industry['faq_title']); ?></h2><?php endif; ?><?php Starter\Theme\component_faq(['rows' => $industry['faq']]); ?></div><?php endif; ?>
<?php if ($industry['show_inquiry']) Starter\Theme\component_product_inquiry(['title' => $industry['cta']['title'], 'description' => $industry['cta']['description'], 'url' => $industry['cta']['url']]); ?>
</section><?php endif; ?>
<?php endwhile; ?></main>
<?php get_footer(); ?>

