<?php
/** Shared details and inquiry for every native product template. */
$product = $args['product'];
$simple = ($args['layout'] ?? 'standard') === 'simple';
$specifications = array_values(array_filter($product['specifications'], static fn (array $row): bool => trim($row['label']) !== '' && trim($row['value']) !== ''));
$has_attributes = !$simple && ($specifications || $product['attribute_tables'] || $product['packaging_tables'] || $product['lead_times'] || $product['certificates']);
$has_description = $product['details']['has_content'] || (!$simple && $product['faq']);
$links = [];
if (!$simple && $product['services']) $links[] = ['label' => 'Services', 'url' => '#product-services'];
if ($has_attributes) $links[] = ['label' => 'Attributes', 'url' => '#product-attributes'];
if ($has_description) $links[] = ['label' => 'Description', 'url' => '#product-details'];
?>
<div class="product-reading-layout">
<div class="product-reading">
<?php if ($links) : ?>
<nav class="product-section-nav" aria-label="Product sections"><div class="anchor-track">
<?php foreach ($links as $link) : ?><a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a><?php endforeach; ?>
</div></nav>
<?php endif; ?>
<?php if (!$simple && $product['services']) : ?>
<section id="product-services" class="product-block"><h2>Services</h2>
<?php Starter\Theme\component_faq(['rows' => $product['services']]); ?>
</section>
<?php endif; ?>
<?php if ($has_attributes) : ?>
<section id="product-attributes" class="product-block"><h2>Attributes</h2>
<?php if ($specifications) : ?>
<div id="specifications" class="product-table-group"><h3>Product specifications</h3>
<?php Starter\Theme\component_spec_table(['rows' => $specifications]); ?>
</div>
<?php endif; ?>
<?php foreach (array_merge($product['attribute_tables'], $product['packaging_tables']) as $group) : ?>
<div class="product-table-group"><h3><?php echo esc_html($group['title']); ?></h3>
<?php Starter\Theme\component_spec_table(['rows' => $group['rows']]); ?>
</div>
<?php endforeach; ?>
<?php Starter\Theme\component_product_lead_times(['rows' => $product['lead_times']]); ?>
<?php Starter\Theme\component_product_certificates(['rows' => $product['certificates']]); ?>
</section>
<?php endif; ?>
<?php if (!$simple) Starter\Theme\component_product_factory($product['factory_card']); ?>
<?php if ($has_description) : ?>
<div id="product-details" class="product-block product-description-block">
<?php if ($product['details']['has_content']) Starter\Theme\component_rich_description(['title' => $product['details']['title'], 'content' => $product['details']['content'], 'eyebrow' => '']); ?>
<?php if (!$simple && $product['faq']) : ?>
<section id="product-questions" class="product-questions">
<?php Starter\Theme\component_section_heading(['title' => 'FAQs']); ?>
<?php Starter\Theme\component_faq(['rows' => $product['faq']]); ?>
</section>
<?php endif; ?>
</div>
<?php endif; ?>
</div>
<?php Starter\Theme\component_product_inquiry(['title' => $product['title'], 'url' => $product['cta']['url'], 'note' => $product['cta']['note']]); ?>
</div>
<?php if (!$simple) : ?>
<section class="section related-section"><?php Starter\Theme\component_related_products(['post_id' => get_the_ID(), 'related_ids' => $product['related_ids']]); ?></section>
<?php endif; ?>

