<?php
use function Starter\Theme\get_setting;
use function Starter\Theme\footer_menu;
use function Starter\Theme\field_option;
$email = get_setting('contact_email');
$phone = get_setting('contact_phone');
$name = get_bloginfo('name');
$wa = $phone ? 'https://wa.me/' . preg_replace('/\D/', '', $phone) : '';
$groups = [
    'footer_products' => ['footer_products_title', __('Machining capabilities', 'b2b-starter')],
    'footer_components' => ['footer_components_title', __('Precision components', 'b2b-starter')],
    'footer_explore' => ['footer_explore_title', __('Company', 'b2b-starter')],
    'footer_resources' => ['footer_resources_title', __('Buyer resources', 'b2b-starter')],
];
$cta_title = (string) get_theme_mod('axis_footer_cta_title', 'Have a drawing ready?');
$cta_text = (string) get_theme_mod('axis_footer_cta_text', 'Share your drawing, material, quantity and finish requirements.');
$disclosure = (string) get_theme_mod('axis_footer_disclosure', 'Product, assembly, application and workshop images are AI-generated concept illustrations. Specifications and supplier evidence are confirmed for each project.');
$disclosure = \AxisCNC\SuiteTheme\public_caption($disclosure);
$legal = footer_menu('footer_legal');
?>
<footer id="site-footer" class="site-footer axis-footer"><div class="shell">
    <div class="axis-footer-intro">
        <div class="axis-footer-brand"><a href="<?php echo esc_url(home_url('/')); ?>"><img src="<?php echo esc_url(get_theme_file_uri('assets/axis-logo-light.svg')); ?>" width="270" height="58" alt="<?php echo esc_attr($name); ?>"></a><?php if (field_option('factory_intro')): ?><p><?php echo esc_html(field_option('factory_intro')); ?></p><?php endif; ?></div>
        <?php if ($email || $wa): ?><address class="axis-footer-contact"><span><?php esc_html_e('Start a conversation', 'b2b-starter'); ?></span><?php if ($email): ?><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?><span aria-hidden="true">↗</span></a><?php endif; ?><?php if ($wa): ?><a href="<?php echo esc_url($wa); ?>" target="_blank" rel="noopener noreferrer">WhatsApp <?php echo esc_html($phone); ?><span aria-hidden="true">↗</span></a><?php endif; ?></address><?php endif; ?>
    </div>
    <div class="axis-footer-navigation">
        <?php foreach ($groups as $location => [$setting, $default]): $menu = footer_menu($location); if (!$menu) continue; $heading = (string) get_theme_mod($setting, $default); ?>
            <nav class="axis-footer-group" aria-label="<?php echo esc_attr($heading ?: $default); ?>"><?php if ($heading !== ''): ?><h2><?php echo esc_html($heading); ?></h2><?php endif; ?><?php echo $menu; ?></nav>
        <?php endforeach; ?>
    </div>
    <?php if ($cta_title !== '' || $cta_text !== ''): ?><div class="axis-footer-cta"><div><?php if ($cta_title !== ''): ?><h2><?php echo esc_html($cta_title); ?></h2><?php endif; ?><?php if ($cta_text !== ''): ?><p><?php echo esc_html($cta_text); ?></p><?php endif; ?></div><a class="button" href="<?php echo esc_url(Starter\Theme\contact_url()); ?>"><?php echo esc_html(get_setting('header_cta_label', 'Request a quote')); ?><span aria-hidden="true">↗</span></a></div><?php endif; ?>
    <div class="axis-footer-bottom"><small>© <?php echo esc_html(wp_date('Y')); ?> <?php echo esc_html($name); ?>.</small><?php if ($legal): ?><nav aria-label="<?php esc_attr_e('Legal information', 'b2b-starter'); ?>"><?php echo $legal; ?></nav><?php endif; ?><a href="#main"><?php esc_html_e('Back to top', 'b2b-starter'); ?> <span aria-hidden="true">↑</span></a></div>
    <?php if ($disclosure !== ''): ?><p class="axis-footer-disclosure"><?php echo esc_html($disclosure); ?></p><?php endif; ?>
</div></footer>
