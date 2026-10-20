<?php
/** Simple product page: the same hero, complete description and real inquiry form. */
$context = get_query_var('product_template_context');
$product = $context['product'] ?? Starter\Theme\product_data();
$hero_cta = $product['cta'];
$hero_cta['url'] = '#product-inquiry';
?>
<main id="main" class="shell product-page product-default product-simple">
<?php Starter\Theme\breadcrumbs(); ?>
<section class="product-hero" aria-label="Product overview">
<?php Starter\Theme\component_product_gallery(['images' => $product['gallery'], 'title' => $product['title']]); ?>
<?php Starter\Theme\component_product_hero_summary(['category' => $product['category'], 'title' => $product['title'], 'description' => $product['excerpt'], 'value_chips' => $product['value_chips'], 'cta' => $hero_cta]); ?>
</section>
<?php get_template_part('parts/product-reading', null, ['product' => $product, 'layout' => 'simple']); ?>
</main>

