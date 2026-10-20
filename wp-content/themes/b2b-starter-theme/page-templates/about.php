<?php
/**
 * Template Name: About page
 * Template Post Type: page
 * Template Content Source: ACF
 *
 * @package b2b-starter
 */

get_header(); ?>
<main id="main" class="shell about-page"><?php while (have_posts()) : the_post();
	$data = Starter\Theme\about_data();
	$sections = Starter\Theme\about_content_sections();
	$photos = Starter\Theme\about_gallery_images();
	$faq = Starter\Theme\about_faq_rows();
	?>
	<?php if (has_post_thumbnail()) : ?><figure class="about-photo"><?php the_post_thumbnail('large', ['loading' => 'eager']); ?></figure><?php endif; ?>

	<header class="about-hero">
		<h1><?php the_title(); ?></h1>
		<?php if (has_excerpt()) : ?><p class="about-leader"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
	</header>

	<?php if (Starter\Theme\has_rich_content($data['story'])) : ?>
	<article class="about-story prose"><?php echo $data['story']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- formatted ACF WYSIWYG or legacy editor HTML. ?></article>
	<?php endif; ?>

    <?php if ($sections) : ?><div class="about-modules"><?php Starter\Theme\component_category_sections($sections); ?></div><?php endif; ?>

    <?php Starter\Theme\component_about_gallery([
        'images' => $photos,
        'title' => Starter\Theme\text('about_gallery_title'),
        'intro' => Starter\Theme\text('about_gallery_intro'),
    ]); ?>

    <?php if ($faq) : ?>
    <section class="about-faq" aria-labelledby="about-faq-heading">
        <h2 id="about-faq-heading"><?php echo esc_html(Starter\Theme\text('about_faq_title') ?: __('FAQs', 'b2b-starter')); ?></h2>
        <?php Starter\Theme\component_faq(['rows' => $faq]); ?>
    </section>
    <?php endif; ?>

<?php endwhile; ?></main>
<?php get_footer(); ?>

