<?php
/**
 * Global topbar and primary navigation.
 *
 * @package b2b-starter
 */

$topbar = Starter\Theme\get_setting('topbar_text');
$email = Starter\Theme\get_setting('contact_email');
$phone = Starter\Theme\get_setting('contact_phone');
?>
<?php if ($topbar) : ?><div class="topbar axis-topbar"><div class="shell"><span><?php echo esc_html($topbar); ?></span><div class="axis-topbar-links"><?php if ($email) : ?><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a><?php endif; ?><?php if ($phone): ?><a href="<?php echo esc_url('https://wa.me/' . preg_replace('/\D/', '', $phone)); ?>" target="_blank" rel="noopener noreferrer">WhatsApp <span aria-hidden="true">↗</span></a><?php endif; ?></div></div></div><?php endif; ?>
<header class="site-header"><div class="shell">
<div class="brand"><a href="<?php echo esc_url(home_url('/')); ?>" aria-label="AXISCNC home"><img class="axis-logo" src="<?php echo esc_url(get_theme_file_uri('assets/axis-logo.svg')); ?>" width="270" height="58" alt="AXISCNC — Precision in every axis"></a></div>
<button class="nav-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="primary-nav"><span></span><span></span><span></span></button>
<nav id="primary-nav" class="primary-nav" aria-label="Primary"><?php
wp_nav_menu([
    'theme_location' => 'primary',
    'container' => false,
    'fallback_cb' => false,
    'depth' => 3,
    'menu_class' => 'axis-primary-menu',
]);
?></nav>
<a class="button header-cta" href="<?php echo esc_url(Starter\Theme\contact_url()); ?>"><?php echo esc_html(Starter\Theme\get_setting('header_cta_label', 'Request a quote')); ?></a>
</div></header>

