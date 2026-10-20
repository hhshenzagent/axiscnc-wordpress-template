<?php
/** Default category: range, products, editable image/text modules and a complete guide. */
$context = get_query_var('category_template_context');
$product_query = $context['product_query'] ?? $GLOBALS['wp_query'];
$category = $context['category'] ?? Starter\Theme\product_category_data();
$has_attributes = $category['specifications'] || $category['attribute_tables'];
$links = [];
if ($category['long_description']) $links[] = ['label' => 'Guide', 'url' => '#buying-guide'];
if ($has_attributes) $links[] = ['label' => 'Attributes', 'url' => '#category-attributes'];
if ($category['faq']) $links[] = ['label' => 'FAQ', 'url' => '#faq'];
$has_guide = !is_paged() && $links;
$product_count = (int) ($product_query->found_posts ?? 0);
$product_columns = min(4, max(1, $product_count));
$hero_cta = array_merge($category['cta'], ['url' => '#product-inquiry']);
?>
<main id="main" class="shell category-page category-standard product-page">
<?php Starter\Theme\component_category_hero(['name' => $category['name'], 'overline' => '', 'intro' => $category['intro'], 'image' => $category['hero_image'], 'cta' => $hero_cta, 'all_url' => $category['show_products'] ? '#products' : '']); ?>
<?php if ($category['show_products']) : ?>
<section id="products" class="section category-products product-columns-<?php echo esc_attr((string) $product_columns); ?><?php echo $product_count === 1 ? ' has-single-product' : ''; ?>">
<?php Starter\Theme\component_section_heading(['title' => 'Products in this range']); ?>
<?php Starter\Theme\component_card_grid(['query' => $product_query, 'main_query' => true, 'pagination' => true, 'title_tag' => 'h3', 'show_specs' => true, 'empty_text' => 'No products in this range yet. Browse another range or send us your requirements.']); ?>
</section>
<?php endif; ?>
<?php Starter\Theme\component_category_sections($category['content_sections']); ?>
<div class="product-reading-layout<?php echo !$has_guide ? ' no-guide' : ''; ?>">
<?php if ($has_guide) : ?>
<div class="product-reading">
<nav class="product-section-nav" aria-label="Category information"><div class="anchor-track">
<?php foreach ($links as $link) : ?><a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a><?php endforeach; ?>
</div></nav>
<?php if ($category['long_description']) : ?>
<section id="buying-guide" class="product-block buying-guide">
<?php Starter\Theme\component_section_heading(['title' => $category['guide_title']]); ?>
<article class="prose category-editorial"><?php echo $category['long_description']; // Formatted and escaped ACF WYSIWYG. ?></article>
</section>
<?php endif; ?>
<?php if ($has_attributes) : ?>
<section id="category-attributes" class="product-block"><h2>Attributes</h2>
<?php if ($category['specifications']) : ?><div id="specifications" class="product-table-group"><h3>Category specifications</h3><?php Starter\Theme\component_spec_table(['rows' => $category['specifications']]); ?></div><?php endif; ?>
<?php foreach ($category['attribute_tables'] as $group) : ?><div class="product-table-group"><h3><?php echo esc_html($group['title']); ?></h3><?php Starter\Theme\component_spec_table(['rows' => $group['rows']]); ?></div><?php endforeach; ?>
</section>
<?php endif; ?>
<?php if ($category['faq']) : ?>
<section id="faq" class="product-block"><h2>FAQ</h2><?php Starter\Theme\component_faq(['rows' => $category['faq']]); ?></section>
<?php endif; ?>
</div>
<?php endif; ?>
<?php Starter\Theme\component_product_inquiry(['title' => $category['cta']['title'], 'note' => $category['cta']['description'], 'url' => $category['cta']['url']]); ?>
</div>
</main>
<?php
$items = [];
foreach (($category['show_products'] ? ($product_query->posts ?? []) : []) as $index => $post) $items[] = ['@type' => 'ListItem', 'position' => (max(1, (int) get_query_var('paged')) - 1) * (int) $product_query->get('posts_per_page') + $index + 1, 'url' => get_permalink($post), 'name' => get_the_title($post)];
?>
<?php if ($items) : ?><script type="application/ld+json"><?php echo wp_json_encode(['@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => $category['name'], 'itemListElement' => $items], JSON_HEX_TAG | JSON_UNESCAPED_SLASHES); ?></script><?php endif; ?>

