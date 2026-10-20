<?php
/**
 * Template Name: Contact / RFQ
 * Template Post Type: page
 * Template Content Source: ACF
 *
 * @package b2b-starter
 */

get_header(); ?>
<main id="main" class="shell contact-page"><?php while (have_posts()) : the_post();
    $data = Starter\Theme\contact_data();
    $has_content = $data['body'] !== '' || $data['faq'];
    $legacy_body = (string) get_post_field('post_content', get_the_ID());
    $legacy_form = has_shortcode($legacy_body, 'starter_rfq_form') || has_shortcode($legacy_body, 'fluentform');
    Starter\Theme\component_page_head([
        'title' => get_the_title(),
        'description' => $data['intro'],
    ]);
?>
    <div id="rfq-form" class="contact-layout<?php echo !$has_content ? ' is-form-only' : ''; ?>">
        <?php if ($has_content) : ?>
            <div class="contact-reading">
                <?php if ($data['body'] !== '') : ?><div class="contact-copy prose"><?php echo $data['body']; // Formatted and escaped by ACF before shortcode rendering. ?></div><?php endif; ?>
                <?php if ($data['faq']) : ?>
                    <section class="contact-faq" aria-labelledby="contact-faq-heading">
                        <h2 id="contact-faq-heading">FAQs</h2>
                        <?php Starter\Theme\component_faq(['rows' => $data['faq']]); ?>
                    </section>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($legacy_form) : ?>
            <aside id="product-inquiry" class="product-inquiry" aria-labelledby="contact-form-heading"><div class="product-inquiry-card form-panel">
                <h2 id="contact-form-heading"><?php echo esc_html($data['form_title']); ?></h2>
                <?php the_content(); // Preserve existing custom form IDs and surrounding content. ?>
                <?php if ($data['form_note'] !== '') : ?><p class="conversion-note"><?php echo esc_html($data['form_note']); ?></p><?php endif; ?>
            </div></aside>
        <?php else : ?>
            <?php Starter\Theme\component_product_inquiry(['title' => $data['form_title'], 'note' => $data['form_note']]); ?>
        <?php endif; ?>
    </div>
<?php endwhile; ?></main>
<?php get_footer(); ?>

