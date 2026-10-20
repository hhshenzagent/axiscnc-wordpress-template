<?php
/**
 * Generic page.
 *
 * @package b2b-starter
 */

$thank_you_page_id = (int) get_option('starter_inquiry_thank_you_page_id', 0);
$is_inquiry_thank_you = $thank_you_page_id > 0 && is_page($thank_you_page_id)
	&& get_post_status($thank_you_page_id) === 'publish';

if (get_theme_mod('axis_suite_enabled')) {
    AxisCNC\SuiteTheme\start();
    echo '<section class="suite-section shell suite-guide-body">';
    while (have_posts()) { the_post();
        echo '<h1>' . esc_html(get_the_title()) . '</h1>';
        if (has_post_thumbnail()) { the_post_thumbnail('large'); }
        the_content();
    }
    echo '</section>';
    AxisCNC\SuiteTheme\end();
    return;
}

get_header(); ?>
<main id="main" class="shell<?php echo $is_inquiry_thank_you ? ' inquiry-thank-you' : ''; ?>"><?php while (have_posts()) : the_post(); ?>
	<?php Starter\Theme\component_page_head(['title' => get_the_title(), 'breadcrumbs' => !$is_inquiry_thank_you]); ?>
	<article class="prose article"><?php
		if (has_post_thumbnail()) {
			the_post_thumbnail('large', ['class' => 'page-photo']);
		}
		the_content();
	?></article>
<?php endwhile; ?></main>
<?php get_footer(); ?>

